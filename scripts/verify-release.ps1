[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $Archive,
    [string] $ChecksumFile,
    [string] $PhpExe = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Invoke-PhpArtisan {
    param([Parameter(ValueFromRemainingArguments = $true)][string[]] $Arguments)

    & $PhpExe artisan @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "Artisan gagal: php artisan $($Arguments -join ' ')"
    }
}

function Remove-VerificationDirectory {
    param([string] $Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        return
    }

    Get-ChildItem -LiteralPath $Path -Force -Recurse -ErrorAction SilentlyContinue | ForEach-Object {
        if (-not $_.PSIsContainer -and $_.IsReadOnly) {
            $_.IsReadOnly = $false
        }
    }

    [System.IO.Directory]::Delete($Path, $true)
}

$projectDir = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$verificationRoot = [System.IO.Path]::GetFullPath((Join-Path $projectDir 'storage\app\release-verification'))
$archivePath = (Resolve-Path -LiteralPath $Archive).Path

if (-not (Test-Path -LiteralPath $PhpExe -PathType Leaf)) {
    throw "PHP executable tidak ditemukan: $PhpExe"
}

if ([string]::IsNullOrWhiteSpace($ChecksumFile)) {
    $candidate = "$archivePath.sha256"

    if (Test-Path -LiteralPath $candidate -PathType Leaf) {
        $ChecksumFile = $candidate
    }
}

if (-not [string]::IsNullOrWhiteSpace($ChecksumFile)) {
    $checksumPath = (Resolve-Path -LiteralPath $ChecksumFile).Path
    $checksumText = (Get-Content -LiteralPath $checksumPath -Raw).Trim()

    if ($checksumText -notmatch '^(?<hash>[a-fA-F0-9]{64})(\s+.+)?$') {
        throw 'Format checksum SHA-256 tidak valid.'
    }

    $expectedHash = $Matches.hash.ToLowerInvariant()
    $actualHash = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant()

    if ($actualHash -ne $expectedHash) {
        throw 'Checksum artefak tidak cocok.'
    }

    Write-Output "[LULUS] Checksum SHA-256: $actualHash"
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($archivePath)

try {
    $entries = @($zip.Entries | ForEach-Object { $_.FullName.Replace('\', '/') })
    $requiredEntries = @(
        'artisan',
        'vendor/autoload.php',
        'public/build/manifest.json',
        'routes/web.php',
        'app/Console/Commands/CheckPilotReadiness.php'
    )

    foreach ($required in $requiredEntries) {
        if ($entries -notcontains $required) {
            throw "Artefak tidak lengkap; entry wajib tidak ditemukan: $required"
        }
    }

    $forbidden = @($entries | Where-Object {
        $_ -match '(^|/)\.env$' -or
        $_ -match '(^|/)public/hot$' -or
        $_ -match '(^|/)node_modules(/|$)' -or
        $_ -match '(^|/)\.git(/|$)' -or
        $_ -match '^vendor/(phpunit|mockery|fakerphp)(/|$)' -or
        $_ -match '^storage/app/backups(/|$)' -or
        $_ -match '^storage/logs/.+\.log$' -or
        $_ -match '\.(sqlite|sqlite3)$'
    })

    if ($forbidden.Count -gt 0) {
        $sample = ($forbidden | Select-Object -First 5) -join ', '
        throw "Artefak memuat entry terlarang ($($forbidden.Count)): $sample"
    }

    Write-Output "[LULUS] Struktur ZIP aman dan lengkap: $($entries.Count) entry"
}
finally {
    $zip.Dispose()
}

New-Item -ItemType Directory -Force -Path $verificationRoot | Out-Null
$verificationDir = Join-Path $verificationRoot ([System.IO.Path]::GetFileNameWithoutExtension($archivePath) + '-' + [guid]::NewGuid().ToString('N'))
$resolvedVerificationDir = [System.IO.Path]::GetFullPath($verificationDir)

if (-not $resolvedVerificationDir.StartsWith($verificationRoot + [System.IO.Path]::DirectorySeparatorChar, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'Direktori rehearsal berada di luar release-verification.'
}

$environmentNames = @(
    'APP_ENV', 'APP_DEBUG', 'APP_KEY', 'APP_URL', 'APP_TIMEZONE', 'APP_LOCALE',
    'DB_CONNECTION', 'DB_DATABASE', 'CACHE_STORE', 'SESSION_DRIVER',
    'SESSION_ENCRYPT', 'SESSION_SECURE_COOKIE', 'QUEUE_CONNECTION',
    'MAIL_MAILER', 'WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'WHATSAPP_APP_SECRET',
    'WHATSAPP_PHONE_NUMBER_ID', 'WHATSAPP_ACCESS_TOKEN', 'WHATSAPP_GRAPH_VERSION',
    'WHATSAPP_OUTBOUND_ENABLED'
)
$originalEnvironment = @{}

foreach ($name in $environmentNames) {
    $originalEnvironment[$name] = [System.Environment]::GetEnvironmentVariable($name, 'Process')
}

try {
    Expand-Archive -LiteralPath $archivePath -DestinationPath $resolvedVerificationDir
    $databasePath = Join-Path $resolvedVerificationDir 'database\release-verification.sqlite'
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $databasePath) | Out-Null
    [System.IO.File]::WriteAllBytes($databasePath, [byte[]]@())

    $keyBytes = New-Object byte[] 32
    $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $random.GetBytes($keyBytes)
    $random.Dispose()

    $env:APP_ENV = 'production'
    $env:APP_DEBUG = 'false'
    $env:APP_KEY = 'base64:' + [Convert]::ToBase64String($keyBytes)
    $env:APP_URL = 'https://release-verification.invalid'
    $env:APP_TIMEZONE = 'Asia/Jakarta'
    $env:APP_LOCALE = 'id'
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $databasePath
    $env:CACHE_STORE = 'database'
    $env:SESSION_DRIVER = 'database'
    $env:SESSION_ENCRYPT = 'true'
    $env:SESSION_SECURE_COOKIE = 'true'
    $env:QUEUE_CONNECTION = 'database'
    $env:MAIL_MAILER = 'log'
    $env:WHATSAPP_WEBHOOK_VERIFY_TOKEN = 'release-verification-only'
    $env:WHATSAPP_APP_SECRET = 'release-verification-only'
    $env:WHATSAPP_PHONE_NUMBER_ID = 'release-verification-only'
    $env:WHATSAPP_ACCESS_TOKEN = 'release-verification-only'
    $env:WHATSAPP_GRAPH_VERSION = 'v99.0'
    $env:WHATSAPP_OUTBOUND_ENABLED = 'false'

    Push-Location $resolvedVerificationDir

    try {
        Invoke-PhpArtisan '--version'
        Invoke-PhpArtisan 'migrate' '--force' '--no-interaction'
        Invoke-PhpArtisan 'config:cache'
        Invoke-PhpArtisan 'route:cache'
        Invoke-PhpArtisan 'view:cache'
        Invoke-PhpArtisan 'route:list' '--path=webhooks/whatsapp'
        Invoke-PhpArtisan 'optimize:clear'

        $env:APP_ENV = 'local'
        $env:APP_DEBUG = 'true'
        $env:APP_URL = 'http://127.0.0.1:8999'

        Invoke-PhpArtisan 'pilot:setup-local' '--base-url=http://127.0.0.1:8999'
        Invoke-PhpArtisan 'pilot:readiness'
    }
    finally {
        Pop-Location
    }

    Write-Output '[LULUS] Boot, migration, production cache, route webhook, dan pilot rehearsal'
    Write-Output 'Release verification completed without production credentials.'
}
finally {
    foreach ($name in $environmentNames) {
        [System.Environment]::SetEnvironmentVariable($name, $originalEnvironment[$name], 'Process')
    }

    Remove-VerificationDirectory -Path $resolvedVerificationDir
}

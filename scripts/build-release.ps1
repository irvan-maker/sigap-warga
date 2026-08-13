[CmdletBinding()]
param(
    [string] $Commit = 'HEAD',
    [string] $PhpExe = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe',
    [string] $ComposerPhar = 'C:\laragon\bin\composer\composer.phar'
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$projectDir = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$releaseRoot = Join-Path $projectDir 'storage\app\releases'
$commitHash = (& git -C $projectDir rev-parse $Commit).Trim()

if ($LASTEXITCODE -ne 0 -or $commitHash -notmatch '^[a-f0-9]{40}$') {
    throw 'Commit release tidak valid.'
}

$shortHash = $commitHash.Substring(0, 7)
$buildId = Get-Date -Format 'yyyyMMdd-HHmmss'
$stagingDir = Join-Path $releaseRoot "staging-$shortHash-$buildId"
$sourceArchive = Join-Path $releaseRoot "source-$shortHash-$buildId.zip"
$releaseArchive = Join-Path $releaseRoot "sigap-warga-$shortHash-$buildId.zip"
$checksumPath = "$releaseArchive.sha256"

New-Item -ItemType Directory -Force -Path $releaseRoot | Out-Null
$resolvedReleaseRoot = [System.IO.Path]::GetFullPath($releaseRoot)

foreach ($path in @($stagingDir, $sourceArchive, $releaseArchive, $checksumPath)) {
    $resolvedPath = [System.IO.Path]::GetFullPath($path)

    if (-not $resolvedPath.StartsWith($resolvedReleaseRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw 'Target artefak berada di luar direktori release.'
    }

    if (Test-Path -LiteralPath $resolvedPath) {
        throw "Target build unik sudah tersedia: $resolvedPath"
    }
}

& git -C $projectDir archive --format=zip "--output=$sourceArchive" $commitHash

if ($LASTEXITCODE -ne 0) {
    throw 'Git archive gagal.'
}

Expand-Archive -LiteralPath $sourceArchive -DestinationPath $stagingDir

$buildSource = Join-Path $projectDir 'public\build'
$buildTarget = Join-Path $stagingDir 'public\build'

if (-not (Test-Path -LiteralPath (Join-Path $buildSource 'manifest.json'))) {
    throw 'Manifest frontend tidak tersedia. Jalankan npm run build terlebih dahulu.'
}

Copy-Item -LiteralPath $buildSource -Destination $buildTarget -Recurse -Force
Copy-Item -LiteralPath (Join-Path $projectDir 'vendor') -Destination (Join-Path $stagingDir 'vendor') -Recurse -Force

foreach ($relativeDirectory in @(
    'bootstrap\cache',
    'storage\app\private',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs'
)) {
    New-Item -ItemType Directory -Force -Path (Join-Path $stagingDir $relativeDirectory) | Out-Null
}

& $PhpExe $ComposerPhar install `
    --working-dir $stagingDir `
    --no-dev `
    --prefer-dist `
    --optimize-autoloader `
    --no-interaction `
    --no-progress

if ($LASTEXITCODE -ne 0) {
    throw 'Composer production install gagal.'
}

if ((Test-Path -LiteralPath (Join-Path $stagingDir '.env')) -or (Test-Path -LiteralPath (Join-Path $stagingDir 'public\hot'))) {
    throw 'Artefak memuat file environment atau Vite hot yang dilarang.'
}

Compress-Archive -Path (Join-Path $stagingDir '*') -DestinationPath $releaseArchive -CompressionLevel Optimal
$checksum = (Get-FileHash -LiteralPath $releaseArchive -Algorithm SHA256).Hash.ToLowerInvariant()
[System.IO.File]::WriteAllText($checksumPath, "$checksum  $(Split-Path -Leaf $releaseArchive)`n")

Remove-Item -LiteralPath $stagingDir -Recurse -Force
Remove-Item -LiteralPath $sourceArchive -Force

Write-Output "Release: $releaseArchive"
Write-Output "SHA256: $checksum"

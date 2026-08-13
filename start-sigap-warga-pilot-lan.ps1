[CmdletBinding()]
param(
    [ValidateRange(1024, 65535)]
    [int] $Port = 8000
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpExe = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
$nodeDir = 'C:\laragon\bin\nodejs\node-v22'
$npmCmd = Join-Path $nodeDir 'npm.cmd'
$manifestPath = Join-Path $projectDir 'public\build\manifest.json'
$hotPath = Join-Path $projectDir 'public\hot'
$queueProcess = $null

if (-not (Test-Path -LiteralPath $phpExe)) {
    throw "PHP executable tidak ditemukan: $phpExe"
}

if (-not (Test-Path -LiteralPath (Join-Path $projectDir 'artisan'))) {
    throw "Folder project tidak valid: $projectDir"
}

$network = Get-NetIPConfiguration |
    Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' } |
    Select-Object -First 1
$lanAddress = $network.IPv4Address.IPAddress | Select-Object -First 1

if (-not $lanAddress -or $lanAddress -like '169.254.*') {
    throw 'Alamat IPv4 LAN aktif tidak ditemukan. Sambungkan komputer ke Wi-Fi/LAN pilot terlebih dahulu.'
}

$baseUrl = "http://${lanAddress}:$Port"

$listeners = Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue

if ($listeners) {
    $owners = ($listeners | Select-Object -ExpandProperty OwningProcess -Unique) -join ', '
    throw "Port $Port sudah digunakan proses PID $owners. Tutup server lama atau pilih -Port lain; launcher tidak menghentikan proses yang bukan miliknya."
}

Push-Location $projectDir

try {
    if (-not (Test-Path -LiteralPath $npmCmd)) {
        throw "npm tidak ditemukan: $npmCmd"
    }

    $env:PATH = "$nodeDir;$env:PATH"
    & $npmCmd run build

    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $manifestPath)) {
        throw 'Build aset aplikasi gagal.'
    }

    if (Test-Path -LiteralPath $hotPath) {
        $resolvedHotPath = [System.IO.Path]::GetFullPath($hotPath)
        $resolvedPublicPath = [System.IO.Path]::GetFullPath((Join-Path $projectDir 'public'))

        if (-not $resolvedHotPath.StartsWith($resolvedPublicPath, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw 'Lokasi public/hot berada di luar project; launcher dihentikan.'
        }

        Remove-Item -LiteralPath $resolvedHotPath -Force
    }

    & $phpExe artisan migrate:status --no-interaction | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw 'Pemeriksaan database gagal. Jalankan migrasi secara terkontrol sebelum uji pilot.'
    }

    & $phpExe artisan pilot:setup-local --refresh-qr "--base-url=$baseUrl" --no-interaction

    if ($LASTEXITCODE -ne 0) {
        throw 'Pembaruan URL QR pilot gagal. Server tidak dijalankan.'
    }

    Write-Host ''
    Write-Host "SIGAP WARGA pilot tersedia di $baseUrl" -ForegroundColor Green
    Write-Host "Halaman QR: $projectDir\storage\app\private\pilot\rw-pilot-01\CETAK-QR.html"
    Write-Host 'Ponsel harus memakai Wi-Fi/LAN yang sama.'
    Write-Host 'Jika Windows meminta izin firewall, izinkan hanya untuk Private networks.'
    Write-Host 'Queue worker pilot dijalankan otomatis dan dihentikan bersama server.'
    Write-Host 'Tekan Ctrl+C untuk menghentikan server.'
    Write-Host ''

    $queueProcess = Start-Process -FilePath $phpExe `
        -ArgumentList @('artisan', 'queue:work', '--queue=whatsapp,default', '--tries=3', '--backoff=5', '--timeout=60') `
        -WorkingDirectory $projectDir `
        -WindowStyle Hidden `
        -PassThru

    & $phpExe artisan serve --host=0.0.0.0 "--port=$Port"
}
finally {
    if ($queueProcess -ne $null -and -not $queueProcess.HasExited) {
        Stop-Process -Id $queueProcess.Id -ErrorAction SilentlyContinue
    }

    Pop-Location
}

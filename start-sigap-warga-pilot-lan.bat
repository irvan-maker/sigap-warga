@echo off
setlocal

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0start-sigap-warga-pilot-lan.ps1"

if errorlevel 1 (
    echo.
    echo [ERROR] Pilot LAN tidak berhasil dijalankan.
    pause
)

endlocal

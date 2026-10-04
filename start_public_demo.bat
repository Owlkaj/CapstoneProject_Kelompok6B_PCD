@echo off
setlocal EnableDelayedExpansion
title TomatCare — All-in-One Public Demo Launcher
cd /d "%~dp0"

echo ==============================================================================
echo       TomatCare — 1-Click All-in-One Public Demo Launcher
echo ==============================================================================
echo.

:: 1. Periksa & Nyalakan FastAPI (Port 8001)
netstat -ano | findstr /R /C:":8001 .*LISTENING" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo [OK] FastAPI AI Service (Port 8001) sudah berjalan aktif.
) else (
    echo [1/3] Menyalakan FastAPI AI Model Service (Port 8001)...
    start "TomatCare - FastAPI AI (Port 8001)" /min cmd /c "cd /d "%~dp0fastapi" && ..\ml\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8001"
)

:: 2. Periksa & Nyalakan Laravel (Port 8000)
netstat -ano | findstr /R /C:":8000 .*LISTENING" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo [OK] Laravel Web Server (Port 8000) sudah berjalan aktif.
) else (
    echo [2/3] Menyalakan Laravel Web Application (Port 8000)...
    start "TomatCare - Laravel Web (Port 8000)" /min cmd /c "cd /d "%~dp0laravel" && php artisan serve --port 8000"
)

:: 3. Beri jeda 3 detik agar server siap
echo [3/3] Menyiapkan koneksi publik Cloudflare Tunnel...
timeout /t 3 /nobreak >nul

echo.
echo ==============================================================================
echo   LINK WEB PUBLIK AKAN MUNCUL DI BAWAH (https://...trycloudflare.com)
echo   Biarkan jendela ini tetap terbuka selama demo berlangsung!
echo   Tekan Ctrl + C untuk menghentikan tunnel publik.
echo ==============================================================================
echo.

"%~dp0cloudflared.exe" tunnel --url http://127.0.0.1:8000

echo.
echo Tunnel telah dihentikan.
pause

@echo off
title TomatCare - Stop All Services
cd /d "%~dp0"

echo ==============================================================================
echo       TomatCare - Menghentikan Seluruh Service Lokal dan Tunnel
echo ==============================================================================
echo.

echo Menghentikan Cloudflare Tunnel...
taskkill /F /IM cloudflared.exe >nul 2>&1

echo Menghentikan Laravel Server [Port 8000]...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr /R /C:":8000 .*LISTENING"') do taskkill /F /PID %%a >nul 2>&1

echo Menghentikan FastAPI AI Server [Port 8001]...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr /R /C:":8001 .*LISTENING"') do taskkill /F /PID %%a >nul 2>&1

echo.
echo [SELESAI] Seluruh server (FastAPI, Laravel, Cloudflare) telah dimatikan.
ping 127.0.0.1 -n 3 >nul
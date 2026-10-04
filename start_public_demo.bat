@echo off
title TomatCare - Public Tunnel Demo (100% Free)
echo ==============================================================================
echo       TomatCare — Cloudflare Public Tunnel Demo (Tanpa Kartu Kredit)
echo ==============================================================================
echo.
echo Menghubungkan aplikasi web TomatCare (port 8000) ke internet publik...
echo Link HTTPS publik akan muncul di bawah dalam beberapa detik.
echo.
echo Tekan Ctrl + C jika ingin menghentikan tunnel publik.
echo ==============================================================================
echo.
cloudflared.exe tunnel --url http://127.0.0.1:8000
pause

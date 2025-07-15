@echo off
title OtoAsist Backend Server

echo Starting OtoAsist Backend Server...
echo.

cd /d "%~dp0"

:START
echo Backend is running on http://localhost:8000
echo Press Ctrl+C to stop the server
echo.

php -S localhost:8000

echo.
echo Server stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak > nul
goto START 
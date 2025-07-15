@echo off
echo OtoAsist Backend API Server Starting...
echo.
echo Server will be available at: http://127.0.0.1:8000
echo API Base URL: http://127.0.0.1:8000/api/v1
echo.
echo Press Ctrl+C to stop the server
echo.
cd /d "%~dp0"
php -S 127.0.0.1:8000 -t . router.php 
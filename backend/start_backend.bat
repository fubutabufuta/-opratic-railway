@echo off
color 0A
echo ====================================
echo      OtoAsist Backend Server
echo ====================================
echo.

REM Check if PHP is installed
php --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP is not installed or not in PATH
    echo Please install PHP and add it to your PATH
    pause
    exit /b 1
)

echo [INFO] PHP is installed
php --version

REM Check if MySQL is running (try to connect)
echo.
echo [INFO] Checking MySQL connection...
php -r "try { new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); echo 'MySQL is running'; } catch (Exception $e) { echo 'MySQL is not running or not accessible'; exit(1); }"

if errorlevel 1 (
    echo.
    echo [ERROR] MySQL is not running!
    echo Please start MySQL server first:
    echo - XAMPP: Start MySQL from XAMPP Control Panel
    echo - WAMP: Start MySQL from WAMP interface
    echo - Laragon: Start MySQL from Laragon
    echo - Manual: Start MySQL service
    echo.
    pause
    exit /b 1
)

echo.
echo [INFO] MySQL is running
echo.

REM Start PHP built-in server
echo [INFO] Starting PHP built-in server...
echo [INFO] Backend will be available at: http://localhost:8000
echo [INFO] Press Ctrl+C to stop the server
echo.

REM Open browser to backend status page
start http://localhost:8000/start_backend.php

REM Start the PHP server
php -S localhost:8000 -t .

echo.
echo [INFO] Backend server stopped
pause 
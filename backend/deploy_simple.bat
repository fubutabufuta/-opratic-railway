@echo off
title OtoAsist SiteGround Deployment

echo.
echo ===== OtoAsist SiteGround Deployment =====
echo.
echo ORJINAL DOSYALAR KORUNACAK!
echo.

:: Deployment klasörü oluştur
if not exist "deployment" mkdir deployment
if not exist "deployment\siteground" mkdir deployment\siteground

:: Backend dosyalarını kopyala
echo [1/4] Backend dosyalari kopyalaniyor...
xcopy "api" "deployment\siteground\api\" /E /I /Y >nul
xcopy "config" "deployment\siteground\config\" /E /I /Y >nul
if exist "router.php" copy "router.php" "deployment\siteground\" >nul

:: .htaccess dosyası oluştur
echo [2/4] .htaccess dosyasi olusturuluyor...
echo RewriteEngine On > "deployment\siteground\.htaccess"
echo. >> "deployment\siteground\.htaccess"
echo # CORS Headers >> "deployment\siteground\.htaccess"
echo Header always set Access-Control-Allow-Origin "*" >> "deployment\siteground\.htaccess"
echo Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" >> "deployment\siteground\.htaccess"
echo Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With" >> "deployment\siteground\.htaccess"
echo. >> "deployment\siteground\.htaccess"
echo # API Routes >> "deployment\siteground\.htaccess"
echo RewriteCond %%{REQUEST_FILENAME} !-f >> "deployment\siteground\.htaccess"
echo RewriteCond %%{REQUEST_FILENAME} !-d >> "deployment\siteground\.htaccess"
echo RewriteRule ^^api/(.*)$ router.php [QSA,L] >> "deployment\siteground\.htaccess"

:: Test dosyası oluştur
echo [3/4] Test dosyasi olusturuluyor...
echo ^<?php > "deployment\siteground\test_api.php"
echo header("Content-Type: text/html; charset=utf-8"); >> "deployment\siteground\test_api.php"
echo echo "^<h1^>OtoAsist API Test^</h1^>"; >> "deployment\siteground\test_api.php"
echo require_once 'config/database.php'; >> "deployment\siteground\test_api.php"
echo echo "^<p^>Test completed^</p^>"; >> "deployment\siteground\test_api.php"
echo ?^> >> "deployment\siteground\test_api.php"

:: README oluştur
echo [4/4] README olusturuluyor...
echo OtoAsist SiteGround Deployment > "deployment\siteground\README.txt"
echo ================================ >> "deployment\siteground\README.txt"
echo. >> "deployment\siteground\README.txt"
echo 1. Bu klasordeki tum dosyalari SiteGround public_html'e yukle >> "deployment\siteground\README.txt"
echo 2. SiteGround'da MySQL veritabani olustur >> "deployment\siteground\README.txt"
echo 3. config/database.php'yi duzenle >> "deployment\siteground\README.txt"
echo 4. SQL dosyasini phpMyAdmin'e import et >> "deployment\siteground\README.txt"
echo 5. test_api.php'yi test et >> "deployment\siteground\README.txt"

echo.
echo ===== DEPLOYMENT TAMAMLANDI! =====
echo.
echo Hazir dosyalar: deployment\siteground\
echo.
echo SONRAKI ADIMLAR:
echo 1. SiteGround File Manager'a git
echo 2. deployment\siteground\ icerigini public_html'e yukle
echo 3. Veritabani olustur ve config'i duzenle
echo 4. SQL dosyasini import et
echo.
pause 
@echo off
title OtoAsist SiteGround Deployment

echo.
echo ===============================================
echo   OtoAsist SiteGround Deployment Baslatiyor
echo ===============================================
echo.

:: Deployment klasoru olustur
if not exist "deployment\siteground" mkdir deployment\siteground

:: Backend dosyalarini kopyala
echo [1/6] Backend dosyalari kopyalaniyor...
xcopy "api" "deployment\siteground\api\" /E /I /Y >nul
xcopy "config" "deployment\siteground\config\" /E /I /Y >nul
copy "router.php" "deployment\siteground\" >nul

:: .htaccess dosyasi olustur
echo [2/6] .htaccess dosyasi olusturuluyor...
(
echo RewriteEngine On
echo.
echo # CORS Headers
echo Header always set Access-Control-Allow-Origin "*"
echo Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
echo Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
echo.
echo # Handle preflight OPTIONS requests
echo RewriteCond %%{REQUEST_METHOD} OPTIONS
echo RewriteRule ^^(.*)$ $1 [R=200,L]
echo.
echo # API Routes
echo RewriteCond %%{REQUEST_FILENAME} !-f
echo RewriteCond %%{REQUEST_FILENAME} !-d
echo RewriteRule ^^api/(.*)$ router.php [QSA,L]
echo.
echo # Default
echo DirectoryIndex index.php router.php
echo.
echo # Guvenlik
echo ^<Files "*.sql"^>
echo     Order allow,deny
echo     Deny from all
echo ^</Files^>
echo.
echo ^<Files "*.log"^>
echo     Order allow,deny
echo     Deny from all
echo ^</Files^>
) > "deployment\siteground\.htaccess"

:: Production config olustur
echo [3/6] Production config olusturuluyor...
(
echo ^<?php
echo class Database {
echo     // SiteGround Production Settings
echo     private $host = "localhost";
echo     private $db_name = "VERITABANI_ADI"; // Buraya SiteGround DB adini yaz
echo     private $username = "KULLANICI_ADI"; // Buraya SiteGround DB kullanicisini yaz
echo     private $password = "SIFRE"; // Buraya SiteGround DB sifresini yaz
echo     private $conn;
echo.
echo     public function getConnection(^) {
echo         $this-^>conn = null;
echo         
echo         try {
echo             $this-^>conn = new PDO(
echo                 "mysql:host=" . $this-^>host . ";dbname=" . $this-^>db_name,
echo                 $this-^>username,
echo                 $this-^>password,
echo                 array(
echo                     PDO::MYSQL_ATTR_INIT_COMMAND =^> "SET NAMES utf8",
echo                     PDO::ATTR_ERRMODE =^> PDO::ERRMODE_EXCEPTION,
echo                     PDO::ATTR_DEFAULT_FETCH_MODE =^> PDO::FETCH_ASSOC,
echo                     PDO::ATTR_EMULATE_PREPARES =^> false
echo                 ^)
echo             ^);
echo         } catch(PDOException $exception^) {
echo             error_log("Database connection error: " . $exception-^>getMessage(^)^);
echo             return null;
echo         }
echo         return $this-^>conn;
echo     }
echo }
echo ?^>
) > "deployment\siteground\config\database_production.php"

:: Test dosyasi olustur
echo [4/6] Test dosyasi olusturuluyor...
(
echo ^<?php
echo header("Content-Type: text/html; charset=utf-8"^);
echo echo "^<h1^>OtoAsist API Test^</h1^>";
echo require_once 'config/database.php';
echo try {
echo     $database = new Database(^);
echo     $db = $database-^>getConnection(^);
echo     if ($db^) {
echo         echo "^<p style='color: green;'^>✓ Veritabani baglantisi basarili^</p^>";
echo     } else {
echo         echo "^<p style='color: red;'^>❌ Veritabani baglantisi basarisiz^</p^>";
echo     }
echo } catch (Exception $e^) {
echo     echo "^<p style='color: red;'^>❌ Hata: " . $e-^>getMessage(^) . "^</p^>";
echo }
echo echo "^<h2^>API Endpoints^</h2^>";
echo echo "^<ul^>";
echo echo "^<li^>^<a href='api/v1/vehicles/'^>Vehicles API^</a^>^</li^>";
echo echo "^<li^>^<a href='api/v1/reminders/'^>Reminders API^</a^>^</li^>";
echo echo "^</ul^>";
echo ?^>
) > "deployment\siteground\test_api.php"

:: README dosyasi olustur
echo [5/6] README dosyasi olusturuluyor...
(
echo OtoAsist SiteGround Deployment
echo ===============================
echo.
echo KURULUM ADIMLARI:
echo 1. deployment\siteground klasorunu SiteGround'a yukle
echo 2. SiteGround'da veritabani olustur
echo 3. config\database.php dosyasini duzenle
echo 4. otoasist_complete_siteground.sql'i phpMyAdmin'e import et
echo 5. https://siteadresen.com/test_api.php ile test et
echo.
echo VERİTABANI BİLGİLERİ:
echo - Host: localhost
echo - Database: kullanici_adi_otoasist
echo - Username: SiteGround DB kullanicisi
echo - Password: SiteGround DB sifresi
echo.
echo FLUTTER CONFIG:
echo lib/core/constants/network_constants.dart:
echo static const String baseUrl = 'https://siteadresen.com';
echo.
echo DESTEK: admin@otoasist.com
) > "deployment\siteground\README.txt"

:: Dosya sayisini kontrol et
echo [6/6] Deployment tamamlaniyor...
for /f %%i in ('dir "deployment\siteground" /s /b ^| find /c /v ""') do set filecount=%%i

echo.
echo ===============================================
echo   ✅ DEPLOYMENT TAMAMLANDI!
echo ===============================================
echo.
echo 📂 Hazırlanan dosyalar: %filecount% adet
echo 📁 Konum: deployment\siteground\
echo.
echo 📋 SONRAKI ADIMLAR:
echo.
echo 1. SiteGround File Manager'a git
echo 2. public_html klasorune deployment\siteground icerigini yukle
echo 3. SiteGround'da MySQL veritabani olustur
echo 4. config\database.php'de veritabani bilgilerini guncelle
echo 5. phpMyAdmin'e otoasist_complete_siteground.sql'i import et
echo 6. https://siteadresen.com/test_api.php'yi test et
echo.
echo 🔧 Ornek veritabani bilgileri:
echo - Host: localhost
echo - Database: kullanici_otoasist
echo - Username: SiteGround kullanici adi
echo - Password: SiteGround sifresi
echo.
echo 📱 Flutter App'te network_constants.dart'i guncelle:
echo   baseUrl = 'https://siteadresen.com'
echo.
echo 📧 Destek icin: admin@otoasist.com
echo.
pause 
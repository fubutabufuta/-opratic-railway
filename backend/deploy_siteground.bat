@echo off
chcp 65001 >nul
title OtoAsist SiteGround Güvenli Deployment

echo.
echo ===============================================
echo   OtoAsist SiteGround Güvenli Deployment
echo ===============================================
echo.
echo ⚠️  ORJİNAL DOSYALAR KORUNACAK!
echo ✅ Sadece kopya dosyalar oluşturulacak
echo.

:: Deployment klasörü oluştur
if not exist "deployment" mkdir deployment
if not exist "deployment\siteground" mkdir deployment\siteground

:: Backend dosyalarını KOPYALA (orjinalleri bozma)
echo [1/5] 📁 Backend dosyaları KOPYALANIYOR...
xcopy "api" "deployment\siteground\api\" /E /I /Y >nul 2>&1
xcopy "config" "deployment\siteground\config\" /E /I /Y >nul 2>&1
if exist "router.php" copy "router.php" "deployment\siteground\" >nul 2>&1

:: .htaccess dosyası oluştur
echo [2/5] ⚙️ .htaccess dosyası oluşturuluyor...
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
echo # Güvenlik
echo ^<Files "*.sql"^>
echo     Order allow,deny
echo     Deny from all
echo ^</Files^>
) > "deployment\siteground\.htaccess"

:: Production config oluştur
echo [3/5] 🔧 Production config oluşturuluyor...
(
echo ^<?php
echo class Database {
echo     // SiteGround Production Settings
echo     private $host = "localhost";
echo     private $db_name = "SITEGROUND_DB_ADI"; // Buraya SiteGround DB adını yaz
echo     private $username = "SITEGROUND_KULLANICI"; // Buraya SiteGround kullanıcısını yaz
echo     private $password = "SITEGROUND_SIFRE"; // Buraya SiteGround şifresini yaz
echo     private $conn;
echo.
echo     public function getConnection(^) {
echo         $this-^>conn = null;
echo         try {
echo             $this-^>conn = new PDO(
echo                 "mysql:host=" . $this-^>host . ";dbname=" . $this-^>db_name,
echo                 $this-^>username, $this-^>password,
echo                 array(PDO::MYSQL_ATTR_INIT_COMMAND =^> "SET NAMES utf8"^)
echo             ^);
echo         } catch(PDOException $exception^) {
echo             error_log("DB Error: " . $exception-^>getMessage(^)^);
echo             return null;
echo         }
echo         return $this-^>conn;
echo     }
echo }
echo ?^>
) > "deployment\siteground\config\database_production.php"

:: Test dosyası oluştur
echo [4/5] 🧪 Test dosyası oluşturuluyor...
(
echo ^<?php
echo header("Content-Type: text/html; charset=utf-8"^);
echo echo "^<h1^>🚀 OtoAsist API Test^</h1^>";
echo echo "^<p^>Test Zamanı: " . date('Y-m-d H:i:s'^) . "^</p^>";
echo require_once 'config/database.php';
echo try {
echo     $db = (new Database(^)^)-^>getConnection(^);
echo     if ($db^) {
echo         echo "^<p style='color: green;'^>✅ Veritabanı: BAŞARILI^</p^>";
echo         $stmt = $db-^>query("SELECT COUNT(*^) as count FROM users"^);
echo         $result = $stmt-^>fetch(PDO::FETCH_ASSOC^);
echo         echo "^<p^>👥 Kullanıcı: " . $result['count'] . "^</p^>";
echo     } else {
echo         echo "^<p style='color: red;'^>❌ Veritabanı: BAŞARISIZ^</p^>";
echo     }
echo } catch (Exception $e^) {
echo     echo "^<p style='color: red;'^>❌ Hata: " . $e-^>getMessage(^) . "^</p^>";
echo }
echo echo "^<h2^>🔗 API Endpoints^</h2^>";
echo echo "^<ul^>^<li^>^<a href='api/v1/vehicles/'^>Vehicles^</a^>^</li^>";
echo echo "^<li^>^<a href='api/v1/reminders/'^>Reminders^</a^>^</li^>^</ul^>";
echo ?^>
) > "deployment\siteground\test_api.php"

:: Dosya sayısını kontrol et
echo [5/5] ✅ Deployment tamamlanıyor...
for /f %%i in ('dir "deployment\siteground" /s /b 2^>nul ^| find /c /v ""') do set /a filecount=%%i

echo.
echo ===============================================
echo   🎉 GÜVENLI DEPLOYMENT TAMAMLANDI!
echo ===============================================
echo.
echo 📊 İstatistikler:
echo   📁 Kopyalanan dosya: %filecount% adet
echo   📂 Deployment klasörü: deployment\siteground\
echo   🔒 Orjinal dosyalar: KORUNDU ✅
echo.
echo 📋 SITEGROUND'A AKTARMA ADIMları:
echo.
echo 1️⃣ SiteGround Site Tools'a git
echo 2️⃣ Files ^> File Manager'ı aç
echo 3️⃣ public_html klasörüne git
echo 4️⃣ deployment\siteground\ içindeki TÜM dosyaları sürükle-bırak
echo 5️⃣ SiteGround'da MySQL veritabanı oluştur
echo 6️⃣ config\database.php'de veritabanı bilgilerini güncelle
echo 7️⃣ otoasist_complete_siteground.sql'i phpMyAdmin'e import et
echo 8️⃣ https://siteadresen.com/test_api.php'yi test et
echo.
echo 🔧 Veritabanı Config Örneği:
echo   Host: localhost
echo   Database: siteground_kullanici_otoasist  
echo   Username: siteground_kullanici_adi
echo   Password: siteground_veritabani_sifresi
echo.
echo 📱 Flutter App Güncelleme:
echo   lib/core/constants/network_constants.dart:
echo   baseUrl = 'https://siteadresen.com'
echo.
echo 🆘 Problem olursa:
echo   - test_api.php'yi kontrol et
echo   - Error log'ları incele
echo   - Veritabanı bağlantısını doğrula
echo.
echo 📧 Destek: Proje klasöründeki README'yi kontrol et
echo.
pause 
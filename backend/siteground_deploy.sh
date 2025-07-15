#!/bin/bash

# OtoAsist SiteGround Deployment Script
# Bu script backend dosyalarını SiteGround'a hazırlar

echo "🚀 OtoAsist SiteGround Deployment Başlıyor..."

# Deployment klasörü oluştur
mkdir -p deployment/siteground

# Backend dosyalarını kopyala
echo "📁 Backend dosyaları kopyalanıyor..."
cp -r api/ deployment/siteground/
cp -r config/ deployment/siteground/
cp router.php deployment/siteground/

# .htaccess dosyası oluştur
echo "⚙️ .htaccess dosyası oluşturuluyor..."
cat > deployment/siteground/.htaccess << 'EOF'
RewriteEngine On

# CORS Headers
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"

# Handle preflight OPTIONS requests
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# API Routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ router.php [QSA,L]

# Default
DirectoryIndex index.php router.php

# Güvenlik
<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
EOF

# Production config oluştur
echo "🔧 Production config oluşturuluyor..."
cat > deployment/siteground/config/database_production.php << 'EOF'
<?php
class Database {
    // SiteGround Production Settings
    private $host = "localhost";
    private $db_name = "VERITABANI_ADI"; // Buraya SiteGround DB adını yaz
    private $username = "KULLANICI_ADI"; // Buraya SiteGround DB kullanıcısını yaz
    private $password = "SIFRE"; // Buraya SiteGround DB şifresini yaz
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $exception) {
            // Production'da hata loglama
            error_log("Database connection error: " . $exception->getMessage());
            return null;
        }
        
        return $this->conn;
    }
    
    public function testConnection() {
        $conn = $this->getConnection();
        if ($conn) {
            try {
                $stmt = $conn->query("SELECT 1");
                return true;
            } catch(PDOException $e) {
                return false;
            }
        }
        return false;
    }
}
?>
EOF

# Test dosyası oluştur
echo "🧪 Test dosyası oluşturuluyor..."
cat > deployment/siteground/test_api.php << 'EOF'
<?php
// OtoAsist API Test Dosyası
header("Content-Type: application/json");

echo "<h1>OtoAsist API Test</h1>";

// Database bağlantısını test et
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✓ Veritabanı bağlantısı başarılı</p>";
        
        // Kullanıcı sayısını kontrol et
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Kullanıcı sayısı: " . $result['count'] . "</p>";
        
        // Araç sayısını kontrol et
        $stmt = $db->query("SELECT COUNT(*) as count FROM vehicles");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Araç sayısı: " . $result['count'] . "</p>";
        
        // Hatırlatıcı sayısını kontrol et
        $stmt = $db->query("SELECT COUNT(*) as count FROM reminders");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Hatırlatıcı sayısı: " . $result['count'] . "</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Veritabanı bağlantısı başarısız</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>API Endpoints Test</h2>";
echo "<ul>";
echo "<li><a href='api/v1/vehicles/'>Vehicles API</a></li>";
echo "<li><a href='api/v1/reminders/'>Reminders API</a></li>";
echo "<li><a href='api/v1/auth/login.php'>Auth API</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>OtoAsist v1.0 - " . date('Y-m-d H:i:s') . "</small></p>";
?>
EOF

# ZIP dosyası oluştur
echo "📦 ZIP dosyası oluşturuluyor..."
cd deployment
zip -r otoasist-siteground.zip siteground/
cd ..

echo "✅ Deployment hazır!"
echo ""
echo "📋 Sonraki Adımlar:"
echo "1. deployment/otoasist-siteground.zip dosyasını SiteGround'a yükle"
echo "2. SiteGround'da veritabanı oluştur"
echo "3. config/database.php dosyasını düzenle"
echo "4. SQL dosyasını phpMyAdmin'e import et"
echo "5. https://siteadresen.com/test_api.php ile test et"
echo ""
echo "🔗 Gerekli dosyalar:"
echo "- deployment/otoasist-siteground.zip (Backend dosyaları)"
echo "- otoasist_complete_siteground.sql (Veritabanı)"
echo ""
echo "📧 Destek: admin@otoasist.com" 
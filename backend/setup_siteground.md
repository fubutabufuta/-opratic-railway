# SiteGround GrowBig Hosting ile OtoAsist Backend Kurulumu

## 1. SiteGround Hazırlık
- SiteGround Site Tools'a giriş yap
- Domain'ini veya subdomain oluştur (örn: api.siteadresen.com)

## 2. Dosya Yöneticisi ile Upload
1. **Site Tools > Files > File Manager**
2. `public_html` klasörüne git (veya subdomain klasörüne)
3. Backend dosyalarını yükle:
   ```
   public_html/
   ├── api/
   │   └── v1/
   │       ├── auth/
   │       ├── vehicles/
   │       └── reminders/
   ├── config/
   └── router.php
   ```

## 3. MySQL Veritabanı Oluşturma
1. **Site Tools > Databases > MySQL**
2. Yeni veritabanı oluştur: `otoasist_db`
3. Kullanıcı oluştur ve yetki ver
4. **phpMyAdmin** ile veritabanına giriş yap
5. `database/otoasist_mysql_import.sql` dosyasını import et

## 4. Database Config Güncelle
`config/database.php` dosyasını SiteGround bilgileriyle güncelle:
```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "otoasist_db";
    private $username = "siteground_username";
    private $password = "siteground_password";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
```

## 5. .htaccess Dosyası Oluştur
`public_html/.htaccess` dosyası oluştur:
```apache
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
DirectoryIndex index.php index.html
```

## 6. Router.php Ana Dosya
`public_html/router.php`:
```php
<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove base path if needed
$path = str_replace('/api/', '', $path);

// Route to appropriate endpoint
if (strpos($path, 'v1/vehicles') === 0) {
    include 'api/v1/vehicles/index.php';
} elseif (strpos($path, 'v1/reminders') === 0) {
    include 'api/v1/reminders/index.php';
} elseif (strpos($path, 'v1/auth') === 0) {
    if (strpos($path, 'login') !== false) {
        include 'api/v1/auth/login.php';
    } elseif (strpos($path, 'register') !== false) {
        include 'api/v1/auth/register.php';
    } elseif (strpos($path, 'verify') !== false) {
        include 'api/v1/auth/verify.php';
    }
} else {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found"]);
}
?>
```

## 7. PHP Version Ayarı
1. **Site Tools > Dev > PHP Manager**
2. PHP 8.1 veya 8.2 seç
3. Extensions kontrol et:
   - PDO
   - PDO_MySQL
   - JSON
   - mbstring

## 8. Flutter Config Güncelle
`lib/core/constants/network_constants.dart`:
```dart
class NetworkConstants {
  // Production
  static const String baseUrl = 'https://api.siteadresen.com';
  
  // Development
  // static const String baseUrl = 'http://localhost:8000';
}
```

## 9. Test Etme
1. **Browser'da test et:**
   - `https://api.siteadresen.com/api/v1/vehicles/`
   - Veritabanı bağlantısını kontrol et

2. **Flutter'dan test et:**
   - Network constants'ı güncelle
   - `flutter run -d chrome`

## 10. SSL Sertifikası
SiteGround otomatik Let's Encrypt SSL sağlar:
- **Site Tools > Security > SSL Manager**
- SSL'i aktifleştir

## 11. Error Logs
Hata durumunda:
- **Site Tools > Statistics > Error Logs**
- PHP error log'larını kontrol et

## Avantajları
✅ 7/24 uptime
✅ Otomatik backup
✅ SSL sertifikası
✅ CDN dahil
✅ Yönetim paneli
✅ Güvenlik koruması

## Maliyetler
- GrowBig planı zaten mevcut
- Ek maliyet yok
- Domain/subdomain kullanabilirsin 
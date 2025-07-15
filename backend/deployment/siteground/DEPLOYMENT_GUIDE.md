# 🚀 OtoAsist SiteGround Deployment Rehberi

## ✅ Hazırlanan Dosyalar
Bu klasörde bulunan dosyalar **orjinal dosyalardan kopyalanmıştır** ve güvenli bir şekilde SiteGround'a yüklenebilir.

### 📁 Dosya Yapısı
```
siteground/
├── api/                    # Backend API dosyaları
├── config/                 # Veritabanı konfigürasyonu
├── .htaccess              # Apache konfigürasyonu
├── router.php             # API yönlendirici
├── test_api.php           # Test dosyası
├── otoasist_siteground.sql # Veritabanı SQL dosyası
└── README.txt             # Kısa bilgi
```

## 📋 SiteGround'a Yükleme Adımları

### 1️⃣ SiteGround'a Dosya Yükleme
1. **SiteGround Site Tools**'a giriş yap
2. **Files > File Manager**'a git
3. **public_html** klasörüne git
4. Bu klasördeki **TÜM dosyaları** sürükle-bırak ile yükle

### 2️⃣ MySQL Veritabanı Oluşturma
1. **Site Tools > Databases > MySQL**'e git
2. **Create Database** butonuna tıkla
3. Veritabanı adı: `kullanici_otoasist` (örnek)
4. **Create** butonuna tıkla
5. **Database User** oluştur ve **All Privileges** ver

### 3️⃣ SQL İmport Etme
1. **Site Tools > Databases > phpMyAdmin**'e git
2. Oluşturduğun veritabanını seç
3. **Import** sekmesine git
4. **Choose File** > `otoasist_siteground.sql` seç
5. **Go** butonuna tıkla

### 4️⃣ Database Config Güncelleme
`config/database.php` dosyasını düzenle:

```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "SITEGROUND_DB_ADI";      // Adım 2'de oluşturduğun DB adı
    private $username = "SITEGROUND_KULLANICI";   // SiteGround DB kullanıcısı
    private $password = "SITEGROUND_SIFRE";       // SiteGround DB şifresi
    private $conn;
    // ... rest of code
}
?>
```

### 5️⃣ Test Etme
1. **https://siteadresen.com/test_api.php** adresini aç
2. ✅ Veritabanı bağlantısı başarılı olmalı
3. API endpoint'lerini test et:
   - `https://siteadresen.com/api/v1/vehicles/`
   - `https://siteadresen.com/api/v1/reminders/`

### 6️⃣ Flutter App Güncelleme
`lib/core/constants/network_constants.dart` dosyasını güncelle:

```dart
class NetworkConstants {
  // Production
  static const String baseUrl = 'https://siteadresen.com';
  
  // Development (yorum satırı yap)
  // static const String baseUrl = 'http://localhost:8000';
}
```

## 🔧 Veritabanı Bilgileri Örneği

```
Host: localhost
Database: kullanici_otoasist
Username: kullanici_db_user
Password: guvenli_sifre_123
Port: 3306 (varsayılan)
```

## 🆘 Problem Çözme

### ❌ Veritabanı Bağlantı Hatası
- Database bilgilerini kontrol et
- SiteGround'da user'ın database'e erişim yetkisi var mı?
- Database adını doğru yazdın mı?

### ❌ 500 Internal Server Error
- Error log'ları kontrol et: **Site Tools > Statistics > Error Logs**
- PHP version 8.1+ olmalı: **Site Tools > Dev > PHP Manager**

### ❌ CORS Hatası
- `.htaccess` dosyasının yüklendiğini kontrol et
- Apache mod_rewrite aktif olmalı

### ❌ API 404 Hatası
- `router.php` dosyasının root'ta olduğunu kontrol et
- `.htaccess` rewrite kuralları çalışıyor mu?

## 📱 Flutter App Test

### Local'den Production'a Geçiş
1. `network_constants.dart`'ı güncelle
2. `flutter clean && flutter pub get`
3. `flutter run -d chrome` ile test et

### Test Kullanıcısı
- **Phone**: +905551234567
- **Password**: test123 (hash'li olarak saklanıyor)

## 🔒 Güvenlik Notları

- ✅ Orjinal dosyalar korundu
- ✅ SQL injection koruması aktif
- ✅ CORS headers ayarlandı
- ✅ Güvenlik headers eklendi
- ✅ .sql dosyaları web'den erişilemez

## 📧 Destek

Problem yaşarsan:
1. `test_api.php` sonucunu kontrol et
2. SiteGround error log'larını incele
3. API endpoint'leri tek tek test et

---

### 🎉 Başarılı Deployment Sonrası
✅ Backend: `https://siteadresen.com/api/v1/`  
✅ Test: `https://siteadresen.com/test_api.php`  
✅ Flutter App: Production mode'da çalışıyor

**Not**: Bu deployment **orjinal dosyaları etkilemez**. İstersen local'de devam edebilirsin. 
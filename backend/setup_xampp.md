# XAMPP ile OtoAsist Backend Kurulumu

## 1. XAMPP Kurulumu
1. [XAMPP'i indir](https://www.apachefriends.org/download.html)
2. Kur ve çalıştır
3. Apache ve MySQL'i başlat

## 2. Backend Dosyalarını Kopyala
```bash
# Backend klasörünü XAMPP'e kopyala
cp -r backend/ C:/xampp/htdocs/otoasist/
```

## 3. Veritabanı Ayarları
1. http://localhost/phpmyadmin aç
2. `otoasist` veritabanını oluştur
3. `database/otoasist_mysql_import.sql` dosyasını import et

## 4. Config Dosyasını Güncelle
`backend/config/database.php` dosyasında:
```php
private $host = "localhost";
private $db_name = "otoasist";
private $username = "root";
private $password = "";
```

## 5. Test Et
- Backend: http://localhost/otoasist/api/v1/vehicles/
- PhpMyAdmin: http://localhost/phpmyadmin/

## 6. Flutter Config Güncelle
`lib/core/constants/network_constants.dart`:
```dart
static const String baseUrl = 'http://localhost/otoasist';
``` 
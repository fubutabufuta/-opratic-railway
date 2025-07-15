# SiteGround'a Deployment Rehberi

## 1. Hazırlık Adımları

### A) Backend Dosyalarını Hazırla
```bash
# Backend klasörünü kopyala
mkdir deployment
cp -r backend/ deployment/otoasist-backend/

# Gereksiz dosyaları temizle
cd deployment/otoasist-backend/
rm *.bat
rm *test*.php
rm -rf database/
```

### B) Config Dosyalarını Güncelle
1. `config/database.php` dosyasını SiteGround bilgileriyle güncelle
2. Production için environment ayarla

## 2. SiteGround Upload Yöntemleri

### A) File Manager (Web Arayüzü)
1. SiteGround Site Tools > Files > File Manager
2. `public_html` klasörüne git
3. Backend dosyalarını yükle
4. Klasör yapısını koru:
   ```
   public_html/
   ├── api/
   ├── config/
   ├── router.php
   └── .htaccess
   ```

### B) FTP/SFTP (Önerilen)
**FileZilla ile:**
- Host: `siteadresen.com`
- Username: SiteGround FTP kullanıcı adı
- Password: SiteGround FTP şifresi
- Port: 21 (FTP) veya 22 (SFTP)

**WinSCP ile (Windows):**
- Same credentials as FileZilla

### C) Git Deployment (Gelişmiş)
```bash
# SiteGround'da Git repository oluştur
git init
git add .
git commit -m "Initial deployment"
git remote add origin https://git.siteground.com/repository
git push origin main
```

## 3. Veritabanı Setup

### A) SiteGround MySQL
1. Site Tools > Databases > MySQL
2. Create Database: `username_otoasist`
3. Create User ve permissions ver
4. Connection string'i kaydet

### B) phpMyAdmin Import
1. Site Tools > Databases > phpMyAdmin
2. Select database
3. Import > Choose file
4. Upload `otoasist_mysql_import.sql`

## 4. Test ve Debug

### A) API Test
```bash
# Browser'da test et
https://siteadresen.com/api/v1/vehicles/

# cURL ile test
curl -X GET https://siteadresen.com/api/v1/vehicles/
```

### B) Error Logs
- Site Tools > Statistics > Error Logs
- PHP error log kontrol et

## 5. Flutter App Güncelleme

### A) Network Constants
```dart
// lib/core/constants/network_constants.dart
class NetworkConstants {
  static const String baseUrl = 'https://siteadresen.com';
}
```

### B) Build ve Test
```bash
flutter clean
flutter pub get
flutter run -d chrome
```

## 6. Production Checklist

### ✅ Güvenlik
- [ ] Database credentials güvenli
- [ ] Error reporting kapalı
- [ ] SSL aktif
- [ ] CORS ayarları doğru

### ✅ Performance
- [ ] PHP OPcache aktif
- [ ] Gzip compression aktif
- [ ] CDN aktif (SiteGround)
- [ ] Database indexes

### ✅ Monitoring
- [ ] Error logs aktif
- [ ] Uptime monitoring
- [ ] Database backup
- [ ] File backup

## 7. Backup Stratejisi

### A) SiteGround Backup
- Site Tools > Security > Backup
- Daily automatic backup aktif

### B) Manual Backup
```bash
# Database backup
mysqldump -u username -p database_name > backup.sql

# Files backup
tar -czf backend-backup.tar.gz api/ config/
```

## 8. Domain/Subdomain Ayarları

### A) Ana Domain
- `siteadresen.com/api/v1/`

### B) Subdomain (Önerilen)
- `api.siteadresen.com/v1/`
- Site Tools > Domains > Subdomains
- Create: `api`

## 9. SSL Sertifikası
- Site Tools > Security > SSL Manager
- Let's Encrypt Free SSL
- Force HTTPS aktif

## 10. Troubleshooting

### A) Common Issues
- **500 Error**: PHP version/extensions
- **Database Error**: Connection credentials
- **CORS Error**: .htaccess headers
- **404 Error**: Router.php missing

### B) Debug Steps
1. Check error logs
2. Test database connection
3. Verify file permissions
4. Check PHP version (8.1+)

## Quick Commands

```bash
# Upload files
scp -r backend/ username@siteadresen.com:/home/username/public_html/

# Test API
curl https://siteadresen.com/api/v1/vehicles/

# Check logs
tail -f /path/to/error.log
``` 
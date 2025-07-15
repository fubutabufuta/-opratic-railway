# WAMP ile OtoAsist Backend Kurulumu

## 1. WAMP Kurulumu
1. [WAMP'i indir](https://www.wampserver.com/en/)
2. Kur ve çalıştır
3. Sol alttaki simge yeşil olmalı

## 2. Backend Dosyalarını Kopyala
```bash
# Backend klasörünü WAMP'e kopyala
copy backend\ C:\wamp64\www\otoasist\
```

## 3. Veritabanı Ayarları
1. http://localhost/phpmyadmin aç
2. `otoasist` veritabanını oluştur
3. SQL dosyasını import et

## 4. Test Et
- Backend: http://localhost/otoasist/api/v1/vehicles/
- PhpMyAdmin: http://localhost/phpmyadmin/

## 5. Flutter Config
Network constants'ı güncelle:
```dart
static const String baseUrl = 'http://localhost/otoasist';
``` 
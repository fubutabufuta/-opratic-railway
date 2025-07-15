# Laragon ile OtoAsist Backend Kurulumu

## 1. Laragon Kurulumu
1. [Laragon'u indir](https://laragon.org/download/)
2. Full version'ı kur (PHP + MySQL dahil)
3. Başlat

## 2. Backend Dosyalarını Kopyala
```bash
# Backend klasörünü Laragon'a kopyala
copy backend\ C:\laragon\www\otoasist\
```

## 3. Otomatik Pretty URL
Laragon otomatik olarak otoasist.test domain'i oluşturur.

## 4. Veritabanı
1. Laragon'da MySQL'i başlat
2. HeidiSQL'i aç (Laragon ile gelir)
3. Veritabanını import et

## 5. Test Et
- Backend: http://otoasist.test/api/v1/vehicles/
- HeidiSQL ile veritabanı yönetimi

## 6. Flutter Config
```dart
static const String baseUrl = 'http://otoasist.test';
```

## Avantajları
- Çok hızlı kurulum
- Otomatik pretty URL'ler
- Modern arayüz
- Minimum konfigürasyon 
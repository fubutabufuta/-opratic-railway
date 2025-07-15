# Oto Asist Bildirim Sistemi Kurulum Rehberi

## 📋 Genel Bakış

Oto Asist bildirim sistemi, kullanıcılara çeşitli bildirimler göndermeyi sağlar:
- Hatırlatmalar (Sigorta, muayene, servis)
- Kampanya bildirimleri
- Güvenlik uyarıları
- Özel içerik bildirimleri

## 🗄️ Veritabanı Kurulumu

### 1. Tabloları Oluşturun
```bash
cd backend
php setup_notifications.php
```

Bu komut aşağıdaki tabloları oluşturacaktır:
- `notifications` - Ana bildirim tablosu
- `user_notifications` - Kullanıcı bazlı okunma durumu
- `notification_settings` - Kullanıcı bildirim tercihleri

## 🔔 Admin Paneli

### Bildirim Gönderme
1. Tarayıcınızda `http://localhost:8000/admin_notifications.php` adresini açın
2. Bildirim başlığı ve mesajını girin
3. Bildirim türünü seçin:
   - Genel Bildirim
   - Hatırlatma
   - Kampanya
   - Uyarı
4. Hedef kitleyi belirleyin:
   - Tüm Kullanıcılar
   - Şehir Bazlı (örn: İstanbul)
   - Araç Markası Bazlı (örn: Toyota)
   - Belirli Kullanıcı (User ID)

## 📱 Mobil Uygulama Entegrasyonu

### 1. Bildirim Zili
Ana sayfanın sağ üst köşesinde bildirim zili bulunur:
- Okunmamış bildirim sayısı gösterir
- Tıklandığında bildirim listesine yönlendirir

### 2. Bildirim Listesi
- Tüm bildirimleri kronolojik sırada gösterir
- Okunmamış bildirimler koyu renkte görünür
- Bildirime tıklandığında detay sayfası açılır

### 3. Bildirim Ayarları
Kullanıcılar şu bildirimleri açıp kapatabilir:
- Hatırlatmalar
- Kampanyalar
- Servis Uyarıları
- Kişiye Özel Öneriler
- Push Bildirimleri
- E-posta Bildirimleri

## 🔥 Firebase Cloud Messaging (FCM) Kurulumu

### 1. Firebase Projesi Oluşturun
1. [Firebase Console](https://console.firebase.google.com/) üzerinden yeni proje oluşturun
2. Android ve iOS uygulamalarınızı ekleyin
3. `google-services.json` (Android) ve `GoogleService-Info.plist` (iOS) dosyalarını indirin

### 2. Flutter Uygulamasında FCM
```dart
// pubspec.yaml dosyasına ekleyin:
dependencies:
  firebase_core: ^2.24.0
  firebase_messaging: ^14.7.0
  flutter_local_notifications: ^16.2.0
```

### 3. Firebase Service'i Etkinleştirin
`lib/core/firebase/firebase_service.dart` dosyasında:
- Firebase import satırlarını uncomment yapın
- `_setupFCM()` metodunu uncomment yapın
- `_handleForegroundMessage()` metodunu uncomment yapın

### 4. Backend FCM Entegrasyonu
Backend'de FCM gönderimi için:
1. Firebase Admin SDK'yı kurun
2. Service account key'i indirin
3. `backend/fcm/send_notification.php` dosyası oluşturun

## 🧪 Test Etme

### 1. Demo Bildirimleri
Veritabanına demo bildirimleri otomatik olarak eklenir:
- "Hoş Geldiniz!" - Genel bildirim
- "Muayene Hatırlatması" - Hatırlatma bildirimi
- "Özel Kampanya" - Kampanya bildirimi

### 2. API Test
```bash
# Bildirim listesini getir
curl http://localhost:8000/api/v1/notifications?user_id=1

# Yeni bildirim gönder (Admin)
curl -X POST http://localhost:8000/api/v1/notifications \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Bildirimi",
    "message": "Bu bir test bildirimidir",
    "target_type": "all",
    "notification_type": "general"
  }'

# Bildirimi okundu olarak işaretle
curl -X PUT http://localhost:8000/api/v1/notifications \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "user_id": 1
  }'
```

## 📊 Gelecek Özellikler

### 1. Otomatik Hatırlatmalar
- Cron job ile periyodik kontroller
- Muayene/sigorta bitiş tarihlerini kontrol
- Otomatik bildirim gönderimi

### 2. Kampanya Entegrasyonu
- B2B müşteriler için kampanya bildirimi API'si
- Hedefli bildirim paketleri
- Bildirim performans raporları

### 3. Konum Bazlı Bildirimler
- GPS konumuna göre bildirimler
- Yakındaki servis/kampanya bildirimleri

## 🔒 Güvenlik Notları

1. **API Güvenliği**
   - JWT token kontrolü ekleyin
   - Rate limiting uygulayın
   - Admin endpoints için ayrı auth

2. **KVKK Uyumu**
   - Kullanıcı onayı alın
   - Bildirim tercihlerini saklayın
   - Opt-out seçeneği sağlayın

3. **Veri Güvenliği**
   - FCM token'ları güvenli saklayın
   - Hassas bilgileri bildirimlerde paylaşmayın

## 🆘 Sorun Giderme

### Bildirimler görünmüyor
1. Veritabanı tablolarının oluşturulduğunu kontrol edin
2. API endpoint'lerinin çalıştığını test edin
3. Console'da hata mesajlarını kontrol edin

### FCM çalışmıyor
1. Firebase yapılandırma dosyalarını kontrol edin
2. FCM token'ın backend'e gönderildiğini doğrulayın
3. Firebase Console'dan test bildirimi gönderin

### Okunmamış sayısı güncellenmiyor
1. `user_notifications` tablosunu kontrol edin
2. API response'larını inceleyin
3. Frontend'de state güncellemesini kontrol edin 
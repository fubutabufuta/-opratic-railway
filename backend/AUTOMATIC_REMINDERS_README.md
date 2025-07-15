# Oto Asist Otomatik Hatırlatma Sistemi

Bu sistem, kullanıcıların araç yenilemelerini (muayene, sigorta, servis vb.) otomatik olarak takip eder ve belirtilen süre öncesinde hatırlatma oluşturur.

## 🎯 Özellikler

- **Otomatik Hatırlatma Ekleme**: 90 gün içinde bitecek yenilemeler için otomatik hatırlatma oluşturur
- **Kullanıcı Ayarları**: Her kullanıcının varsayılan hatırlatma gün sayısı ve saati
- **Bildirim Sistemi**: Yaklaşan yenilemeler için bildirim gönderir
- **Çoklu Hatırlatma Türü**: Muayene, sigorta, kasko, servis destekli
- **Tekrar Kontrol**: Aynı hatırlatma birden fazla eklenmez

## 📋 Sistem Gereksinimleri

- PHP 7.4+
- MySQL 5.7+
- Cron job desteği (Linux/Unix sistemler)

## 🚀 Kurulum

### 1. Veritabanı Şeması Güncelleme

```bash
# MySQL'e bağlan ve şu SQL'i çalıştır:
mysql -u root -p otoasist < backend/add_auto_reminder_fields.sql
```

### 2. User Settings API'si

Kullanıcı ayarları için yeni API endpoint'i:
- `GET /api/v1/user-settings/?user_id=X` - Ayarları getir
- `POST /api/v1/user-settings/` - Ayarları güncelle

### 3. Cron Job Kurulumu

```bash
# Otomatik kurulum
cd backend
chmod +x setup_cron.sh
sudo ./setup_cron.sh

# Manuel kurulum
crontab -e
# Şu satırı ekle:
0 9 * * * /usr/bin/php /path/to/backend/cron/check_reminders.php >> /path/to/backend/logs/cron.log 2>&1
```

## ⚙️ Yapılandırma

### Varsayılan Ayarlar

```php
$defaultDays = 7;        // 7 gün önceden hatırlatma
$defaultTime = '09:00:00'; // Saat 09:00'da
```

### Kullanıcı Ayarları Tablosu

```sql
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    default_reminder_days INT DEFAULT 7,
    default_reminder_time TIME DEFAULT '09:00:00',
    notifications_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🔄 Çalışma Mantığı

1. **Her gün saat 09:00'da** cron job çalışır
2. **90 gün içinde bitecek** tüm yenilemeleri bulur
3. **Kullanıcının ayarlarına** göre hatırlatma tarihi hesaplar
4. **Zaten hatırlatma yoksa** yeni hatırlatma oluşturur
5. **Yaklaşan hatırlatmalar** için bildirim gönderir

### Hatırlatma Türleri

- `inspection` → Muayene
- `insurance` → Trafik Sigortası  
- `kasko` → Kasko
- `service` → Servis
- `tire` → Lastik
- `oil_change` → Yağ Değişimi

## 📊 Monitoring

### Log Dosyaları

```bash
# Cron job logları
tail -f backend/logs/cron.log

# Manuel test
php backend/cron/check_reminders.php
```

### Çıktı Örnekleri

```
2025-01-08 09:00:00 - Hatırlatma kontrolü başladı
Otomatik hatırlatmalar ekleniyor...
✓ Otomatik hatırlatma eklendi: 06ABC123 - Muayene (01/01/2025 09:00)
✓ Otomatik hatırlatma eklendi: 34DEF456 - Sigorta (15/01/2025 09:00)
⏭️ Zaten hatırlatma var: 35GHI789 - Servis
Muayene hatırlatmaları kontrol ediliyor...
✓ Muayene hatırlatması gönderildi: 06ABC123
2025-01-08 09:00:01 - Hatırlatma kontrolü tamamlandı
```

## 🛠️ Troubleshooting

### Yaygın Sorunlar

**1. Cron job çalışmıyor**
```bash
# Cron servisini kontrol et
sudo systemctl status cron

# Cron job'ları listele
crontab -l

# Log dosyasını kontrol et
tail -f /var/log/cron
```

**2. PHP hatası**
```bash
# PHP path'ini kontrol et
which php

# Manuel test
php -v
php backend/cron/check_reminders.php
```

**3. Veritabanı bağlantı hatası**
```bash
# Database config'i kontrol et
cat backend/config/database.php

# MySQL bağlantısını test et
mysql -u username -p database_name
```

## 🔧 Geliştirme

### Yeni Hatırlatma Türü Ekleme

1. `getReminderType()` fonksiyonuna ekle
2. Veritabanında `reminder_type` enum'ına ekle
3. Frontend'de dropdown'a ekle

### Özelleştirme

```php
// Farklı türler için farklı süreler
switch ($renewal['reminder_type']) {
    case 'inspection':
        $defaultDays = 30; // Muayene için 30 gün
        break;
    case 'insurance':
        $defaultDays = 15; // Sigorta için 15 gün
        break;
    default:
        $defaultDays = 7;  // Diğerleri için 7 gün
}
```

## 📞 Destek

Herhangi bir sorun yaşarsanız:

1. Log dosyalarını kontrol edin
2. Manuel test çalıştırın
3. Veritabanı şemasını kontrol edin
4. Cron job ayarlarını doğrulayın

---

**Son Güncelleme**: 8 Ocak 2025
**Versiyon**: 1.0.0 
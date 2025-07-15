# OtoAsist API - Railway Deployment

Bu klasör Railway.app'a deploy edilmek üzere hazırlanmıştır.

## 🚀 Hızlı Deploy

### 1. GitHub'a Push Et
```bash
# Bu klasörü ayrı bir repo olarak kullan
cd railway-deployment
git init
git add .
git commit -m "Initial Railway deployment"
git remote add origin https://github.com/yourusername/otoasist-railway.git
git push -u origin main
```

### 2. Railway'da Deploy Et
```bash
1. railway.app'a git
2. "New Project" → "Deploy from GitHub repo"
3. otoasist-railway repo'sunu seç
4. "Deploy Now" tıkla
```

### 3. MySQL Database Ekle
```bash
1. Project dashboard → "Add Service"
2. "Database" → "Add MySQL"
3. Database otomatik oluşur ve environment variables set edilir
```

### 4. Database'i Import Et
```bash
1. TablePlus/phpMyAdmin kullan
2. Railway MySQL bilgilerini al
3. otoasist_clean_no_fk.sql dosyasını import et
```

## 📡 API Endpoints

### Health Check
```
GET https://your-app.railway.app/health
```

### Database Test
```
GET https://your-app.railway.app/backend/api/test_railway.php
```

### Main API
```
GET https://your-app.railway.app/backend/api/v1/
```

## 🔧 Environment Variables

Railway otomatik olarak şu variables'ları set eder:
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `DATABASE_URL`

## 🧪 Test

Deploy sonrası bu endpoints'i test et:
1. `https://your-app.railway.app/health` - API sağlık kontrolü
2. `https://your-app.railway.app/backend/api/test_railway.php` - Database bağlantısı
3. `https://your-app.railway.app/backend/api/v1/campaigns/` - Kampanyalar
4. `https://your-app.railway.app/backend/api/v1/vehicles/` - Araçlar

## 📱 Flutter Integration

Flutter app'te bu URL'i kullan:
```dart
// lib/core/constants/network_constants.dart
class NetworkConstants {
  static const String baseUrl = 'https://your-app.railway.app/backend/api';
}
```

## 📝 Önemli Notlar

- ✅ Orijinal dosyalar değişmedi
- ✅ Railway'a özel konfigürasyon
- ✅ Database connection otomatik
- ✅ CORS headers eklendi
- ✅ Error handling geliştirildi
- ✅ Environment variables desteği

## 🔍 Sorun Giderme

### Database Connection Error
```bash
1. Railway'da MySQL service running olduğunu kontrol et
2. Environment variables set olduğunu kontrol et
3. /backend/api/test_railway.php endpoint'ini ziyaret et
```

### API Not Found
```bash
1. /health endpoint'ini test et
2. Railway logs'larını kontrol et
3. Deployment successful olduğunu kontrol et
```

## 📞 Destek

Herhangi bir sorun durumunda:
1. Railway logs'larını kontrol et
2. /health endpoint'ini test et
3. Database connection'ı test et 
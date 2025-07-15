# Oto Asist B2B API Dokümantasyonu

## 🔑 API Kimlik Doğrulama

Tüm API istekleri `X-API-Key` header'ı ile kimlik doğrulaması gerektirir.

```bash
X-API-Key: b2b_your_api_key_here
```

## 📡 API Endpoint'leri

### 1. Kampanya Bildirimi Gönder

**Endpoint:** `POST /api/v1/b2b/campaign-notifications`

**Headers:**
```json
{
    "Content-Type": "application/json",
    "X-API-Key": "b2b_your_api_key_here"
}
```

**Request Body:**
```json
{
    "title": "Özel Kampanya",
    "message": "Bugüne özel %20 indirim fırsatı!",
    "target_type": "all", // "all", "city", "vehicle_brand"
    "target_value": null, // Örn: "İstanbul" veya "Toyota"
    "campaign_id": "12345", // Opsiyonel: Sizin kampanya ID'niz
    "image_url": "https://example.com/campaign.jpg", // Opsiyonel
    "action_url": "https://example.com/campaign" // Opsiyonel
}
```

**Response:**
```json
{
    "success": true,
    "message": "Kampanya bildirimi gönderildi",
    "notification_id": 123,
    "sent_count": 450, // Bildirimi kabul eden kullanıcı sayısı
    "target_count": 500 // Toplam hedef kullanıcı sayısı
}
```

### 2. Hedefleme Seçenekleri

#### a) Tüm Kullanıcılar
```json
{
    "target_type": "all",
    "target_value": null
}
```

#### b) Şehir Bazlı
```json
{
    "target_type": "city",
    "target_value": "İstanbul"
}
```

#### c) Araç Markası Bazlı
```json
{
    "target_type": "vehicle_brand",
    "target_value": "Toyota"
}
```

## 📊 Kota ve Limitler

- Her B2B müşterinin aylık bildirim kotası vardır
- Kota aşımında `HTTP 429` hatası döner
- Kota her ayın başında sıfırlanır

## 🚨 Hata Kodları

| Kod | Açıklama |
|-----|----------|
| 401 | Geçersiz API anahtarı |
| 403 | Yetkisiz hedefleme türü |
| 429 | Aylık kota aşıldı |
| 400 | Geçersiz istek parametreleri |
| 500 | Sunucu hatası |

## 💡 Örnek Kullanımlar

### PHP Örneği
```php
<?php
$apiKey = 'b2b_your_api_key_here';
$url = 'https://otoasist.com/api/v1/b2b/campaign-notifications';

$data = [
    'title' => 'Kış Kampanyası',
    'message' => 'Kış lastiği değişiminde %25 indirim!',
    'target_type' => 'city',
    'target_value' => 'Ankara',
    'image_url' => 'https://example.com/winter-campaign.jpg'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
```

### Python Örneği
```python
import requests
import json

api_key = 'b2b_your_api_key_here'
url = 'https://otoasist.com/api/v1/b2b/campaign-notifications'

headers = {
    'Content-Type': 'application/json',
    'X-API-Key': api_key
}

data = {
    'title': 'Sigorta Yenileme Kampanyası',
    'message': 'Kasko sigortanızı yenileyin, %15 indirim kazanın!',
    'target_type': 'all',
    'campaign_id': 'INS2024001',
    'action_url': 'https://sigortam.com/kampanya'
}

response = requests.post(url, headers=headers, json=data)
result = response.json()
print(result)
```

### JavaScript (Node.js) Örneği
```javascript
const axios = require('axios');

const apiKey = 'b2b_your_api_key_here';
const url = 'https://otoasist.com/api/v1/b2b/campaign-notifications';

const data = {
    title: 'Servis Kampanyası',
    message: 'Periyodik bakımda motor yağı hediye!',
    target_type: 'vehicle_brand',
    target_value: 'Mercedes',
    image_url: 'https://example.com/service-campaign.jpg'
};

axios.post(url, data, {
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey
    }
})
.then(response => {
    console.log(response.data);
})
.catch(error => {
    console.error('Error:', error.response.data);
});
```

## 📋 Bildirim Türleri ve Kullanım Senaryoları

### 1. Genel Kampanya Bildirimi
Tüm kullanıcılara özel fırsatlar
```json
{
    "title": "Black Friday İndirimleri",
    "message": "Tüm hizmetlerde %30 indirim!",
    "target_type": "all"
}
```

### 2. Bölgesel Kampanya
Belirli şehirdeki kullanıcılara
```json
{
    "title": "İstanbul'a Özel",
    "message": "Ücretsiz araç kontrolü fırsatı",
    "target_type": "city",
    "target_value": "İstanbul"
}
```

### 3. Marka Bazlı Kampanya
Belirli araç markası sahiplerine
```json
{
    "title": "BMW Sahiplerine Özel",
    "message": "Orijinal yedek parçada %20 indirim",
    "target_type": "vehicle_brand",
    "target_value": "BMW"
}
```

## 🔒 Güvenlik Notları

1. API anahtarınızı güvenli saklayın
2. HTTPS kullanın
3. API anahtarınızı client-side kodda kullanmayın
4. Rate limiting'e dikkat edin
5. Webhook URL'lerini doğrulayın

## 📞 Destek

Teknik destek için: api-support@otoasist.com
API anahtarı talebi için admin panelinizi kullanın. 
<?php
// Tüm API endpoint'lerini test et
function testAPI($endpoint, $description = '') {
    $url = "http://localhost:8001/api/v1/" . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "=== $description ===\n";
    echo "Endpoint: $endpoint\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data) {
            if (isset($data['data']) && is_array($data['data'])) {
                echo "✓ Başarılı - " . count($data['data']) . " kayıt bulundu\n";
            } else {
                echo "✓ Başarılı\n";
            }
        } else {
            echo "⚠ Yanıt JSON değil\n";
        }
    } else {
        echo "✗ Hata: $httpCode\n";
        echo "Response: $response\n";
    }
    echo "\n";
}

echo "🔍 API Endpoint Testleri Başlıyor...\n\n";

// Test endpoints
testAPI('sliders/', 'Ana Sayfa Sliderları');
testAPI('vehicles/', 'Araç Listesi');
testAPI('reminders/', 'Hatırlatmalar');
testAPI('news/', 'Haberler');
testAPI('campaigns/', 'Kampanyalar');
testAPI('notifications/', 'Bildirimler');

echo "✅ Test tamamlandı!\n";
?>

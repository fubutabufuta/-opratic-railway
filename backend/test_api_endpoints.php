<?php
// API endpoint'lerini test et
$baseUrl = 'http://192.168.1.159:8001';

$endpoints = [
    'auth/login' => 'POST',
    'vehicles' => 'GET',
    'campaigns' => 'GET',
    'reminders' => 'GET',
    'reminders/upcoming' => 'GET',
    'quotes' => 'GET',
];

echo "=== API Endpoint Testleri ===\n\n";

foreach ($endpoints as $endpoint => $method) {
    $url = "$baseUrl/api/v1/$endpoint";
    
    echo "Testing: $method $endpoint\n";
    
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
        ]
    ];
    
    if ($method === 'POST' && $endpoint === 'auth/login') {
        $options['http']['content'] = json_encode([
            'phone' => '5551234567',
            'password' => '123456'
        ]);
    }
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        $error = error_get_last();
        echo "❌ Hata: " . $error['message'] . "\n";
    } else {
        echo "✅ Başarılı - Yanıt uzunluğu: " . strlen($result) . " karakter\n";
        if (strlen($result) < 200) {
            echo "   Yanıt: $result\n";
        }
    }
    
    echo "\n";
}
?> 
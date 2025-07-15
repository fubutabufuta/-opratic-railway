<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Analytics verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Basit log kaydı (production'da database'e kaydedilir)
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $input,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Log dosyasına yaz (opsiyonel)
    // file_put_contents('analytics.log', json_encode($logData) . "\n", FILE_APPEND);
    
    // Başarılı response döndür
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Analytics event tracked successfully'
    ]);
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?> 
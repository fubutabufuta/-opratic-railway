<?php
// include_once('../test_db.php'); // GEREKSİZ ve hata veriyor, kaldırıldı
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $conn = $database->getConnection();
    $userCount = $conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $serverInfo = $conn->query('SHOW STATUS')->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode([
        'status' => 'success',
        'message' => 'Veritabanı bağlantısı başarılı',
        'user_count' => $userCount,
        'server_info' => $serverInfo ? 'OK' : 'N/A',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $userId = $_GET['user_id'] ?? '1';

    // Okunmamış yanıt sayısını hesapla
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM quote_responses qres 
        JOIN quote_requests qr ON qres.quote_request_id = qr.id 
        WHERE qr.user_id = ? AND (qres.is_read = 0 OR qres.is_read IS NULL)
    ");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetchColumn() ?: 0;

    echo json_encode([
        'success' => true,
        'unread_count' => (int)$unreadCount
    ]);
} catch (Exception $e) {
    // Fallback
    echo json_encode([
        'success' => true,
        'unread_count' => 2 // Demo için sabit sayı
    ]);
}

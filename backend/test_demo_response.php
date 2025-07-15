<?php
require_once 'config/database.php';

echo "=== DEMO QUOTE RESPONSE TEST ===\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Demo quote response oluştur
    $stmt = $db->prepare("
        INSERT INTO quote_responses 
        (quote_request_id, provider_id, response_message, estimated_price, estimated_duration, is_read) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([16, 4, 'Test yanıt mesajı. Servisiniz için hazırız.', 500.00, '2 gün', 0]);

    if ($result) {
        echo "✅ Demo quote response oluşturuldu\n";
    } else {
        echo "❌ Quote response oluşturulamadı\n";
    }

    // Badge count test et
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM quote_responses qres 
        JOIN quote_requests qr ON qres.quote_request_id = qr.id 
        WHERE qr.user_id = 1 AND (qres.is_read = 0 OR qres.is_read IS NULL)
    ");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    echo "📱 User 1 için okunmamış yanıt sayısı: $count\n";

    // Responses API mock test
    echo "\n🧪 Responses API Mock Test:\n";
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$count
    ]) . "\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

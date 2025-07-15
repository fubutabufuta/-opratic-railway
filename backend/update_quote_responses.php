<?php
require_once 'config/database.php';

echo "=== QUOTE RESPONSES TABLE UPDATE ===\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // is_read sütunu ekle
    $db->exec("ALTER TABLE quote_responses ADD COLUMN is_read TINYINT(1) DEFAULT 0");
    echo "✅ is_read sütunu eklendi\n";
} catch (Exception $e) {
    // Sütun zaten varsa hata vermez
    echo "ℹ️  is_read sütunu zaten mevcut veya eklendi\n";
}

try {
    // Mevcut quote response sayısını kontrol et
    $stmt = $db->prepare('SELECT COUNT(*) FROM quote_responses');
    $stmt->execute();
    $count = $stmt->fetchColumn();

    echo "📊 Mevcut quote response sayısı: $count\n";

    // Test için okunmamış yanıt sayısını kontrol et
    $stmt = $db->prepare('SELECT COUNT(*) FROM quote_responses WHERE is_read = 0 OR is_read IS NULL');
    $stmt->execute();
    $unreadCount = $stmt->fetchColumn();

    echo "📬 Okunmamış yanıt sayısı: $unreadCount\n";

    echo "\n✅ Quote responses sistemi hazır!\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

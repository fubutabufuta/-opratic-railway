<?php
require_once 'config/database.php';

echo "=== SERVICE TYPE DÜZELTME ===\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Boş service_type'ları düzelt
    $stmt = $db->prepare("UPDATE quote_requests SET service_type = 'maintenance' WHERE service_type = '' OR service_type IS NULL");
    $result = $stmt->execute();
    $updated = $stmt->rowCount();

    echo "Güncellenen kayıt sayısı: $updated\n";

    // Kontrol et
    $stmt = $db->query("SELECT id, title, service_type FROM quote_requests ORDER BY id DESC LIMIT 5");
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nSon 5 quote request:\n";
    foreach ($quotes as $q) {
        echo "ID: {$q['id']}, Title: {$q['title']}, Service Type: {$q['service_type']}\n";
    }

    echo "\n✅ Service type'lar düzeltildi!\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

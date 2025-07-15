<?php
require_once 'config/database.php';

echo "=== Abonelik Tarihleri Kontrolü ===\n";

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id, provider_id, start_date, end_date, created_at, status FROM subscriptions ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Toplam bulunan abonelik: " . count($results) . "\n\n";

    foreach ($results as $row) {
        echo "ID: " . $row['id'] . "\n";
        echo "Durum: " . $row['status'] . "\n";
        echo "Oluşturulma: " . $row['created_at'] . "\n";
        echo "Başlangıç: " . ($row['start_date'] ?? 'NULL') . "\n";
        echo "Bitiş: " . ($row['end_date'] ?? 'NULL') . "\n";
        echo "---\n";
    }

    // Test: Bugünden 12 ay sonrası
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime($start_date . " +12 months"));
    echo "\nTest Tarihleri:\n";
    echo "Bugün: " . $start_date . "\n";
    echo "12 ay sonra: " . $end_date . "\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

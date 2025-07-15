<?php
require_once 'config/database.php';

echo "=== Abonelik Tarihleri Düzeltme ===\n";

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Önce mevcut tabloyu yedekle ve temizle
    echo "Mevcut abonelik verilerini temizliyorum...\n";

    $stmt = $conn->prepare("DELETE FROM subscriptions");
    $stmt->execute();

    echo "Subscriptions tablosu temizlendi.\n";
    echo "Yeni abonelik oluşturulması için admin paneline gidin.\n";
    echo "Bugünün tarihi: " . date('Y-m-d') . "\n";
    echo "12 ay sonrası: " . date('Y-m-d', strtotime(date('Y-m-d') . ' +12 months')) . "\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

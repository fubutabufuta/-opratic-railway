<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Veritabanı bağlantısı başarısız\n";
        exit();
    }
    
    // Campaigns tablosunun yapısını kontrol et
    $query = "DESCRIBE campaigns";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Campaigns tablosu yapısı:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    
    // Campaigns tablosundaki kayıtları kontrol et
    echo "\nCampaigns tablosundaki kayıtlar:\n";
    $campaigns = $db->query("SELECT * FROM campaigns LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($campaigns as $campaign) {
        echo json_encode($campaign) . "\n";
    }
    
    echo "\nToplam kampanya sayısı: " . count($campaigns) . "\n";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
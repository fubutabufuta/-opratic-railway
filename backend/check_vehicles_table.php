<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Veritabanı bağlantısı başarısız\n";
        exit();
    }
    
    // Vehicles tablosunun yapısını kontrol et
    $query = "DESCRIBE vehicles";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Vehicles tablosu yapısı:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    
    // Vehicles tablosundaki kayıtları kontrol et
    echo "\nVehicles tablosundaki kayıtlar:\n";
    $vehicles = $db->query("SELECT id, user_id, brand, model, year, plate, image FROM vehicles LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($vehicles as $vehicle) {
        echo json_encode($vehicle) . "\n";
    }
    
    echo "\nToplam araç sayısı: " . count($vehicles) . "\n";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "Veritabanı bağlantı hatası\n";
        exit();
    }

    // Vehicles tablosunun yapısını kontrol et
    $query = "DESCRIBE vehicles";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo "=== VEHICLES TABLOSU YAPISI ===\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Key: {$row['Key']} | Default: {$row['Default']}\n";
    }

    // Örnek bir araç kaydını kontrol et
    $query = "SELECT * FROM vehicles LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vehicle) {
        echo "\n=== ÖRNEK ARAÇ KAYDI ===\n";
        foreach ($vehicle as $key => $value) {
            echo "$key: $value\n";
        }
    } else {
        echo "\nAraç kaydı bulunamadı\n";
    }

    // Reminders tablosunun yapısını da kontrol et
    $query = "DESCRIBE reminders";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo "\n=== REMINDERS TABLOSU YAPISI ===\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Key: {$row['Key']} | Default: {$row['Default']}\n";
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

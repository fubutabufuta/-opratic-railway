<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Veritabanı bağlantısı başarısız\n";
        exit();
    }
    
    // Reminders tablosunun yapısını kontrol et
    $query = "DESCRIBE reminders";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Reminders tablosu yapısı:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    
    // Reminders tablosundaki kayıtları kontrol et
    echo "\nReminders tablosundaki kayıtlar:\n";
    $reminders = $db->query("SELECT * FROM reminders LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reminders as $reminder) {
        echo json_encode($reminder) . "\n";
    }
    
    echo "\nToplam hatırlatma sayısı: " . count($reminders) . "\n";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Veritabanı bağlantısı başarısız\n";
        exit();
    }
    
    // Users tablosunun yapısını kontrol et
    $query = "DESCRIBE users";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users tablosu yapısı:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    
    // Users tablosundaki kayıtları kontrol et
    echo "\nUsers tablosundaki kayıtlar:\n";
    $users = $db->query("SELECT * FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo json_encode($user) . "\n";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
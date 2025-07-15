<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Veritabanı bağlantısı başarısız\n";
        exit();
    }
    
    // MySQL için tablo listesi
    $query = "SHOW TABLES";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Veritabanındaki tablolar:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Users tablosunu kontrol et
    if (in_array('users', $tables)) {
        echo "\nUsers tablosu mevcut. Kayıt sayısı:\n";
        $user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "Toplam kullanıcı: $user_count\n";
        
        // İlk kullanıcıyı göster
        $user = $db->query("SELECT id, phone, name FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "İlk kullanıcı: " . json_encode($user) . "\n";
        }
    } else {
        echo "\nUsers tablosu bulunamadı!\n";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>

<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Bildirim tabloları oluşturuluyor...\n";

// SQL dosyasını oku
$sql = file_get_contents('create_notification_tables.sql');

// SQL komutlarını ayrı ayrı çalıştır
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        try {
            $db->exec($statement);
            echo "✓ Komut başarıyla çalıştırıldı\n";
        } catch (PDOException $e) {
            echo "✗ Hata: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nBildirim tabloları başarıyla oluşturuldu!\n";

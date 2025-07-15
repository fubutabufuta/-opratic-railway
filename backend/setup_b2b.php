<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "B2B tabloları oluşturuluyor...\n";

// SQL dosyasını oku
$sql = file_get_contents('create_b2b_tables.sql');

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

echo "\nB2B tabloları başarıyla oluşturuldu!\n";
echo "\nTest için kullanabileceğiniz API anahtarları:\n";
echo "- Galeri: b2b_galeri_test_key_123456\n";
echo "- Sigorta: b2b_sigorta_test_key_789012\n";
echo "- Servis: b2b_servis_test_key_345678\n";

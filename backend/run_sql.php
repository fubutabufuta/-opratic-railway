<?php
$host = 'localhost';
$dbname = 'otoasist';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL dosyasını oku
    $sql = file_get_contents('create_database_functions.sql');
    
    // SQL'i parçalara ayır ve çalıştır
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ SQL komutu başarıyla çalıştırıldı\n";
            } catch (PDOException $e) {
                echo "✗ SQL komutu hatası: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Tüm database fonksiyonları başarıyla oluşturuldu!\n";
    
} catch (PDOException $e) {
    echo "❌ Database bağlantı hatası: " . $e->getMessage() . "\n";
}
?> 
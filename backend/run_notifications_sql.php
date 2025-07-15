<?php
echo "=== Oto Asist Bildirim Tabloları Oluşturuluyor ===\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    echo "✅ Veritabanı bağlantısı başarılı\n";

    // SQL dosyasını oku
    $sql_content = file_get_contents('create_notifications_tables.sql');

    if (!$sql_content) {
        throw new Exception("SQL dosyası okunamadı");
    }

    echo "📄 SQL dosyası okundu\n";

    // SQL komutlarını ayır ve çalıştır
    $statements = explode(';', $sql_content);
    $executed = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);

        // Boş satırları ve yorumları geç
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $executed++;
            echo "✅ SQL komutu çalıştırıldı: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            $errors++;
            echo "❌ Hata: " . $e->getMessage() . "\n";
            echo "   Komut: " . substr($statement, 0, 100) . "...\n";
        }
    }

    echo "\n=== ÖZET ===\n";
    echo "✅ Başarılı komutlar: $executed\n";
    echo "❌ Hatalı komutlar: $errors\n";

    // Tabloları kontrol et
    echo "\n=== TABLO KONTROL ===\n";
    $tables = ['notifications', 'user_notifications', 'notification_settings'];

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "✅ $table tablosu mevcut (" . count($columns) . " sütun)\n";
        } catch (PDOException $e) {
            echo "❌ $table tablosu bulunamadı\n";
        }
    }

    // Demo verileri kontrol et
    echo "\n=== VERİ KONTROL ===\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
        $count = $stmt->fetchColumn();
        echo "📊 notifications tablosunda $count kayıt var\n";

        $stmt = $pdo->query("SELECT COUNT(*) FROM notification_settings");
        $count = $stmt->fetchColumn();
        echo "📊 notification_settings tablosunda $count kayıt var\n";
    } catch (PDOException $e) {
        echo "❌ Veri kontrol hatası: " . $e->getMessage() . "\n";
    }

    echo "\n🎉 Bildirim sistemi veritabanı kurulumu tamamlandı!\n";
} catch (Exception $e) {
    echo "❌ Kritik hata: " . $e->getMessage() . "\n";
    exit(1);
}

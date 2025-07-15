<?php
echo "=== BİLDİRİM SİSTEMİ TABLO KONTROL ===\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $tables = ['notifications', 'user_notifications', 'notification_settings'];

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            echo "✅ $table tablosu mevcut\n";

            $count_stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $count_stmt->fetchColumn();
            echo "   📊 $count kayıt var\n";
        } catch (Exception $e) {
            echo "❌ $table tablosu yok\n";
        }
    }

    // FCM token kontrolü
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'fcm_token'");
        if ($stmt->rowCount() > 0) {
            echo "✅ users.fcm_token alanı mevcut\n";
        } else {
            echo "❌ users.fcm_token alanı yok\n";
        }
    } catch (Exception $e) {
        echo "❌ FCM token kontrolü başarısız: " . $e->getMessage() . "\n";
    }

    echo "\n=== ÖRNEKLEMELİ VERİLER ===\n";
    try {
        $stmt = $pdo->query("SELECT title, notification_type FROM notifications LIMIT 3");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "📄 " . $row['title'] . " (" . $row['notification_type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "❌ Örnek veri görüntüleme hatası\n";
    }
} catch (Exception $e) {
    echo "❌ Veritabanı bağlantı hatası: " . $e->getMessage() . "\n";
}

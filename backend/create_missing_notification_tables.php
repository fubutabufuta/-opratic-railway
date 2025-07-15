<?php
echo "=== EKSİK BİLDİRİM TABLOLARI OLUŞTURULUYOR ===\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    echo "✅ Veritabanı bağlantısı başarılı\n";

    // 1. user_notifications tablosu
    echo "\n1. user_notifications tablosu oluşturuluyor...\n";
    $sql1 = "CREATE TABLE IF NOT EXISTS user_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notification_id INT NOT NULL,
        user_id INT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        read_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_notification (notification_id, user_id),
        INDEX idx_user_read (user_id, is_read),
        FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    try {
        $pdo->exec($sql1);
        echo "✅ user_notifications tablosu oluşturuldu\n";
    } catch (PDOException $e) {
        echo "❌ user_notifications hatası: " . $e->getMessage() . "\n";
    }

    // 2. notification_settings tablosu
    echo "\n2. notification_settings tablosu oluşturuluyor...\n";
    $sql2 = "CREATE TABLE IF NOT EXISTS notification_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        reminders_enabled BOOLEAN DEFAULT TRUE,
        campaigns_enabled BOOLEAN DEFAULT TRUE,
        service_alerts_enabled BOOLEAN DEFAULT TRUE,
        personal_offers_enabled BOOLEAN DEFAULT TRUE,
        push_notifications_enabled BOOLEAN DEFAULT TRUE,
        email_notifications_enabled BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    try {
        $pdo->exec($sql2);
        echo "✅ notification_settings tablosu oluşturuldu\n";
    } catch (PDOException $e) {
        echo "❌ notification_settings hatası: " . $e->getMessage() . "\n";
    }

    // 3. FCM token alanı ekleme
    echo "\n3. users tablosuna fcm_token alanı ekleniyor...\n";
    $sql3 = "ALTER TABLE users ADD COLUMN fcm_token VARCHAR(255) NULL";

    try {
        $pdo->exec($sql3);
        echo "✅ fcm_token alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✅ fcm_token alanı zaten mevcut\n";
        } else {
            echo "❌ fcm_token hatası: " . $e->getMessage() . "\n";
        }
    }

    // 4. İndeks ekleme
    echo "\n4. FCM token indeksi ekleniyor...\n";
    $sql4 = "ALTER TABLE users ADD INDEX idx_fcm_token (fcm_token)";

    try {
        $pdo->exec($sql4);
        echo "✅ fcm_token indeksi eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "✅ fcm_token indeksi zaten mevcut\n";
        } else {
            echo "❌ indeks hatası: " . $e->getMessage() . "\n";
        }
    }

    // 5. Varsayılan notification settings oluştur
    echo "\n5. Varsayılan notification settings oluşturuluyor...\n";
    $sql5 = "INSERT INTO notification_settings (user_id) 
             SELECT id FROM users 
             WHERE id NOT IN (SELECT user_id FROM notification_settings)";

    try {
        $stmt = $pdo->prepare($sql5);
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "✅ $count kullanıcı için notification settings oluşturuldu\n";
    } catch (PDOException $e) {
        echo "❌ Settings oluşturma hatası: " . $e->getMessage() . "\n";
    }

    // Final kontrol
    echo "\n=== FİNAL KONTROL ===\n";
    $tables = ['notifications', 'user_notifications', 'notification_settings'];

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ $table: $count kayıt\n";
        } catch (Exception $e) {
            echo "❌ $table: kontrol edilemedi\n";
        }
    }

    echo "\n🎉 Bildirim sistemi kurulumu tamamlandı!\n";
} catch (Exception $e) {
    echo "❌ Kritik hata: " . $e->getMessage() . "\n";
}

<?php
require_once 'config/database.php';

echo "=== Bildirim Sistemi Kurulumu ===\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Bildirim tablosunu kontrol et
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() == 0) {
        echo "notifications tablosu oluşturuluyor...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            user_id INT DEFAULT 0,
            notification_type VARCHAR(50) DEFAULT 'general',
            target_type VARCHAR(50) DEFAULT 'all',
            target_value VARCHAR(100) NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "✅ notifications tablosu oluşturuldu\n";
    }

    // user_notifications tablosunu kontrol et
    $stmt = $db->query("SHOW TABLES LIKE 'user_notifications'");
    if ($stmt->rowCount() == 0) {
        echo "user_notifications tablosu oluşturuluyor...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS user_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            notification_id INT NOT NULL,
            user_id INT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_notification (notification_id, user_id)
        )");
        echo "✅ user_notifications tablosu oluşturuldu\n";
    }

    // Mevcut bildirimleri temizle (test için)
    $db->exec("DELETE FROM notifications WHERE user_id = 1");

    // Örnek bildirimler ekle
    $notifications = [
        ['🎉 Hoş geldiniz!', 'Oto Asist uygulamasına hoş geldiniz. Araçlarınızı takip etmeye başlayabilirsiniz.', 'general'],
        ['🔧 Servis Hatırlatması', 'Aracınızın servis zamanı yaklaşıyor. Randevu almayı unutmayın.', 'reminder'],
        ['🎁 Özel Kampanya', 'Size özel %20 indirimli servis kampanyamız başladı! Fırsatı kaçırmayın.', 'campaign'],
        ['⚠️ Sigorta Uyarısı', 'Aracınızın sigorta tarihi yaklaşıyor. Yenilemeyi unutmayın.', 'alert'],
        ['📰 Yeni Haber', 'Araç bakım ipuçları hakkında yeni makale yayınlandı.', 'news'],
        ['🛡️ Muayene Hatırlatması', 'Aracınızın muayene tarihi yaklaşıyor. 15 gün kaldı.', 'reminder'],
        ['💰 Kasko Bildirimi', 'Kasko poliçenizin yenileme zamanı geldi.', 'alert']
    ];

    foreach ($notifications as $index => $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (title, message, notification_type, user_id, created_at) VALUES (?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? DAY))");
        $stmt->execute([$notif[0], $notif[1], $notif[2], $index]);
        echo "✅ Bildirim eklendi: {$notif[0]}\n";
    }

    // Bildirim sayısını kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n📊 Toplam bildirim sayısı: {$result['count']}\n";

    echo "\n🎉 Bildirim sistemi başarıyla kuruldu!\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

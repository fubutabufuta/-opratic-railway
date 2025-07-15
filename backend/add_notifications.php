<?php
require_once 'config/database.php';

echo "Bildirim verileri ekleniyor...\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Notifications tablosunu oluştur
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

    // Mevcut bildirimleri temizle
    $db->exec("DELETE FROM notifications WHERE user_id = 1");

    // Örnek bildirimler
    $notifications = [
        ['🎉 Hoş geldiniz!', 'Oto Asist uygulamasına hoş geldiniz.', 'general'],
        ['🔧 Servis Hatırlatması', 'Aracınızın servis zamanı yaklaşıyor.', 'reminder'],
        ['🎁 Özel Kampanya', 'Size özel %20 indirimli servis kampanyası!', 'campaign'],
        ['⚠️ Sigorta Uyarısı', 'Sigorta tarihi yaklaşıyor.', 'alert'],
        ['📰 Yeni Haber', 'Araç bakım ipuçları yayınlandı.', 'news']
    ];

    foreach ($notifications as $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (title, message, notification_type, user_id) VALUES (?, ?, ?, 1)");
        $stmt->execute([$notif[0], $notif[1], $notif[2]]);
    }

    echo "Bildirimler eklendi!\n";

    // Quote requests sample data ekle
    echo "\nTeklif talepleri ekleniyor...\n";

    $db->exec("DELETE FROM quote_requests WHERE user_id = 1");

    $quoteRequests = [
        ['maintenance', 'Periyodik Bakım', 'Aracımın 15.000 km bakımını yaptırmak istiyorum'],
        ['repair', 'Fren Tamiri', 'Ön frenlerimde ses geliyor'],
        ['parts', 'Lastik Değişimi', '4 adet lastik değişimi gerekiyor'],
        ['insurance', 'Araç Sigortası', 'Araç sigortamı yenilemek istiyorum'],
        ['other', 'Klima Tamiri', 'Araç kliması çalışmıyor']
    ];

    foreach ($quoteRequests as $req) {
        $stmt = $db->prepare("INSERT INTO quote_requests (service_type, title, description, user_id, status) VALUES (?, ?, ?, 1, 'pending')");
        $stmt->execute([$req[0], $req[1], $req[2]]);
    }

    echo "✅ " . count($quoteRequests) . " teklif talebi eklendi\n";

    // Campaigns ekle
    echo "\nKampanyalar ekleniyor...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        discount_percentage INT DEFAULT 0,
        start_date DATE DEFAULT CURDATE(),
        end_date DATE DEFAULT DATE_ADD(CURDATE(), INTERVAL 30 DAY),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("DELETE FROM campaigns");

    $campaigns = [
        ['Kış Lastik Kampanyası', '4 adet kış lastiği alana %25 indirim!', '/assets/images/kis-lastik.jpg', 25],
        ['Periyodik Bakım Fırsatı', 'Aralık ayına özel periyodik bakımda %20 indirim', '/assets/images/bakim.jpg', 20],
        ['Yılbaşı Sigorta Fırsatı', 'Yeni yıla özel araç sigortasında özel indirim', '/assets/images/sigorta.jpg', 15]
    ];

    foreach ($campaigns as $campaign) {
        $stmt = $db->prepare("INSERT INTO campaigns (title, description, image_url, discount_percentage) VALUES (?, ?, ?, ?)");
        $stmt->execute([$campaign[0], $campaign[1], $campaign[2], $campaign[3]]);
    }

    echo "✅ " . count($campaigns) . " kampanya eklendi\n";

    // News ekle
    echo "\nHaberler ekleniyor...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        excerpt VARCHAR(500),
        image_url VARCHAR(500),
        category VARCHAR(100) DEFAULT 'genel',
        view_count INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("DELETE FROM news");

    $news = [
        ['Kış Lastiği Zorunluluğu', 'Kış mevsimi ile birlikte araçlarda kış lastiği kullanımı zorunlu hale geldi.', 'Kış lastiği zorunluluğu başladı.', '/assets/images/kis-lastigi.jpg', 'trafik'],
        ['Araç Muayene Güncelleme', 'Araç muayene ücretlerinde yeni düzenleme yapıldı.', 'Muayene ücretleri güncellendi.', '/assets/images/muayene.jpg', 'mevzuat'],
        ['Elektrikli Araç Teşvikleri', 'Elektrikli araç alımında yeni teşvik paketi açıklandı.', 'Elektrikli araçlar için teşvik.', '/assets/images/elektrikli.jpg', 'teknoloji']
    ];

    foreach ($news as $newsItem) {
        $stmt = $db->prepare("INSERT INTO news (title, content, excerpt, image_url, category, view_count) VALUES (?, ?, ?, ?, ?, ?)");
        $viewCount = rand(50, 300);
        $stmt->execute([$newsItem[0], $newsItem[1], $newsItem[2], $newsItem[3], $newsItem[4], $viewCount]);
    }

    echo "✅ " . count($news) . " haber eklendi\n";

    echo "\n🎯 TÜM BONUS ÖZELLİKLER EKLENDİ!\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

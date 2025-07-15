<?php
echo "🎯 SAMPLE VERİLER EKLENİYOR...\n";
echo "============================\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Veritabanı bağlantısı başarılı\n";

    // 1. QUOTE_REQUESTS için sample data
    echo "\n💼 Teklif talepleri oluşturuluyor...\n";

    $db->exec("DELETE FROM quote_requests WHERE user_id = 1");

    $quoteRequests = [
        ['maintenance', 'Periyodik Bakım', 'Aracımın 15.000 km bakımını yaptırmak istiyorum. Yağ değişimi, filtre değişimi dahil.'],
        ['repair', 'Fren Tamiri', 'Ön frenlerimde ses geliyor. Kontrol edilip gerekli onarım yapılsın.'],
        ['parts', 'Lastik Değişimi', '4 adet lastik değişimi gerekiyor. 205/55 R16 ölçüsünde.'],
        ['insurance', 'Araç Sigortası', 'Araç sigortamı yenilemek istiyorum. En uygun teklifleri istiyorum.'],
        ['other', 'Klima Tamiri', 'Araç kliması çalışmıyor. Kontrol edilmesi gerekiyor.']
    ];

    foreach ($quoteRequests as $index => $req) {
        $stmt = $db->prepare("INSERT INTO quote_requests (service_type, title, description, user_id, status, created_at) VALUES (?, ?, ?, 1, 'pending', NOW())");
        $stmt->execute([$req[0], $req[1], $req[2]]);
    }

    echo "📋 " . count($quoteRequests) . " teklif talebi eklendi\n";

    // 2. CAMPAIGNS için sample data
    echo "\n🎁 Kampanyalar oluşturuluyor...\n";

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
        ['Kış Lastik Kampanyası', '4 adet kış lastiği alana %25 indirim! Montaj ücretsiz.', '/assets/images/kis-lastik.jpg', 25],
        ['Periyodik Bakım Fırsatı', 'Aralık ayına özel periyodik bakımda %20 indirim', '/assets/images/bakim.jpg', 20],
        ['Yılbaşı Sigorta Fırsatı', 'Yeni yıla özel araç sigortasında özel indirim', '/assets/images/sigorta.jpg', 15],
        ['Acil Yardım Paketi', 'Çekici + Acil tamır hizmeti paket fiyatla', '/assets/images/acil-yardim.jpg', 10]
    ];

    foreach ($campaigns as $campaign) {
        $stmt = $db->prepare("INSERT INTO campaigns (title, description, image_url, discount_percentage) VALUES (?, ?, ?, ?)");
        $stmt->execute([$campaign[0], $campaign[1], $campaign[2], $campaign[3]]);
    }

    echo "🎉 " . count($campaigns) . " kampanya eklendi\n";

    // 3. NEWS için sample data
    echo "\n📰 Haberler oluşturuluyor...\n";

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
        ['Kış Lastiği Zorunluluğu Başladı', 'Kış mevsimi ile birlikte araçlarda kış lastiği kullanımı zorunlu hale geldi. Detaylar haberimizde...', 'Kış lastiği zorunluluğu 1 Aralık itibariyle başladı.', '/assets/images/kis-lastigi.jpg', 'trafik'],
        ['Araç Muayene Ücretlerine Güncelleme', 'Araç muayene ücretlerinde yeni düzenleme yapıldı. Yeni tarife bilgileri...', 'Muayene ücretleri güncellendi.', '/assets/images/muayene.jpg', 'mevzuat'],
        ['Elektrikli Araç Teşvikleri', 'Elektrikli araç alımında yeni teşvik paketi açıklandı. Çevre dostu araçlar için önemli destek...', 'Elektrikli araçlar için teşvik paketi.', '/assets/images/elektrikli-arac.jpg', 'teknoloji'],
        ['Trafik Sigortasında Yeni Dönem', '2024 yılı trafik sigortası primlerinde değişiklikler oldu. Detaylar...', 'Sigorta primlerinde güncellemeler.', '/assets/images/sigorta-haber.jpg', 'sigorta']
    ];

    foreach ($news as $newsItem) {
        $stmt = $db->prepare("INSERT INTO news (title, content, excerpt, image_url, category, view_count) VALUES (?, ?, ?, ?, ?, ?)");
        $viewCount = rand(50, 500);
        $content = $newsItem[1] . "\n\nDetaylı bilgi için ilgili kurumların web sitelerini ziyaret edebilirsiniz.";
        $stmt->execute([$newsItem[0], $content, $newsItem[2], $newsItem[3], $newsItem[4], $viewCount]);
    }

    echo "📄 " . count($news) . " haber eklendi\n";

    // 4. VEHICLES kontrol
    echo "\n🚗 Araçlar kontrol ediliyor...\n";

    $stmt = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE user_id = 1");
    $vehicleCount = $stmt->fetch()['count'];

    if ($vehicleCount == 0) {
        $vehicles = [
            ['Toyota', 'Corolla', 2022, '34ABC123'],
            ['Volkswagen', 'Golf', 2021, '06DEF456']
        ];

        foreach ($vehicles as $vehicle) {
            $stmt = $db->prepare("INSERT INTO vehicles (user_id, brand, model, year, plate) VALUES (1, ?, ?, ?, ?)");
            $stmt->execute($vehicle);
        }
        echo "🚗 " . count($vehicles) . " araç eklendi\n";
    } else {
        echo "🚗 Zaten $vehicleCount araç mevcut\n";
    }

    // 5. İSTATİSTİKLER
    echo "\n📊 TOPLAM İSTATİSTİKLER:\n";
    echo "========================\n";

    $stmt = $db->query("SELECT COUNT(*) as total, SUM(is_read=0) as unread FROM notifications WHERE user_id = 1");
    $notifStats = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as total FROM quote_requests WHERE user_id = 1");
    $quoteStats = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as total FROM vehicles WHERE user_id = 1");
    $vehicleStats = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as total FROM campaigns WHERE is_active = 1");
    $campaignStats = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as total FROM news");
    $newsStats = $stmt->fetch();

    echo "📱 Bildirimler: {$notifStats['total']} total, {$notifStats['unread']} okunmamış\n";
    echo "💼 Teklifler: {$quoteStats['total']} teklif talebi\n";
    echo "🚗 Araçlar: {$vehicleStats['total']} araç kaydı\n";
    echo "🎁 Kampanyalar: {$campaignStats['total']} aktif kampanya\n";
    echo "📰 Haberler: {$newsStats['total']} haber\n";

    echo "\n✅ TÜM SAMPLE VERİLER HAZIR!\n";
    echo "Backend: http://127.0.0.1:8000\n";
    echo "Frontend: http://localhost:3004\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

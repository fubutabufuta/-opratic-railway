<?php
echo "🎯 BONUS ÖZELLİKLER KURULUYOR...\n";
echo "================================\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Veritabanı bağlantısı başarılı\n";

    // 1. NOTIFICATIONS TABLOSU VE VERİLER
    echo "\n📬 Bildirimler sistemi kuruluyor...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        user_id INT DEFAULT 1,
        notification_type VARCHAR(50) DEFAULT 'general',
        target_type VARCHAR(50) DEFAULT 'all',
        target_value VARCHAR(100) NULL,
        status VARCHAR(20) DEFAULT 'active',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_count INT DEFAULT 0
    )");

    // Mevcut bildirimleri temizle ve yenilerini ekle
    $db->exec("DELETE FROM notifications WHERE user_id = 1");

    $notifications = [
        ['🎉 Hoş Geldiniz!', 'Oto Asist uygulamasına hoş geldiniz. Tüm araç ihtiyaçlarınız burada!', 'welcome'],
        ['🔧 Servis Hatırlatması', 'Aracınızın periyodik bakım zamanı yaklaşıyor. Randevu almayı unutmayın!', 'reminder'],
        ['🎁 Özel Kampanya', 'Size özel %25 indirimli servis kampanyası! Son 3 gün.', 'campaign'],
        ['⚠️ Sigorta Uyarısı', 'Araç sigortanızın bitiş tarihi yaklaşıyor. Yenileme zamanı!', 'alert'],
        ['📰 Yeni Güncelleme', 'Uygulama güncellendi. Yeni özellikler eklendi!', 'update'],
        ['🛠️ Bakım Tamamlandı', 'Son servis işleminiz başarıyla tamamlandı.', 'service'],
        ['💰 Teklif Aldınız', 'Talebiniz için 3 yeni teklif geldi. İncelemek için tıklayın.', 'quote'],
        ['🚗 Araç Kaydı', 'Yeni aracınız sisteme başarıyla kaydedildi.', 'vehicle']
    ];

    foreach ($notifications as $index => $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (title, message, notification_type, user_id, is_read) VALUES (?, ?, ?, 1, ?)");
        $isRead = $index > 5 ? 1 : 0; // İlk 5'i okunmamış, diğerleri okunmuş
        $stmt->execute([$notif[0], $notif[1], $notif[2], $isRead]);
    }

    echo "📱 " . count($notifications) . " bildirim eklendi\n";

    // 2. QUOTE_REQUESTS SAMPLE DATA
    echo "\n💼 Teklif talepleri oluşturuluyor...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS quote_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        vehicle_id INT DEFAULT 1,
        service_type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        city VARCHAR(100) DEFAULT 'Lefkoşa',
        user_notes TEXT,
        share_phone TINYINT(1) DEFAULT 1,
        status VARCHAR(50) DEFAULT 'pending',
        budget_min DECIMAL(10,2) DEFAULT NULL,
        budget_max DECIMAL(10,2) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Mevcut teklif taleplerini temizle
    $db->exec("DELETE FROM quote_requests WHERE user_id = 1");

    $quoteRequests = [
        ['maintenance', 'Periyodik Bakım', 'Aracımın 15.000 km bakımını yaptırmak istiyorum. Yağ değişimi, filtre değişimi dahil.', 1500, 2500],
        ['repair', 'Fren Tamiri', 'Ön frenlerimde ses geliyor. Kontrol edilip gerekli onarım yapılsın.', 800, 1500],
        ['parts', 'Lastik Değişimi', '4 adet lastik değişimi gerekiyor. 205/55 R16 ölçüsünde.', 2000, 3500],
        ['insurance', 'Araç Sigortası', 'Araç sigortamı yenilemek istiyorum. En uygun teklifleri istiyorum.', 1200, 2000],
        ['towing', 'Çekici Hizmeti', 'Aracım çalışmıyor, servise çekilmesi gerekiyor.', 200, 500]
    ];

    foreach ($quoteRequests as $index => $req) {
        $stmt = $db->prepare("INSERT INTO quote_requests (service_type, title, description, budget_min, budget_max, user_id, status) VALUES (?, ?, ?, ?, ?, 1, ?)");
        $status = $index < 2 ? 'completed' : 'pending';
        $stmt->execute([$req[0], $req[1], $req[2], $req[3], $req[4], $status]);
    }

    echo "📋 " . count($quoteRequests) . " teklif talebi eklendi\n";

    // 3. SERVICE PROVIDERS SAMPLE DATA
    echo "\n🏢 Servis sağlayıcıları ekleniyor...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS service_providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 0,
        company_name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(20),
        email VARCHAR(255),
        city VARCHAR(100) DEFAULT 'Lefkoşa',
        address TEXT,
        services TEXT,
        description TEXT,
        rating DECIMAL(3,2) DEFAULT 4.5,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $providers = [
        ['Oto Servis Plus', 'Mehmet Yılmaz', '+905301234567', 'info@otoservisplus.com', 'Tüm marka araç servisi, yağ değişimi, fren tamiri', 'Lefkoşa merkezde 15 yıllık deneyim'],
        ['Kıbrıs Lastik Center', 'Ahmet Özkan', '+905302345678', 'ahmet@kibrislastik.com', 'Lastik satış ve montaj, jant değişimi', 'En kaliteli lastikler, uygun fiyat'],
        ['Sigorta Merkezi', 'Ayşe Demir', '+905303456789', 'ayse@sigortamerkezi.com', 'Araç sigortası, kasko, trafik sigortası', 'Tüm sigorta şirketleri ile anlaşmalı'],
        ['24/7 Çekici Hizmetleri', 'Ali Kaya', '+905304567890', 'info@24cekici.com', 'Çekici hizmeti, kurtarma, yol yardımı', 'Adanın her yerine 24 saat hizmet'],
        ['Master Oto Tamiri', 'Fatma Yıldız', '+905305678901', 'fatma@masteroto.com', 'Motor tamiri, şanzıman, klima tamiri', 'Uzman teknisyen kadrosu']
    ];

    $db->exec("DELETE FROM service_providers");

    foreach ($providers as $index => $provider) {
        $stmt = $db->prepare("INSERT INTO service_providers (company_name, contact_person, phone, email, services, description, rating) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $rating = 4.2 + ($index * 0.2); // 4.2 - 5.0 arası rating
        $stmt->execute([$provider[0], $provider[1], $provider[2], $provider[3], $provider[4], $provider[5], $rating]);
    }

    echo "🏪 " . count($providers) . " servis sağlayıcı eklendi\n";

    // 4. CAMPAIGNS SAMPLE DATA
    echo "\n🎁 Kampanyalar ekleniyor...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        discount_percentage INT DEFAULT 0,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        start_date DATE,
        end_date DATE,
        image_url VARCHAR(500),
        is_active TINYINT(1) DEFAULT 1,
        terms_conditions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $campaigns = [
        ['Kış Lastik Kampanyası', '4 adet kış lastiği alana %25 indirim! Montaj ücretsiz.', 25, 0, '2024-12-01', '2024-12-31', '/assets/images/kis-lastik.jpg'],
        ['Periyodik Bakım Fırsatı', 'Aralık ayına özel periyodik bakımda %20 indirim', 20, 0, '2024-12-01', '2024-12-31', '/assets/images/bakim.jpg'],
        ['Yılbaşı Sigorta Fırsatı', 'Yeni yıla özel araç sigortasında 200 TL indirim', 0, 200, '2024-12-15', '2025-01-15', '/assets/images/sigorta.jpg'],
        ['Acil Yardım Paketi', 'Çekici + Acil tamır hizmeti paket fiyatla', 15, 0, '2024-12-01', '2024-12-31', '/assets/images/acil-yardim.jpg']
    ];

    $db->exec("DELETE FROM campaigns");

    foreach ($campaigns as $campaign) {
        $stmt = $db->prepare("INSERT INTO campaigns (title, description, discount_percentage, discount_amount, start_date, end_date, image_url, terms_conditions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $terms = 'Kampanya koşulları: ' . $campaign[1] . ' Detaylar için mağazalarımızı arayınız.';
        $stmt->execute([$campaign[0], $campaign[1], $campaign[2], $campaign[3], $campaign[4], $campaign[5], $campaign[6], $terms]);
    }

    echo "🎉 " . count($campaigns) . " kampanya eklendi\n";

    // 5. USERS VE VEHICLES KONTROL
    echo "\n👤 Kullanıcı ve araç verileri kontrol ediliyor...\n";

    // Demo user oluştur
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) UNIQUE,
        password VARCHAR(255),
        full_name VARCHAR(255),
        email VARCHAR(255),
        is_verified TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare("INSERT IGNORE INTO users (id, phone, full_name, email, password) VALUES (1, '+905551234567', 'Demo Kullanıcı', 'demo@otoasist.com', ?)");
    $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
    $stmt->execute([$hashedPassword]);

    // Demo vehicles oluştur
    $db->exec("CREATE TABLE IF NOT EXISTS vehicles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 1,
        brand VARCHAR(100),
        model VARCHAR(100),
        year INT,
        plate VARCHAR(20),
        color VARCHAR(50),
        fuel_type VARCHAR(50) DEFAULT 'Benzin',
        engine_size VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $vehicles = [
        ['Toyota', 'Corolla', 2022, '34ABC123', 'Beyaz', 'Benzin', '1.6L'],
        ['Volkswagen', 'Golf', 2021, '06DEF456', 'Gri', 'Dizel', '1.4L'],
        ['BMW', '320i', 2023, '35GHI789', 'Siyah', 'Benzin', '2.0L']
    ];

    $db->exec("DELETE FROM vehicles WHERE user_id = 1");

    foreach ($vehicles as $vehicle) {
        $stmt = $db->prepare("INSERT INTO vehicles (user_id, brand, model, year, plate, color, fuel_type, engine_size) VALUES (1, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($vehicle);
    }

    echo "🚗 " . count($vehicles) . " araç eklendi\n";

    // 6. NEWS SAMPLE DATA
    echo "\n📰 Haberler ekleniyor...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        excerpt VARCHAR(500),
        image_url VARCHAR(500),
        category VARCHAR(100) DEFAULT 'genel',
        is_featured TINYINT(1) DEFAULT 0,
        view_count INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $news = [
        ['Kış Lastiği Zorunluluğu Başladı', 'Meteoroloji açıklamasına göre kış lastiği zorunluluğu 1 Aralık itibariyle başladı...', 'Kış mevsimi ile birlikte araçlarda kış lastiği kullanımı zorunlu hale geldi.', '/assets/images/kis-lastigi.jpg', 'trafik'],
        ['Araç Muayene Ücretlerine Zam', 'Araç muayene ücretlerinde %15 oranında artış yapıldı...', 'Yeni düzenleme ile araç muayene ücretleri güncellendi.', '/assets/images/muayene.jpg', 'mevzuat'],
        ['Elektrikli Araç Teşvikleri', 'Elektrikli araç alımında yeni teşvik paketi açıklandı...', 'Çevre dostu araçlar için önemli destek paketi.', '/assets/images/elektrikli-arac.jpg', 'teknoloji'],
        ['Trafik Sigortasında Yeni Dönem', '2024 yılı trafik sigortası primlerinde değişiklik...', 'Sigorta şirketleri yeni tarife yapısını açıkladı.', '/assets/images/sigorta-haber.jpg', 'sigorta']
    ];

    $db->exec("DELETE FROM news");

    foreach ($news as $index => $newsItem) {
        $stmt = $db->prepare("INSERT INTO news (title, content, excerpt, image_url, category, is_featured, view_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $isFeatured = $index < 2 ? 1 : 0;
        $viewCount = rand(50, 500);
        $content = $newsItem[2] . "\n\nDetaylı bilgi için ilgili kurumların web sitelerini ziyaret edebilirsiniz. Bu haber " . date('d.m.Y') . " tarihinde yayınlanmıştır.";
        $stmt->execute([$newsItem[0], $content, $newsItem[2], $newsItem[3], $newsItem[4], $isFeatured, $viewCount]);
    }

    echo "📄 " . count($news) . " haber eklendi\n";

    // 7. İSTATİSTİKLER
    echo "\n📊 İstatistikler hazırlanıyor...\n";

    // Bildirim sayısını kontrol et
    $stmt = $db->query("SELECT COUNT(*) as total, SUM(is_read=0) as unread FROM notifications WHERE user_id = 1");
    $notifStats = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as total FROM quote_requests WHERE user_id = 1");
    $quoteStats = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as total FROM vehicles WHERE user_id = 1");
    $vehicleStats = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as total FROM campaigns WHERE is_active = 1");
    $campaignStats = $stmt->fetch();

    echo "\n🎯 BONUS ÖZELLİKLER BAŞARIYLA KURULDU!\n";
    echo "=====================================\n";
    echo "📱 Bildirimler: {$notifStats['total']} total, {$notifStats['unread']} okunmamış\n";
    echo "💼 Teklifler: {$quoteStats['total']} teklif talebi\n";
    echo "🚗 Araçlar: {$vehicleStats['total']} araç kaydı\n";
    echo "🎁 Kampanyalar: {$campaignStats['total']} aktif kampanya\n";
    echo "🏢 Servis Sağlayıcıları: " . count($providers) . " firma\n";
    echo "📰 Haberler: " . count($news) . " haber\n";
    echo "\n✅ Tüm veriler hazır! Backend ve frontend çalıştırılabilir.\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

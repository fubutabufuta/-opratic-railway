<?php
// Basit database tabloları oluştur
$host = 'localhost';
$dbname = 'otoasist';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Database oluştur
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    echo "✅ Database '$dbname' oluşturuldu/seçildi\n";
    
    // User roles tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Varsayılan roller
    $pdo->exec("
        INSERT IGNORE INTO user_roles (id, name, description) VALUES
        (1, 'admin', 'Sistem yöneticisi'),
        (2, 'b2b', 'B2B kullanıcı'),
        (3, 'user', 'Normal kullanıcı'),
        (4, 'corporate_user', 'Kurumsal kullanıcı')
    ");
    
    // Users tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role_id INT DEFAULT 3,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES user_roles(id)
        )
    ");
    
    // Test kullanıcısı
    $pdo->exec("
        INSERT IGNORE INTO users (id, name, email, phone, password, role_id) VALUES
        (1, 'Test User', 'test@test.com', '+905551234567', 'password', 3)
    ");
    
    // Membership packages tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS membership_packages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            duration_months INT NOT NULL,
            max_vehicles INT DEFAULT -1,
            features JSON,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Örnek paketler
    $pdo->exec("
        INSERT IGNORE INTO membership_packages (id, name, description, price, duration_months, max_vehicles, features, is_active) VALUES
        (1, 'Aylık Plan', 'Aylık kurumsal üyelik', 99.99, 1, -1, '[\"Sınırsız araç\", \"7/24 destek\", \"Bulut yedekleme\"]', TRUE),
        (2, '3 Aylık Plan', '3 aylık kurumsal üyelik', 249.99, 3, -1, '[\"Sınırsız araç\", \"7/24 destek\", \"Bulut yedekleme\", \"Öncelikli destek\"]', TRUE),
        (3, '6 Aylık Plan', '6 aylık kurumsal üyelik', 449.99, 6, -1, '[\"Sınırsız araç\", \"7/24 destek\", \"Bulut yedekleme\", \"Öncelikli destek\", \"API erişimi\"]', TRUE),
        (4, 'Yıllık Plan', 'Yıllık kurumsal üyelik', 799.99, 12, -1, '[\"Sınırsız araç\", \"7/24 destek\", \"Bulut yedekleme\", \"Öncelikli destek\", \"API erişimi\", \"Özel entegrasyon\"]', TRUE),
        (5, 'Ömür Boyu', 'Ömür boyu kurumsal üyelik', 2999.99, 999, -1, '[\"Sınırsız araç\", \"7/24 destek\", \"Bulut yedekleme\", \"Öncelikli destek\", \"API erişimi\", \"Özel entegrasyon\", \"Ömür boyu güncelleme\"]', TRUE)
    ");
    
    // User memberships tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_memberships (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            package_id INT NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
            payment_reference VARCHAR(100),
            auto_renew BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (package_id) REFERENCES membership_packages(id)
        )
    ");
    
    // Vehicles tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vehicles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            brand VARCHAR(50) NOT NULL,
            model VARCHAR(50) NOT NULL,
            year INT NOT NULL,
            plate VARCHAR(20) UNIQUE NOT NULL,
            color VARCHAR(30),
            fuel_type VARCHAR(20),
            engine_size VARCHAR(20),
            transmission VARCHAR(20),
            image_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Test araçları
    $pdo->exec("
        INSERT IGNORE INTO vehicles (id, user_id, brand, model, year, plate, color, fuel_type) VALUES
        (1, 1, 'Toyota', 'Corolla', 2020, '34ABC123', 'Beyaz', 'Benzin'),
        (2, 1, 'Honda', 'Civic', 2019, '06XYZ789', 'Siyah', 'Benzin'),
        (3, 1, 'Volkswagen', 'Golf', 2021, '35DEF456', 'Gri', 'Dizel')
    ");
    
    // Basic campaigns tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS campaigns (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            start_date DATE,
            end_date DATE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Test kampanyaları
    $pdo->exec("
        INSERT IGNORE INTO campaigns (id, title, description, image_url, is_active) VALUES
        (1, 'Kış Lastiği Kampanyası', 'Kış lastiklerinde %20 indirim', 'https://example.com/winter-tire.jpg', TRUE),
        (2, 'Motor Yağı Değişimi', 'Motor yağı değişiminde ücretsiz filtre', 'https://example.com/engine-oil.jpg', TRUE),
        (3, 'Volkswagen Özel Servis', 'Volkswagen araçlarda özel servis fırsatı', 'https://example.com/vw-service.jpg', TRUE)
    ");
    
    // Basic sliders tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sliders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            link_url VARCHAR(255),
            order_index INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Test sliderları
    $pdo->exec("
        INSERT IGNORE INTO sliders (id, title, description, image_url, is_active) VALUES
        (1, 'Araç Takip Sistemi', 'Araçlarınızı kolayca takip edin', 'https://example.com/slider1.jpg', TRUE),
        (2, 'Kurumsal Çözümler', 'İşletmeniz için özel çözümler', 'https://example.com/slider2.jpg', TRUE)
    ");
    
    // Basic news tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS news (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            image_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Test haberleri
    $pdo->exec("
        INSERT IGNORE INTO news (id, title, content, image_url, is_active) VALUES
        (1, 'Yeni Özellikler Eklendi', 'Uygulamamıza yeni özellikler eklendi', 'https://example.com/news1.jpg', TRUE),
        (2, 'Güvenlik Güncellemesi', 'Güvenlik güncellemesi yayınlandı', 'https://example.com/news2.jpg', TRUE),
        (3, 'Mobil Uygulama', 'Mobil uygulamamız güncellendi', 'https://example.com/news3.jpg', TRUE),
        (4, 'Yeni Servis Noktaları', 'Yeni servis noktaları açıldı', 'https://example.com/news4.jpg', TRUE)
    ");
    
    // Basic notifications tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Basic reminders tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reminders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            vehicle_id INT,
            type ENUM('service', 'insurance', 'inspection', 'kasko', 'tire', 'oil_change') NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            reminder_date DATE NOT NULL,
            is_completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        )
    ");
    
    // Test hatırlatmaları
    $pdo->exec("
        INSERT IGNORE INTO reminders (id, user_id, vehicle_id, type, title, description, reminder_date) VALUES
        (1, 1, 1, 'service', 'Periyodik Bakım', 'Araç periyodik bakım zamanı', '2025-08-15'),
        (2, 1, 2, 'insurance', 'Sigorta Yenileme', 'Araç sigortası yenileme zamanı', '2025-07-30'),
        (3, 1, 3, 'inspection', 'Muayene', 'Araç muayene zamanı', '2025-09-10')
    ");
    
    // Basic quotes tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quotes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            vehicle_id INT,
            service_type VARCHAR(100) NOT NULL,
            description TEXT,
            status ENUM('pending', 'responded', 'accepted', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        )
    ");
    
    // Quote responses tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quote_responses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quote_id INT NOT NULL,
            company_name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quote_id) REFERENCES quotes(id)
        )
    ");
    
    echo "✅ Tüm tablolar başarıyla oluşturuldu!\n";
    echo "✅ Test verileri eklendi!\n";
    echo "\n📊 Oluşturulan tablolar:\n";
    echo "- user_roles (4 rol)\n";
    echo "- users (1 test kullanıcısı)\n";
    echo "- membership_packages (5 paket)\n";
    echo "- user_memberships\n";
    echo "- vehicles (3 test aracı)\n";
    echo "- campaigns (3 kampanya)\n";
    echo "- sliders (2 slider)\n";
    echo "- news (4 haber)\n";
    echo "- notifications\n";
    echo "- reminders (3 hatırlatma)\n";
    echo "- quotes\n";
    echo "- quote_responses\n";
    
} catch (PDOException $e) {
    echo "❌ Database hatası: " . $e->getMessage() . "\n";
}
?> 
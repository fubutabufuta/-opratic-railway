<?php
// Minimal database kurulumu
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
    
    echo "✅ Database '$dbname' hazır\n";
    
    // Basit user_roles tablosu
    $pdo->exec("DROP TABLE IF EXISTS user_roles");
    $pdo->exec("
        CREATE TABLE user_roles (
            id INT PRIMARY KEY,
            role_name VARCHAR(50) NOT NULL
        )
    ");
    
    $pdo->exec("
        INSERT INTO user_roles (id, role_name) VALUES
        (1, 'admin'),
        (2, 'b2b'),
        (3, 'user'),
        (4, 'corporate_user')
    ");
    
    // Basit users tablosu
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("
        CREATE TABLE users (
            id INT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            password VARCHAR(255),
            role_id INT DEFAULT 3
        )
    ");
    
    $pdo->exec("
        INSERT INTO users (id, name, email, phone, password, role_id) VALUES
        (1, 'Test User', 'test@test.com', '+905551234567', 'password', 3)
    ");
    
    // Basit membership_packages tablosu
    $pdo->exec("DROP TABLE IF EXISTS membership_packages");
    $pdo->exec("
        CREATE TABLE membership_packages (
            id INT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            duration_months INT NOT NULL,
            max_vehicles INT DEFAULT -1,
            features TEXT,
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    
    $pdo->exec("
        INSERT INTO membership_packages (id, name, description, price, duration_months, max_vehicles, features, is_active) VALUES
        (1, 'Aylık Plan', 'Aylık kurumsal üyelik', 99.99, 1, -1, 'Sınırsız araç, 7/24 destek, Bulut yedekleme', TRUE),
        (2, '3 Aylık Plan', '3 aylık kurumsal üyelik', 249.99, 3, -1, 'Sınırsız araç, 7/24 destek, Bulut yedekleme, Öncelikli destek', TRUE),
        (3, '6 Aylık Plan', '6 aylık kurumsal üyelik', 449.99, 6, -1, 'Sınırsız araç, 7/24 destek, Bulut yedekleme, Öncelikli destek, API erişimi', TRUE),
        (4, 'Yıllık Plan', 'Yıllık kurumsal üyelik', 799.99, 12, -1, 'Sınırsız araç, 7/24 destek, Bulut yedekleme, Öncelikli destek, API erişimi, Özel entegrasyon', TRUE),
        (5, 'Ömür Boyu', 'Ömür boyu kurumsal üyelik', 2999.99, 999, -1, 'Sınırsız araç, 7/24 destek, Bulut yedekleme, Öncelikli destek, API erişimi, Özel entegrasyon, Ömür boyu güncelleme', TRUE)
    ");
    
    // Basit user_memberships tablosu
    $pdo->exec("DROP TABLE IF EXISTS user_memberships");
    $pdo->exec("
        CREATE TABLE user_memberships (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            package_id INT NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            payment_status VARCHAR(20) DEFAULT 'pending',
            payment_reference VARCHAR(100),
            auto_renew BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    
    // Basit vehicles tablosu
    $pdo->exec("DROP TABLE IF EXISTS vehicles");
    $pdo->exec("
        CREATE TABLE vehicles (
            id INT PRIMARY KEY,
            user_id INT NOT NULL,
            brand VARCHAR(50) NOT NULL,
            model VARCHAR(50) NOT NULL,
            year INT NOT NULL,
            plate VARCHAR(20) NOT NULL,
            color VARCHAR(30),
            fuel_type VARCHAR(20),
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    
    $pdo->exec("
        INSERT INTO vehicles (id, user_id, brand, model, year, plate, color, fuel_type) VALUES
        (1, 1, 'Toyota', 'Corolla', 2020, '34ABC123', 'Beyaz', 'Benzin'),
        (2, 1, 'Honda', 'Civic', 2019, '06XYZ789', 'Siyah', 'Benzin'),
        (3, 1, 'Volkswagen', 'Golf', 2021, '35DEF456', 'Gri', 'Dizel')
    ");
    
    // Diğer basit tablolar
    $pdo->exec("DROP TABLE IF EXISTS campaigns");
    $pdo->exec("
        CREATE TABLE campaigns (
            id INT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    
    $pdo->exec("
        INSERT INTO campaigns (id, title, description, image_url, is_active) VALUES
        (1, 'Kış Lastiği Kampanyası', 'Kış lastiklerinde %20 indirim', 'https://example.com/winter-tire.jpg', TRUE),
        (2, 'Motor Yağı Değişimi', 'Motor yağı değişiminde ücretsiz filtre', 'https://example.com/engine-oil.jpg', TRUE),
        (3, 'Volkswagen Özel Servis', 'Volkswagen araçlarda özel servis fırsatı', 'https://example.com/vw-service.jpg', TRUE)
    ");
    
    $pdo->exec("DROP TABLE IF EXISTS sliders");
    $pdo->exec("
        CREATE TABLE sliders (
            id INT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    
    $pdo->exec("
        INSERT INTO sliders (id, title, description, image_url, is_active) VALUES
        (1, 'Araç Takip Sistemi', 'Araçlarınızı kolayca takip edin', 'https://example.com/slider1.jpg', TRUE),
        (2, 'Kurumsal Çözümler', 'İşletmeniz için özel çözümler', 'https://example.com/slider2.jpg', TRUE)
    ");
    
    $pdo->exec("DROP TABLE IF EXISTS news");
    $pdo->exec("
        CREATE TABLE news (
            id INT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            image_url VARCHAR(255),
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    
    $pdo->exec("
        INSERT INTO news (id, title, content, image_url, is_active) VALUES
        (1, 'Yeni Özellikler Eklendi', 'Uygulamamıza yeni özellikler eklendi', 'https://example.com/news1.jpg', TRUE),
        (2, 'Güvenlik Güncellemesi', 'Güvenlik güncellemesi yayınlandı', 'https://example.com/news2.jpg', TRUE),
        (3, 'Mobil Uygulama', 'Mobil uygulamamız güncellendi', 'https://example.com/news3.jpg', TRUE),
        (4, 'Yeni Servis Noktaları', 'Yeni servis noktaları açıldı', 'https://example.com/news4.jpg', TRUE)
    ");
    
    $pdo->exec("DROP TABLE IF EXISTS notifications");
    $pdo->exec("
        CREATE TABLE notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(20) DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE
        )
    ");
    
    $pdo->exec("DROP TABLE IF EXISTS reminders");
    $pdo->exec("
        CREATE TABLE reminders (
            id INT PRIMARY KEY,
            user_id INT NOT NULL,
            vehicle_id INT,
            type VARCHAR(20) NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            reminder_date DATE NOT NULL,
            is_completed BOOLEAN DEFAULT FALSE
        )
    ");
    
    $pdo->exec("
        INSERT INTO reminders (id, user_id, vehicle_id, type, title, description, reminder_date) VALUES
        (1, 1, 1, 'service', 'Periyodik Bakım', 'Araç periyodik bakım zamanı', '2025-08-15'),
        (2, 1, 2, 'insurance', 'Sigorta Yenileme', 'Araç sigortası yenileme zamanı', '2025-07-30'),
        (3, 1, 3, 'inspection', 'Muayene', 'Araç muayene zamanı', '2025-09-10')
    ");
    
    $pdo->exec("DROP TABLE IF EXISTS quotes");
    $pdo->exec("
        CREATE TABLE quotes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            vehicle_id INT,
            service_type VARCHAR(100) NOT NULL,
            description TEXT,
            status VARCHAR(20) DEFAULT 'pending'
        )
    ");
    
    $pdo->exec("DROP TABLE IF EXISTS quote_responses");
    $pdo->exec("
        CREATE TABLE quote_responses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quote_id INT NOT NULL,
            company_name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT
        )
    ");
    
    echo "✅ Tüm tablolar başarıyla oluşturuldu!\n";
    echo "✅ Test verileri eklendi!\n";
    echo "\n📊 Hazır tablolar:\n";
    echo "- user_roles ✓\n";
    echo "- users ✓\n";
    echo "- membership_packages ✓\n";
    echo "- user_memberships ✓\n";
    echo "- vehicles ✓\n";
    echo "- campaigns ✓\n";
    echo "- sliders ✓\n";
    echo "- news ✓\n";
    echo "- notifications ✓\n";
    echo "- reminders ✓\n";
    echo "- quotes ✓\n";
    echo "- quote_responses ✓\n";
    
} catch (PDOException $e) {
    echo "❌ Database hatası: " . $e->getMessage() . "\n";
}
?> 
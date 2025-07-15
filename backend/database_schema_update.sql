-- ============================================
-- OtoAsist Kapsamlı Sistem Veritabanı Şeması
-- ============================================

-- 1. KULLANICI ROLLERİ VE YETKİLER
-- ============================================

-- Rolleri tanımla
CREATE TABLE user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO
    user_roles (name, description)
VALUES (
        'user',
        'Normal kullanıcı - araç sahipleri (max 4 araç)'
    ),
    (
        'service_provider',
        'Servis sağlayıcı - işletmeler'
    ),
    (
        'admin', 
        'Sistem yöneticisi'
    ),
    (
        'corporate_user',
        'Kurumsal kullanıcı - sınırsız araç ekleme'
    );

-- Users tablosuna role_id ekle
ALTER TABLE users ADD COLUMN role_id INT DEFAULT 1;

ALTER TABLE users
ADD FOREIGN KEY (role_id) REFERENCES user_roles (id);

-- 2. KURUMSAL ÜYELİK PAKETLERİ
-- ============================================

CREATE TABLE membership_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration_months INT NOT NULL,
    max_vehicles INT DEFAULT -1, -- -1 = sınırsız
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO
    membership_packages (
        name,
        description,
        price,
        duration_months,
        max_vehicles,
        features
    )
VALUES (
        'Aylık Kurumsal',
        'Aylık kurumsal paket - sınırsız araç',
        99.99,
        1,
        -1,
        JSON_ARRAY('Sınırsız araç ekleme', 'Öncelikli destek', 'Toplu araç yönetimi')
    ),
    (
        '3 Aylık Kurumsal',
        '3 aylık kurumsal paket - %10 indirim',
        269.99,
        3,
        -1,
        JSON_ARRAY('Sınırsız araç ekleme', 'Öncelikli destek', 'Toplu araç yönetimi', 'Raporlama')
    ),
    (
        '6 Aylık Kurumsal',
        '6 aylık kurumsal paket - %15 indirim',
        509.99,
        6,
        -1,
        JSON_ARRAY('Sınırsız araç ekleme', 'Öncelikli destek', 'Toplu araç yönetimi', 'Raporlama', 'API erişimi')
    ),
    (
        'Yıllık Kurumsal',
        'Yıllık kurumsal paket - %20 indirim',
        959.99,
        12,
        -1,
        JSON_ARRAY('Sınırsız araç ekleme', 'Öncelikli destek', 'Toplu araç yönetimi', 'Raporlama', 'API erişimi', 'Özel eğitim')
    ),
    (
        'Ömür Boyu Kurumsal',
        'Tek seferlik ödeme - ömür boyu kurumsal paket',
        4999.99,
        999,
        -1,
        JSON_ARRAY('Sınırsız araç ekleme', 'Öncelikli destek', 'Toplu araç yönetimi', 'Raporlama', 'API erişimi', 'Özel eğitim', 'Özel logo')
    );

-- 3. KULLANICI ÜYELİKLERİ
-- ============================================

CREATE TABLE user_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    payment_status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
    payment_reference VARCHAR(255),
    auto_renew BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES membership_packages (id)
);

-- 4. ARAÇ LİMİTİ KONTROLÜ VE MEMBERSHIP FONKSİYONLARI
-- ============================================

-- Kullanıcının aktif üyeliğini kontrol etme fonksiyonu
DELIMITER //
CREATE FUNCTION GetUserMaxVehicles(user_id INT) RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE max_vehicles INT DEFAULT 4;
    DECLARE user_role_id INT;
    DECLARE active_membership_vehicles INT;
    
    -- Kullanıcının rolünü al
    SELECT role_id INTO user_role_id 
    FROM users 
    WHERE id = user_id;
    
    -- Kurumsal kullanıcı ise sınırsız
    IF user_role_id = 4 THEN
        RETURN -1; -- Sınırsız
    END IF;
    
    -- Aktif üyeliği kontrol et
    SELECT mp.max_vehicles INTO active_membership_vehicles
    FROM user_memberships um
    JOIN membership_packages mp ON um.package_id = mp.id
    WHERE um.user_id = user_id 
    AND um.is_active = TRUE 
    AND um.payment_status = 'paid'
    AND NOW() BETWEEN um.start_date AND um.end_date
    ORDER BY um.created_at DESC
    LIMIT 1;
    
    -- Aktif üyelik varsa o paketteki limiti döndür
    IF active_membership_vehicles IS NOT NULL THEN
        RETURN active_membership_vehicles;
    END IF;
    
    -- Varsayılan normal kullanıcı limiti
    RETURN 4;
END//
DELIMITER ;

-- Kullanıcının kalan üyelik süresini hesaplama fonksiyonu
DELIMITER //
CREATE FUNCTION GetUserMembershipDaysRemaining(user_id INT) RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE days_remaining INT DEFAULT 0;
    
    SELECT DATEDIFF(um.end_date, NOW()) INTO days_remaining
    FROM user_memberships um
    WHERE um.user_id = user_id 
    AND um.is_active = TRUE 
    AND um.payment_status = 'paid'
    AND NOW() BETWEEN um.start_date AND um.end_date
    ORDER BY um.created_at DESC
    LIMIT 1;
    
    RETURN COALESCE(days_remaining, 0);
END//
DELIMITER ;

-- Kullanıcının üyelik türünü alma fonksiyonu
DELIMITER //
CREATE FUNCTION GetUserMembershipType(user_id INT) RETURNS VARCHAR(100)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE membership_type VARCHAR(100) DEFAULT 'Normal';
    DECLARE user_role_id INT;
    
    -- Kullanıcının rolünü al
    SELECT role_id INTO user_role_id 
    FROM users 
    WHERE id = user_id;
    
    -- Kurumsal kullanıcı ise
    IF user_role_id = 4 THEN
        RETURN 'Kurumsal';
    END IF;
    
    -- Aktif üyeliği kontrol et
    SELECT mp.name INTO membership_type
    FROM user_memberships um
    JOIN membership_packages mp ON um.package_id = mp.id
    WHERE um.user_id = user_id 
    AND um.is_active = TRUE 
    AND um.payment_status = 'paid'
    AND NOW() BETWEEN um.start_date AND um.end_date
    ORDER BY um.created_at DESC
    LIMIT 1;
    
    RETURN COALESCE(membership_type, 'Normal');
END//
DELIMITER ;

-- 5. HİZMET TÜRLERİ
-- ============================================

CREATE TABLE service_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO
    service_types (name, description, icon)
VALUES (
        'Servis',
        'Genel araç servisi ve bakım',
        'build'
    ),
    (
        'Sigorta',
        'Araç sigortası hizmetleri',
        'security'
    ),
    (
        'Kasko',
        'Kasko sigortası',
        'shield'
    ),
    (
        'Lastik',
        'Lastik değişimi ve tamiri',
        'tire_repair'
    ),
    (
        'Yağ Değişimi',
        'Motor yağı değişimi',
        'oil_barrel'
    ),
    (
        'Ekspertiz',
        'Araç ekspertizi',
        'fact_check'
    ),
    (
        'Çekici',
        'Araç çekici hizmeti',
        'local_shipping'
    ),
    (
        'Cam Tamiri',
        'Araç cam tamiri',
        'window'
    );

-- 6. ABONELİK PAKETLERİ
-- ============================================

CREATE TABLE subscription_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration_months INT NOT NULL,
    max_requests_per_month INT DEFAULT -1, -- -1 = unlimited
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO
    subscription_packages (
        name,
        description,
        price,
        duration_months,
        max_requests_per_month
    )
VALUES (
        'Başlangıç',
        'Aylık 50 teklif talebi',
        299.99,
        1,
        50
    ),
    (
        'Standart',
        'Aylık 200 teklif talebi',
        899.99,
        1,
        200
    ),
    (
        'Premium',
        'Sınırsız teklif talebi + reklam',
        1999.99,
        1,
        -1
    ),
    (
        'Yıllık Premium',
        'Yıllık sınırsız + %20 indirim',
        19199.99,
        12,
        -1
    );

-- Paket - hizmet ilişkisi
CREATE TABLE package_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    service_type_id INT NOT NULL,
    FOREIGN KEY (package_id) REFERENCES subscription_packages (id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types (id) ON DELETE CASCADE,
    UNIQUE KEY unique_package_service (package_id, service_type_id)
);

-- 7. SERVİS SAĞLAYICILAR
-- ============================================

CREATE TABLE service_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    tax_number VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(150),
    website VARCHAR(200),
    description TEXT,
    logo_url VARCHAR(500),
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- Servis sağlayıcı - hizmet türü ilişkisi
CREATE TABLE provider_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    service_type_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers (id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types (id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_service (provider_id, service_type_id)
);

-- 8. ABONELİKLER
-- ============================================

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    package_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    auto_renew BOOLEAN DEFAULT FALSE,
    payment_status ENUM(
        'pending',
        'paid',
        'failed',
        'cancelled'
    ) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers (id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES subscription_packages (id)
);

-- 9. TEKLİF TALEPLERİ
-- ============================================

CREATE TABLE quote_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_type_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    user_notes TEXT,
    share_phone BOOLEAN DEFAULT FALSE,
    status ENUM(
        'pending',
        'active',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types (id)
);

-- 10. TEKLİFLER
-- ============================================

CREATE TABLE quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    provider_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2),
    estimated_duration VARCHAR(100),
    valid_until DATETIME,
    status ENUM(
        'pending',
        'accepted',
        'rejected',
        'expired'
    ) DEFAULT 'pending',
    provider_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES quote_requests (id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES service_providers (id) ON DELETE CASCADE
);

-- 11. SLIDER YÖNETİMİ
-- ============================================

CREATE TABLE sliders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_url VARCHAR(500) NOT NULL,
    link_type ENUM('url', 'page', 'news') DEFAULT 'url',
    link_value VARCHAR(500),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATETIME,
    end_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Slider sayfaları
CREATE TABLE slider_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slider_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    meta_title VARCHAR(200),
    meta_description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (slider_id) REFERENCES sliders (id) ON DELETE CASCADE
);

-- 12. KAMPANYA YÖNETİMİ
-- ============================================

CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    content TEXT,
    image_url VARCHAR(500),
    start_date DATETIME,
    end_date DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    is_approved BOOLEAN DEFAULT FALSE,
    admin_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers (id) ON DELETE SET NULL
);

-- 13. REKLAM TALEPLERİ
-- ============================================

CREATE TABLE ad_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    type ENUM(
        'slider',
        'sponsored_news',
        'campaign'
    ) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    content TEXT,
    image_url VARCHAR(500),
    target_url VARCHAR(500),
    budget DECIMAL(10, 2),
    start_date DATETIME,
    end_date DATETIME,
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'active',
        'completed'
    ) DEFAULT 'pending',
    admin_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers (id) ON DELETE CASCADE
);

-- 14. SİSTEM AYARLARI
-- ============================================

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO
    system_settings (
        setting_key,
        setting_value,
        description
    )
VALUES (
        'commission_rate',
        '10.00',
        'Sistemden alınan komisyon oranı (%)'
    ),
    (
        'min_quote_amount',
        '100.00',
        'Minimum teklif tutarı'
    ),
    (
        'max_quote_amount',
        '50000.00',
        'Maksimum teklif tutarı'
    ),
    (
        'quote_validity_days',
        '7',
        'Tekliflerin geçerlilik süresi (gün)'
    ),
    (
        'auto_approve_providers',
        'false',
        'Servis sağlayıcıları otomatik onayla'
    );

-- 15. AKTİVİTE LOGLARI
-- ============================================

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);

-- 16. BİLDİRİMLER
-- ============================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM(
        'info',
        'success',
        'warning',
        'error'
    ) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- İndeksler
CREATE INDEX idx_users_role ON users (role_id);

CREATE INDEX idx_providers_city ON service_providers (city);

CREATE INDEX idx_subscriptions_active ON subscriptions (is_active, end_date);

CREATE INDEX idx_quotes_status ON quotes (status);

CREATE INDEX idx_quote_requests_status ON quote_requests (status);

CREATE INDEX idx_notifications_user_read ON notifications (user_id, is_read);

-- Örnek admin kullanıcısı
INSERT INTO
    users (
        phone,
        password,
        full_name,
        role_id
    )
VALUES (
        '+905551234567',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Admin User',
        3
    );
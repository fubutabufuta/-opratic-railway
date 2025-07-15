-- Database fonksiyonlarını oluştur
USE otoasist;

-- GetUserMaxVehicles fonksiyonu
DROP FUNCTION IF EXISTS GetUserMaxVehicles;
DELIMITER //
CREATE FUNCTION GetUserMaxVehicles(user_id INT) RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE max_vehicles INT DEFAULT 4;
    DECLARE user_role_id INT;
    
    -- Kullanıcının role_id'sini al
    SELECT role_id INTO user_role_id FROM users WHERE id = user_id;
    
    -- Eğer kurumsal üye ise (role_id = 4) sınırsız araç
    IF user_role_id = 4 THEN
        SET max_vehicles = -1;
    ELSE
        -- Normal üye için 4 araç limiti
        SET max_vehicles = 4;
    END IF;
    
    RETURN max_vehicles;
END //
DELIMITER ;

-- GetUserMembershipType fonksiyonu
DROP FUNCTION IF EXISTS GetUserMembershipType;
DELIMITER //
CREATE FUNCTION GetUserMembershipType(user_id INT) RETURNS VARCHAR(50)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE membership_type VARCHAR(50) DEFAULT 'Normal Üyelik';
    DECLARE user_role_id INT;
    DECLARE package_name VARCHAR(100);
    
    -- Kullanıcının role_id'sini al
    SELECT role_id INTO user_role_id FROM users WHERE id = user_id;
    
    -- Eğer kurumsal üye ise aktif paket bilgisini al
    IF user_role_id = 4 THEN
        SELECT mp.name INTO package_name 
        FROM user_memberships um
        JOIN membership_packages mp ON um.package_id = mp.id
        WHERE um.user_id = user_id 
        AND um.is_active = TRUE 
        AND um.payment_status = 'paid'
        AND NOW() BETWEEN um.start_date AND um.end_date
        ORDER BY um.created_at DESC
        LIMIT 1;
        
        IF package_name IS NOT NULL THEN
            SET membership_type = package_name;
        ELSE
            SET membership_type = 'Kurumsal Üyelik';
        END IF;
    END IF;
    
    RETURN membership_type;
END //
DELIMITER ;

-- GetUserMembershipDaysRemaining fonksiyonu
DROP FUNCTION IF EXISTS GetUserMembershipDaysRemaining;
DELIMITER //
CREATE FUNCTION GetUserMembershipDaysRemaining(user_id INT) RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE days_remaining INT DEFAULT 0;
    DECLARE user_role_id INT;
    DECLARE end_date DATETIME;
    
    -- Kullanıcının role_id'sini al
    SELECT role_id INTO user_role_id FROM users WHERE id = user_id;
    
    -- Eğer kurumsal üye ise aktif üyelik süresini kontrol et
    IF user_role_id = 4 THEN
        SELECT um.end_date INTO end_date
        FROM user_memberships um
        WHERE um.user_id = user_id 
        AND um.is_active = TRUE 
        AND um.payment_status = 'paid'
        AND NOW() BETWEEN um.start_date AND um.end_date
        ORDER BY um.created_at DESC
        LIMIT 1;
        
        IF end_date IS NOT NULL THEN
            SET days_remaining = DATEDIFF(end_date, NOW());
            IF days_remaining < 0 THEN
                SET days_remaining = 0;
            END IF;
        END IF;
    END IF;
    
    RETURN days_remaining;
END //
DELIMITER ;

-- Test için bir kullanıcı oluştur (eğer yoksa)
INSERT IGNORE INTO users (id, name, email, phone, password, role_id, created_at, updated_at)
VALUES (1, 'Test User', 'test@test.com', '+905551234567', 'password', 3, NOW(), NOW());

-- Test için örnek membership packages
INSERT IGNORE INTO membership_packages (id, name, description, price, duration_months, max_vehicles, features, is_active) VALUES
(1, 'Aylık Plan', 'Aylık kurumsal üyelik', 99.99, 1, -1, '["Sınırsız araç", "7/24 destek", "Bulut yedekleme"]', TRUE),
(2, '3 Aylık Plan', '3 aylık kurumsal üyelik', 249.99, 3, -1, '["Sınırsız araç", "7/24 destek", "Bulut yedekleme", "Öncelikli destek"]', TRUE),
(3, '6 Aylık Plan', '6 aylık kurumsal üyelik', 449.99, 6, -1, '["Sınırsız araç", "7/24 destek", "Bulut yedekleme", "Öncelikli destek", "API erişimi"]', TRUE),
(4, 'Yıllık Plan', 'Yıllık kurumsal üyelik', 799.99, 12, -1, '["Sınırsız araç", "7/24 destek", "Bulut yedekleme", "Öncelikli destek", "API erişimi", "Özel entegrasyon"]', TRUE),
(5, 'Ömür Boyu', 'Ömür boyu kurumsal üyelik', 2999.99, 999, -1, '["Sınırsız araç", "7/24 destek", "Bulut yedekleme", "Öncelikli destek", "API erişimi", "Özel entegrasyon", "Ömür boyu güncelleme"]', TRUE); 
<?php
require_once 'config/database.php';
session_start();

// Include all CRUD functions
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_token']) && !empty($_SESSION['admin_token']);
}

function authenticateAdmin($token)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            SELECT u.id, u.full_name, u.email, u.phone, u.role_id 
            FROM users u 
            WHERE u.phone = ? AND u.role_id = 3
        ");

        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

// Handle POST actions
if ($_POST) {
    $action = $_POST['action'] ?? '';

    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'login':
                $token = $_POST['token'] ?? '';

                if ($admin = authenticateAdmin($token)) {
                    $_SESSION['admin_token'] = $token;
                    $_SESSION['admin_user'] = $admin;
                    header('Location: admin_modern.php');
                    exit;
                } else {
                    $error = "Geçersiz admin token!";
                }
                break;

            // User CRUD
            case 'create_user':
                if (isAdminLoggedIn()) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $password, $_POST['role_id']]);
                    $message = 'Kullanıcı başarıyla eklendi';
                }
                break;

            case 'update_user':
                if (isAdminLoggedIn()) {
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, role_id = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $password, $_POST['role_id'], $_POST['user_id']]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role_id = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['role_id'], $_POST['user_id']]);
                    }
                    $message = 'Kullanıcı başarıyla güncellendi';
                }
                break;

            case 'delete_user':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role_id != 3");
                    $stmt->execute([$_POST['user_id']]);
                    $message = 'Kullanıcı başarıyla silindi';
                }
                break;

            // Provider CRUD
            case 'create_provider':
                if (isAdminLoggedIn()) {
                    // Users tablosuna alanları ekle (eğer yoksa)
                    try {
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS company_name VARCHAR(255) NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS services TEXT NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS provider_status VARCHAR(50) DEFAULT 'active'");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS description TEXT NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS working_hours TEXT NULL");
                    } catch (Exception $e) {
                        // Alanlar zaten varsa hata alma
                    }

                    // Direkt users tablosuna servis sağlayıcı oluştur
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        INSERT INTO users 
                        (full_name, email, phone, password, role_id, company_name, city, services, rating, provider_status, description, address) 
                        VALUES (?, ?, ?, ?, 2, ?, ?, ?, 5.0, 'active', ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['company_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $password,
                        $_POST['company_name'],
                        $_POST['city'],
                        $_POST['services'],
                        $_POST['description'],
                        $_POST['address'] ?? ''
                    ]);
                    $message = 'Servis sağlayıcı başarıyla eklendi';
                }
                break;

            case 'update_provider':
                if (isAdminLoggedIn()) {
                    // Users tablosundaki servis sağlayıcıyı güncelle
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, phone = ?, company_name = ?, city = ?, services = ?, description = ?, address = ?
                        WHERE id = ? AND role_id = 2
                    ");
                    $stmt->execute([
                        $_POST['company_name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['company_name'],
                        $_POST['city'],
                        $_POST['services'],
                        $_POST['description'],
                        $_POST['address'] ?? '',
                        $_POST['provider_id']
                    ]);
                    $message = 'Servis sağlayıcı güncellendi';
                }
                break;

            case 'migrate_to_users_table':
                if (isAdminLoggedIn()) {
                    // Users tablosuna alanları ekle
                    try {
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS company_name VARCHAR(255) NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS services TEXT NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS provider_status VARCHAR(50) DEFAULT 'active'");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS description TEXT NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL");
                        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS working_hours TEXT NULL");

                        // service_providers tablosundaki verileri users tablosuna aktar
                        $conn->exec("
                            UPDATE users u 
                            JOIN service_providers sp ON u.id = sp.user_id 
                            SET 
                                u.company_name = sp.company_name,
                                u.city = sp.city,
                                u.services = sp.services,
                                u.rating = sp.rating,
                                u.provider_status = sp.status,
                                u.description = sp.description,
                                u.address = sp.address,
                                u.working_hours = sp.working_hours,
                                u.role_id = 2
                            WHERE sp.user_id IS NOT NULL
                        ");

                        // Demo kullanıcıyı servis sağlayıcı olarak ayarla
                        $conn->exec("
                            UPDATE users 
                            SET 
                                role_id = 2,
                                company_name = 'Demo Servis',
                                city = 'Lefkoşa',
                                services = 'Servis ve Bakım',
                                rating = 4.5,
                                provider_status = 'active',
                                description = 'Demo servis sağlayıcı hesabı',
                                address = 'Lefkoşa, KKTC',
                                working_hours = '08:00-18:00'
                            WHERE phone = '+905551234567'
                        ");

                        $message = 'Servis sağlayıcılar users tablosuna başarıyla aktarıldı!';
                    } catch (Exception $e) {
                        $error = 'Migrasyon hatası: ' . $e->getMessage();
                    }
                }
                break;

            case 'delete_provider':
                if (isAdminLoggedIn()) {
                    // Önce provider'ın user_id'sini al
                    $stmt = $conn->prepare("SELECT user_id FROM service_providers WHERE id = ?");
                    $stmt->execute([$_POST['provider_id']]);
                    $user_id = $stmt->fetchColumn();

                    // Provider'ı sil
                    $stmt = $conn->prepare("DELETE FROM service_providers WHERE id = ?");
                    $stmt->execute([$_POST['provider_id']]);

                    // User'ı da sil
                    if ($user_id) {
                        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role_id = 2");
                        $stmt->execute([$user_id]);
                    }
                    $message = 'Servis sağlayıcı silindi';
                }
                break;

            // Package CRUD
            case 'create_package':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("INSERT INTO subscription_packages (name, description, price, duration_months, max_requests_per_month) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration_months'], $_POST['max_requests_per_month'] ?: null]);
                    $message = 'Paket başarıyla eklendi';
                }
                break;

            case 'update_package':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE subscription_packages SET name = ?, description = ?, price = ?, duration_months = ?, max_requests_per_month = ? WHERE id = ?");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration_months'], $_POST['max_requests_per_month'] ?: null, $_POST['package_id']]);
                    $message = 'Paket başarıyla güncellendi';
                }
                break;

            case 'delete_package':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM subscription_packages WHERE id = ?");
                    $stmt->execute([$_POST['package_id']]);
                    $message = 'Paket başarıyla silindi';
                }
                break;

            // News CRUD
            case 'create_news':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("INSERT INTO news (title, content, excerpt, image_url, category, is_featured, is_sponsored, author) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['content'],
                        $_POST['excerpt'],
                        $_POST['image_url'],
                        $_POST['category'],
                        isset($_POST['is_featured']) ? 1 : 0,
                        isset($_POST['is_sponsored']) ? 1 : 0,
                        $_SESSION['admin_user']['full_name']
                    ]);
                    $message = 'Haber başarıyla eklendi';
                }
                break;

            case 'update_news':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, excerpt = ?, image_url = ?, category = ?, is_featured = ?, is_sponsored = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['content'],
                        $_POST['excerpt'],
                        $_POST['image_url'],
                        $_POST['category'],
                        isset($_POST['is_featured']) ? 1 : 0,
                        isset($_POST['is_sponsored']) ? 1 : 0,
                        $_POST['news_id']
                    ]);
                    $message = 'Haber başarıyla güncellendi';
                }
                break;

            case 'delete_news':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
                    $stmt->execute([$_POST['news_id']]);
                    $message = 'Haber başarıyla silindi';
                }
                break;

            // Slider CRUD
            case 'create_slider':
                if (isAdminLoggedIn()) {
                    $conn->exec("CREATE TABLE IF NOT EXISTS sliders (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), description TEXT, image_url VARCHAR(500), link_url VARCHAR(500), sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                    $stmt = $conn->prepare("INSERT INTO sliders (title, description, image_url, link_url, sort_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['image_url'], $_POST['link_url'], $_POST['sort_order']]);
                    $message = 'Slider başarıyla eklendi';
                }
                break;

            case 'update_slider':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE sliders SET title = ?, description = ?, image_url = ?, link_url = ?, sort_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['image_url'], $_POST['link_url'], $_POST['sort_order'], isset($_POST['is_active']) ? 1 : 0, $_POST['slider_id']]);
                    $message = 'Slider başarıyla güncellendi';
                }
                break;

            case 'delete_slider':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM sliders WHERE id = ?");
                    $stmt->execute([$_POST['slider_id']]);
                    $message = 'Slider başarıyla silindi';
                }
                break;

            // Quote CRUD
            case 'update_quote':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE quote_requests SET title = ?, description = ?, status = ? WHERE id = ?");
                    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['status'], $_POST['quote_id']]);
                    $message = 'Teklif başarıyla güncellendi';
                }
                break;

            case 'delete_quote_request':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM quote_requests WHERE id = ?");
                    $stmt->execute([$_POST['quote_request_id']]);
                    $message = 'Teklif talebi başarıyla silindi';
                }
                break;

            // Notification CRUD
            case 'create_notification':
                if (isAdminLoggedIn()) {
                    // Target user list based on criteria
                    $targetUsers = [];
                    $targetType = $_POST['target_type'];
                    $targetValue = $_POST['target_value'] ?? null;

                    switch ($targetType) {
                        case 'all':
                            $stmt = $conn->query("SELECT id FROM users WHERE role_id = 1");
                            $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            break;
                        case 'brand':
                            $stmt = $conn->prepare("SELECT DISTINCT u.id FROM users u JOIN vehicles v ON u.id = v.user_id WHERE v.brand = ?");
                            $stmt->execute([$targetValue]);
                            $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            break;
                        case 'model':
                            $stmt = $conn->prepare("SELECT DISTINCT u.id FROM users u JOIN vehicles v ON u.id = v.user_id WHERE v.model = ?");
                            $stmt->execute([$targetValue]);
                            $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            break;
                        case 'service_due':
                            $stmt = $conn->query("SELECT DISTINCT u.id FROM users u JOIN vehicles v ON u.id = v.user_id WHERE DATEDIFF(DATE_ADD(v.last_service_date, INTERVAL 6 MONTH), CURDATE()) <= 30");
                            $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            break;
                        case 'insurance_due':
                            $stmt = $conn->query("SELECT DISTINCT u.id FROM users u JOIN vehicles v ON u.id = v.user_id WHERE DATEDIFF(v.insurance_expiry_date, CURDATE()) <= 30");
                            $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            break;
                        case 'inspection_due':
                            $stmt = $conn->query("SELECT DISTINCT u.id FROM users u JOIN vehicles v ON u.id = v.user_id WHERE DATEDIFF(v.last_inspection_date, CURDATE()) <= 30");
                            $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            break;
                    }

                    // Insert notifications for each target user
                    $sentCount = 0;
                    if (!empty($targetUsers)) {
                        foreach ($targetUsers as $userId) {
                            $stmt = $conn->prepare("INSERT INTO notifications (title, message, user_id, notification_type, target_type, target_value, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                            $stmt->execute([
                                $_POST['title'],
                                $_POST['message'],
                                $userId,
                                $_POST['notification_type'],
                                $targetType,
                                $targetValue
                            ]);
                            $sentCount++;
                        }
                    } else {
                        // If no specific users, create a general notification
                        $stmt = $conn->prepare("INSERT INTO notifications (title, message, user_id, notification_type, target_type, target_value, status) VALUES (?, ?, 0, ?, ?, ?, 'active')");
                        $stmt->execute([
                            $_POST['title'],
                            $_POST['message'],
                            $_POST['notification_type'],
                            $targetType,
                            $targetValue
                        ]);
                        $sentCount = 1;
                    }

                    $message = "Bildirim başarıyla oluşturuldu. $sentCount kullanıcıya gönderildi.";
                }
                break;

            case 'delete_notification':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
                    $stmt->execute([$_POST['notification_id']]);
                    $message = 'Bildirim başarıyla silindi';
                }
                break;

            // Inspection Schedule CRUD
            case 'create_inspection_schedule':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("INSERT INTO inspection_schedule (inspection_schedule, plate_code, vehicle_type, inspection_date, inspection_end_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['year'],
                        $_POST['plate_code'],
                        $_POST['vehicle_type'],
                        $_POST['inspection_start_date'],
                        $_POST['inspection_end_date']
                    ]);
                    $message = 'Muayene tarihi başarıyla eklendi';
                }
                break;

            case 'update_inspection_schedule':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("UPDATE inspection_schedule SET inspection_schedule = ?, plate_code = ?, vehicle_type = ?, inspection_date = ?, inspection_end_date = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['year'],
                        $_POST['plate_code'],
                        $_POST['vehicle_type'],
                        $_POST['inspection_start_date'],
                        $_POST['inspection_end_date'],
                        $_POST['schedule_id']
                    ]);
                    $message = 'Muayene tarihi başarıyla güncellendi';
                }
                break;

            case 'delete_inspection_schedule':
                if (isAdminLoggedIn()) {
                    $stmt = $conn->prepare("DELETE FROM inspection_schedule WHERE id = ?");
                    $stmt->execute([$_POST['schedule_id']]);
                    $message = 'Muayene tarihi başarıyla silindi';
                }
                break;

            case 'assign_inspection_dates':
                if (isAdminLoggedIn()) {
                    $year = $_POST['year'];
                    
                    // Seçilen yıl için tüm araçları al
                    $stmt = $conn->prepare("SELECT id, plate, vehicle_type FROM vehicles WHERE 1=1");
                    $stmt->execute();
                    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $updated = 0;
                    foreach ($vehicles as $vehicle) {
                        $plateCode = substr($vehicle['plate'], 0, 2);
                        $vehicleType = strtoupper($vehicle['vehicle_type']);
                        
                        // Muayene tarihi programını al - hem tek plaka kodu hem de virgülle ayrılmış listede ara
                        $stmt = $conn->prepare("SELECT inspection_date, inspection_end_date FROM inspection_schedule 
                                               WHERE inspection_schedule = ? 
                                               AND (plate_code = ? OR FIND_IN_SET(?, plate_code) > 0) 
                                               AND vehicle_type = ?");
                        $stmt->execute([$year, $plateCode, $plateCode, $vehicleType]);
                        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($schedule) {
                            // Araç kaydını güncelle
                            $stmt = $conn->prepare("UPDATE vehicles SET next_inspection_date = ?, inspection_end_date = ? WHERE id = ?");
                            $stmt->execute([
                                $schedule['inspection_date'],
                                $schedule['inspection_end_date'],
                                $vehicle['id']
                            ]);
                            $updated++;
                        }
                    }
                    
                    $message = "$updated araç için muayene tarihleri başarıyla atandı";
                }
                break;

            // Profile Update
            case 'update_profile':
                if (isAdminLoggedIn()) {
                    $userId = $_SESSION['admin_user']['id'];
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $password, $userId]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                        $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $userId]);
                    }

                    // Session'ı güncelle
                    $_SESSION['admin_user']['full_name'] = $_POST['full_name'];
                    $_SESSION['admin_user']['email'] = $_POST['email'];
                    $_SESSION['admin_user']['phone'] = $_POST['phone'];

                    $message = 'Profil başarıyla güncellendi';
                }
                break;

            // Settings
            case 'update_settings':
                if (isAdminLoggedIn()) {
                    // Ayarlar tablosunu oluştur
                    $conn->exec("CREATE TABLE IF NOT EXISTS app_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(255) UNIQUE, setting_value TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

                    $settings = ['app_name', 'app_description', 'contact_email', 'contact_phone', 'maintenance_mode'];

                    foreach ($settings as $key) {
                        if (isset($_POST[$key])) {
                            $value = $key === 'maintenance_mode' ? (isset($_POST[$key]) ? '1' : '0') : $_POST[$key];
                            $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                            $stmt->execute([$key, $value]);
                        }
                    }
                    $message = 'Ayarlar başarıyla güncellendi';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_modern.php');
    exit;
}

// Get data
function getData($action)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'users':
                $stmt = $conn->query("SELECT u.*, ur.name as role_name FROM users u LEFT JOIN user_roles ur ON u.role_id = ur.id ORDER BY u.id DESC LIMIT 50");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'providers':
                // Users tablosundan servis sağlayıcıları çek (role_id = 2)
                $stmt = $conn->query("
                    SELECT id, full_name, phone, email, 
                           company_name, city, services, rating, provider_status,
                           description, address, working_hours, created_at
                    FROM users 
                    WHERE role_id = 2 
                    ORDER BY id DESC
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'packages':
                $stmt = $conn->query("SELECT *, (SELECT COUNT(*) FROM subscriptions WHERE package_id = subscription_packages.id) as subscriptions FROM subscription_packages ORDER BY price ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'news':
                $stmt = $conn->query("SELECT *, CASE WHEN is_sponsored = 1 THEN 'Sponsor' ELSE 'Normal' END as news_type FROM news ORDER BY created_at DESC LIMIT 50");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'sliders':
                $conn->exec("CREATE TABLE IF NOT EXISTS sliders (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), description TEXT, image_url VARCHAR(500), link_url VARCHAR(500), sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                $stmt = $conn->query("SELECT * FROM sliders ORDER BY sort_order ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'inspection_schedule':
                // Inspection schedule tablosunu oluştur (eğer yoksa)
                $conn->exec("CREATE TABLE IF NOT EXISTS inspection_schedule (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    inspection_schedule INT NOT NULL,
                    plate_code VARCHAR(2) NOT NULL,
                    vehicle_type VARCHAR(1) NOT NULL,
                    inspection_date DATE NOT NULL,
                    inspection_end_date DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_schedule (inspection_schedule, plate_code, vehicle_type)
                )");
                
                $stmt = $conn->query("SELECT *, inspection_schedule as year, inspection_date as inspection_start_date FROM inspection_schedule ORDER BY inspection_schedule DESC, plate_code ASC, vehicle_type ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'notifications':
                // Notifications tablosunu oluştur
                $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    user_id INT DEFAULT 0,
                    notification_type VARCHAR(50) DEFAULT 'general',
                    target_type VARCHAR(50) DEFAULT 'all',
                    target_value VARCHAR(100) NULL,
                    status VARCHAR(20) DEFAULT 'active',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    sent_count INT DEFAULT 0
                )");

                $stmt = $conn->query("SELECT n.*, 
                    CASE 
                        WHEN target_type = 'all' THEN 'Tüm Kullanıcılar'
                        WHEN target_type = 'brand' THEN CONCAT('Marka: ', target_value)
                        WHEN target_type = 'model' THEN CONCAT('Model: ', target_value)
                        WHEN target_type = 'service_due' THEN 'Servis Yaklaşan'
                        WHEN target_type = 'insurance_due' THEN 'Sigorta Yaklaşan'
                        WHEN target_type = 'inspection_due' THEN 'Muayene Yaklaşan'
                        WHEN target_type = 'city' THEN CONCAT('Şehir: ', target_value)
                        ELSE target_type 
                    END as target_display
                    FROM notifications n 
                    ORDER BY n.created_at DESC LIMIT 100");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'quotes':
                $stmt = $conn->query("SELECT qr.*, u.full_name as user_name, u.phone as user_phone, v.brand, v.model, COALESCE(qr.status, 'pending') as status FROM quote_requests qr JOIN users u ON qr.user_id = u.id LEFT JOIN vehicles v ON qr.vehicle_id = v.id ORDER BY qr.created_at DESC LIMIT 50");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            default:
                return [];
        }
    } catch (Exception $e) {
        return [];
    }
}

function getStats()
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stats = [];
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
        $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $conn->query("SELECT COUNT(*) as count FROM service_providers");
        $stats['providers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $conn->query("SELECT COUNT(*) as count FROM subscription_packages");
        $stats['packages'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $conn->query("SELECT COUNT(*) as count FROM quote_requests WHERE MONTH(created_at) = MONTH(NOW())");
        $stats['quotes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return $stats;
    } catch (Exception $e) {
        return ['users' => 0, 'providers' => 0, 'packages' => 0, 'quotes' => 0];
    }
}

function getSettings()
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $conn->exec("CREATE TABLE IF NOT EXISTS app_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(255) UNIQUE, setting_value TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

        $stmt = $conn->query("SELECT setting_key, setting_value FROM app_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Default values
        $defaults = [
            'app_name' => 'Oto Asist',
            'app_description' => 'Profesyonel otomotiv hizmetleri platformu',
            'contact_email' => 'info@otoasist.com',
            'contact_phone' => '+90 555 123 45 67',
            'maintenance_mode' => '0'
        ];

        return array_merge($defaults, $settings);
    } catch (Exception $e) {
        return [
            'app_name' => 'Oto Asist',
            'app_description' => 'Profesyonel otomotiv hizmetleri platformu',
            'contact_email' => 'info@otoasist.com',
            'contact_phone' => '+90 555 123 45 67',
            'maintenance_mode' => '0'
        ];
    }
}

// Get edit data for forms
$editData = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $editType = $_GET['edit'];
    $editId = $_GET['id'];

    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($editType) {
            case 'user':
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'provider':
                // Users tablosundan servis sağlayıcıyı çek
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role_id = 2");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'package':
                $stmt = $conn->prepare("SELECT * FROM subscription_packages WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'news':
                $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'slider':
                $stmt = $conn->prepare("SELECT * FROM sliders WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'quote':
                $stmt = $conn->prepare("SELECT * FROM quote_requests WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'notification':
                $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            case 'schedule':
                $stmt = $conn->prepare("SELECT * FROM inspection_schedule WHERE id = ?");
                $stmt->execute([$editId]);
                $editData = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
        }
    } catch (Exception $e) {
        $editData = null;
    }
}

// Get quote details for modal
$quoteDetails = null;
if (isset($_GET['view_quote']) && isset($_GET['id'])) {
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            SELECT qr.*, u.full_name as user_name, u.phone as user_phone, u.email as user_email, u.driving_license_photo,
                   v.brand, v.model, v.year, v.plate, v.color, v.last_service_date, v.insurance_expiry_date
            FROM quote_requests qr 
            JOIN users u ON qr.user_id = u.id 
            LEFT JOIN vehicles v ON qr.vehicle_id = v.id 
            WHERE qr.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $quoteDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        // Parse vehicle_details and attachments JSON
        if ($quoteDetails) {
            $quoteDetails['vehicle_details_parsed'] = !empty($quoteDetails['vehicle_details'])
                ? json_decode($quoteDetails['vehicle_details'], true)
                : null;
            $quoteDetails['attachments_parsed'] = !empty($quoteDetails['attachments'])
                ? json_decode($quoteDetails['attachments'], true)
                : null;
        }
    } catch (Exception $e) {
        $quoteDetails = null;
    }
}

$action = $_GET['action'] ?? 'dashboard';
$data = [];
$stats = [];
$settings = [];

if (isAdminLoggedIn()) {
    $stats = getStats();
    if ($action !== 'dashboard') {
        $data = getData($action);
    }
    if ($action === 'settings') {
        $settings = getSettings();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oto Asist - AdminMart Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #6366f1;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-color);
            margin: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--dark-color) 0%, #2d3748 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #374151;
            text-align: center;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .menu-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .menu-item:hover,
        .menu-item.active {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .menu-item i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-area {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.users {
            border-left-color: var(--primary-color);
        }

        .stat-card.providers {
            border-left-color: var(--success-color);
        }

        .stat-card.packages {
            border-left-color: var(--warning-color);
        }

        .stat-card.quotes {
            border-left-color: var(--danger-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card.users .stat-number {
            color: var(--primary-color);
        }

        .stat-card.providers .stat-number {
            color: var(--success-color);
        }

        .stat-card.packages .stat-number {
            color: var(--warning-color);
        }

        .stat-card.quotes .stat-number {
            color: var(--danger-color);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
            border: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-danger {
            background: var(--danger-color);
            border: none;
            border-radius: 8px;
        }

        .btn-warning {
            background: var(--warning-color);
            border: none;
            border-radius: 8px;
        }

        .btn-success {
            background: var(--success-color);
            border: none;
            border-radius: 8px;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background: var(--dark-color);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table tbody tr:hover {
            background: #f1f5f9;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 400px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php if (!isAdminLoggedIn()): ?>
        <div class="login-container">
            <div class="login-card">
                <div class="text-center mb-4">
                    <h2><i class="fas fa-car text-primary"></i> Oto Asist</h2>
                    <p class="text-muted">AdminMart Panel Girişi</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label">Admin Token</label>
                        <input type="text" name="token" class="form-control" value="+905551234567" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                    </button>
                </form>

                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted">
                        <strong>Demo Token:</strong> +905551234567<br>
                        <strong>Kullanıcı:</strong> Ahmet Yılmaz
                    </small>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-car"></i> Oto Asist</h3>
                <small>AdminMart Panel</small>
            </div>

            <div class="sidebar-menu">
                <a href="?action=dashboard" class="menu-item <?= $action === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?action=users" class="menu-item <?= $action === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Kullanıcılar
                </a>
                <a href="?action=providers" class="menu-item <?= $action === 'providers' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> Servis Sağlayıcılar
                </a>
                <a href="?action=packages" class="menu-item <?= $action === 'packages' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i> Paketler
                </a>
                <a href="admin_subscriptions.php?token=<?= urlencode($_SESSION['admin_user']['phone']) ?>" class="menu-item">
                    <i class="fas fa-credit-card"></i> Abonelik Talepleri
                </a>
                <a href="?action=news" class="menu-item <?= $action === 'news' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i> Haberler
                </a>
                <a href="?action=sliders" class="menu-item <?= $action === 'sliders' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i> Sliderlar
                </a>
                <a href="?action=quotes" class="menu-item <?= $action === 'quotes' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Teklifler
                </a>
                <a href="?action=notifications" class="menu-item <?= $action === 'notifications' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i> Bildirimler
                </a>
                <a href="?action=inspection_schedule" class="menu-item <?= $action === 'inspection_schedule' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Muayene Tarihleri
                </a>
                <div style="border-top: 1px solid #374151; margin: 1rem 0;"></div>
                <a href="?action=profile" class="menu-item <?= $action === 'profile' ? 'active' : '' ?>">
                    <i class="fas fa-user-cog"></i> Profil
                </a>
                <a href="?action=settings" class="menu-item <?= $action === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i> Ayarlar
                </a>
                <a href="?logout=1" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <h4 class="mb-0">
                    <?php
                    $titles = [
                        'dashboard' => 'Dashboard',
                        'users' => 'Kullanıcı Yönetimi',
                        'providers' => 'Servis Sağlayıcılar',
                        'packages' => 'Paket Yönetimi',
                        'news' => 'Haber Yönetimi',
                        'sliders' => 'Slider Yönetimi',
                        'quotes' => 'Teklif Yönetimi',
                        'notifications' => 'Bildirim Yönetimi',
                        'inspection_schedule' => 'Muayene Tarihi Yönetimi',
                        'profile' => 'Profil Ayarları',
                        'settings' => 'Sistem Ayarları'
                    ];
                    echo $titles[$action] ?? 'Yönetim Paneli';
                    ?>
                </h4>
                <div>
                    <span class="text-muted">Hoş geldiniz, <?= htmlspecialchars($_SESSION['admin_user']['full_name']) ?></span>
                </div>
            </div>

            <div class="content-area">
                <?php if (isset($message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'dashboard'): ?>
                    <!-- Dashboard Stats -->
                    <div class="stats-grid">
                        <div class="stat-card users">
                            <div class="stat-number"><?= $stats['users'] ?></div>
                            <div class="text-muted">Toplam Kullanıcı</div>
                        </div>
                        <div class="stat-card providers">
                            <div class="stat-number"><?= $stats['providers'] ?></div>
                            <div class="text-muted">Servis Sağlayıcı</div>
                        </div>
                        <div class="stat-card packages">
                            <div class="stat-number"><?= $stats['packages'] ?></div>
                            <div class="text-muted">Abonelik Paketi</div>
                        </div>
                        <div class="stat-card quotes">
                            <div class="stat-number"><?= $stats['quotes'] ?></div>
                            <div class="text-muted">Aylık Teklif</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-line me-2"></i>Son Aktiviteler</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">✅ AdminMart temalı modern panel aktif</p>
                                    <p class="text-muted">✅ CRUD işlemleri çalışıyor</p>
                                    <p class="text-muted">✅ Demo veriler yüklendi</p>
                                    <p class="text-muted">✅ Edit özellikleri eklendi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-cogs me-2"></i>Sistem Bilgileri</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Tema:</strong> AdminMart</p>
                                    <p><strong>Versiyon:</strong> 2.0</p>
                                    <p><strong>PHP:</strong> <?= PHP_VERSION ?></p>
                                    <p><strong>Durum:</strong> <span class="badge bg-success">Aktif</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- CRUD Sections -->

                    <?php if ($action === 'users'): ?>
                        <!-- User Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users me-2"></i>Kullanıcı Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add User Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>Yeni Kullanıcı Ekle</h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_user' : 'create_user' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="user_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="col-md-3">
                                                <input type="text" name="full_name" class="form-control" placeholder="Ad Soyad" value="<?= htmlspecialchars($editData['full_name'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="email" name="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($editData['email'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="phone" class="form-control" placeholder="Telefon" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="password" name="password" class="form-control" placeholder="<?= $editData ? 'Yeni Şifre (boş bırakılabilir)' : 'Şifre' ?>" <?= !$editData ? 'required' : '' ?>>
                                            </div>
                                            <div class="col-md-1">
                                                <select name="role_id" class="form-control">
                                                    <option value="1" <?= ($editData['role_id'] ?? 1) == 1 ? 'selected' : '' ?>>User</option>
                                                    <option value="2" <?= ($editData['role_id'] ?? 1) == 2 ? 'selected' : '' ?>>Provider</option>
                                                    <option value="3" <?= ($editData['role_id'] ?? 1) == 3 ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="submit" class="btn btn-primary">
                                                    <?= $editData ? 'Güncelle' : 'Ekle' ?>
                                                </button>
                                                <?php if ($editData): ?>
                                                    <a href="?action=users" class="btn btn-secondary">İptal</a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for Users -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterUsers" class="form-control" placeholder="🔍 Kullanıcı ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterUserRole" class="form-control">
                                            <option value="">Tüm Roller</option>
                                            <?php
                                            $roles = array_unique(array_column($data, 'role_name'));
                                            sort($roles);
                                            foreach ($roles as $role):
                                                if (!empty($role)):
                                            ?>
                                                    <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('users')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Users Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="usersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Ad Soyad</th>
                                                    <th>Telefon</th>
                                                    <th>Email</th>
                                                    <th>Rol</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $user): ?>
                                                    <tr>
                                                        <td><?= $user['id'] ?></td>
                                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                                        <td><span class="badge bg-info"><?= $user['role_name'] ?? 'User' ?></span></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=users&edit=user&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if ($user['role_id'] != 3): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="delete_user">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz kullanıcı bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'packages'): ?>
                        <!-- Package Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-box me-2"></i>Paket Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add Package Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6><?= $editData ? 'Paket Düzenle' : 'Yeni Paket Ekle' ?></h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_package' : 'create_package' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="package_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="col-md-3">
                                                <input type="text" name="name" class="form-control" placeholder="Paket Adı" value="<?= htmlspecialchars($editData['name'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="description" class="form-control" placeholder="Açıklama" value="<?= htmlspecialchars($editData['description'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="price" class="form-control" placeholder="Fiyat" step="0.01" value="<?= $editData['price'] ?? '' ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="duration_months" class="form-control" placeholder="Süre (Ay)" value="<?= $editData['duration_months'] ?? '' ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="max_requests_per_month" class="form-control" placeholder="Aylık Teklif Limiti" value="<?= $editData['max_requests_per_month'] ?? '' ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <button type="submit" class="btn btn-primary">
                                                    <?= $editData ? 'Güncelle' : 'Ekle' ?>
                                                </button>
                                                <?php if ($editData): ?>
                                                    <a href="?action=packages" class="btn btn-secondary">İptal</a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for Packages -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterPackages" class="form-control" placeholder="🔍 Paket ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="filterMinPrice" class="form-control" placeholder="Min Fiyat" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="filterMaxPrice" class="form-control" placeholder="Max Fiyat" step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('packages')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Packages Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="packagesTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Paket Adı</th>
                                                    <th>Fiyat</th>
                                                    <th>Süre</th>
                                                    <th>Abonelik</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $package): ?>
                                                    <tr>
                                                        <td><?= $package['id'] ?></td>
                                                        <td><?= htmlspecialchars($package['name']) ?></td>
                                                        <td><span class="badge bg-success"><?= $package['price'] ?> ₺</span></td>
                                                        <td><?= $package['duration_months'] ?> ay</td>
                                                        <td><?= $package['subscriptions'] ?? 0 ?></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=packages&edit=package&id=<?= $package['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_package">
                                                                <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz paket bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'providers'): ?>
                        <!-- Providers Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-building me-2"></i>Servis Sağlayıcı Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add Provider Form -->
                                <?php if (!$editData): ?>
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h6>Yeni Servis Sağlayıcı Ekle</h6>
                                            <form method="POST" class="row g-3">
                                                <input type="hidden" name="action" value="create_provider">
                                                <div class="col-md-4">
                                                    <input type="text" name="company_name" class="form-control" placeholder="Şirket Adı" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="phone" class="form-control" placeholder="Telefon" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="password" name="password" class="form-control" placeholder="Şifre" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="city" class="form-control" required>
                                                        <option value="">Şehir Seçin</option>
                                                        <option value="Lefkoşa">Lefkoşa</option>
                                                        <option value="Girne">Girne</option>
                                                        <option value="Gazimağusa">Gazimağusa</option>
                                                        <option value="Güzelyurt">Güzelyurt</option>
                                                        <option value="İskele">İskele</option>
                                                        <option value="Lefke">Lefke</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="services" class="form-control" required>
                                                        <option value="">Hizmet Seçin</option>
                                                        <option value="Servis ve Bakım">Servis ve Bakım</option>
                                                        <option value="Lastik Değişimi">Lastik Değişimi</option>
                                                        <option value="Cam Değişimi">Cam Değişimi</option>
                                                        <option value="Oto Elektrik">Oto Elektrik</option>
                                                        <option value="Motor Tamiri">Motor Tamiri</option>
                                                        <option value="Fren Sistemi">Fren Sistemi</option>
                                                        <option value="Klima Servisi">Klima Servisi</option>
                                                        <option value="Boya Badana">Boya Badana</option>
                                                        <option value="Ekspertiz">Ekspertiz</option>
                                                        <option value="Çekici Hizmeti">Çekici Hizmeti</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" name="address" class="form-control" placeholder="Adres">
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="description" class="form-control" placeholder="Açıklama" rows="2"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Ekle</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Edit Provider Form -->
                                <?php if ($editData): ?>
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h6>Servis Sağlayıcı Düzenle</h6>
                                            <form method="POST" class="row g-3">
                                                <input type="hidden" name="action" value="update_provider">
                                                <input type="hidden" name="provider_id" value="<?= $editData['id'] ?>">
                                                <div class="col-md-4">
                                                    <input type="text" name="company_name" class="form-control" placeholder="Şirket Adı" value="<?= htmlspecialchars($editData['company_name']) ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <select name="city" class="form-control" required>
                                                        <option value="">Şehir Seçin</option>
                                                        <option value="Lefkoşa" <?= $editData['city'] == 'Lefkoşa' ? 'selected' : '' ?>>Lefkoşa</option>
                                                        <option value="Girne" <?= $editData['city'] == 'Girne' ? 'selected' : '' ?>>Girne</option>
                                                        <option value="Gazimağusa" <?= $editData['city'] == 'Gazimağusa' ? 'selected' : '' ?>>Gazimağusa</option>
                                                        <option value="Güzelyurt" <?= $editData['city'] == 'Güzelyurt' ? 'selected' : '' ?>>Güzelyurt</option>
                                                        <option value="İskele" <?= $editData['city'] == 'İskele' ? 'selected' : '' ?>>İskele</option>
                                                        <option value="Lefke" <?= $editData['city'] == 'Lefke' ? 'selected' : '' ?>>Lefke</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <select name="services" class="form-control" required>
                                                        <option value="">Hizmet Seçin</option>
                                                        <option value="Servis ve Bakım" <?= $editData['services'] == 'Servis ve Bakım' ? 'selected' : '' ?>>Servis ve Bakım</option>
                                                        <option value="Lastik Değişimi" <?= $editData['services'] == 'Lastik Değişimi' ? 'selected' : '' ?>>Lastik Değişimi</option>
                                                        <option value="Cam Değişimi" <?= $editData['services'] == 'Cam Değişimi' ? 'selected' : '' ?>>Cam Değişimi</option>
                                                        <option value="Oto Elektrik" <?= $editData['services'] == 'Oto Elektrik' ? 'selected' : '' ?>>Oto Elektrik</option>
                                                        <option value="Motor Tamiri" <?= $editData['services'] == 'Motor Tamiri' ? 'selected' : '' ?>>Motor Tamiri</option>
                                                        <option value="Fren Sistemi" <?= $editData['services'] == 'Fren Sistemi' ? 'selected' : '' ?>>Fren Sistemi</option>
                                                        <option value="Klima Servisi" <?= $editData['services'] == 'Klima Servisi' ? 'selected' : '' ?>>Klima Servisi</option>
                                                        <option value="Boya Badana" <?= $editData['services'] == 'Boya Badana' ? 'selected' : '' ?>>Boya Badana</option>
                                                        <option value="Ekspertiz" <?= $editData['services'] == 'Ekspertiz' ? 'selected' : '' ?>>Ekspertiz</option>
                                                        <option value="Çekici Hizmeti" <?= $editData['services'] == 'Çekici Hizmeti' ? 'selected' : '' ?>>Çekici Hizmeti</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="text" name="address" class="form-control" placeholder="Adres" value="<?= htmlspecialchars($editData['address'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <textarea name="description" class="form-control" placeholder="Açıklama" rows="2"><?= htmlspecialchars($editData['description']) ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                                    <a href="?action=providers" class="btn btn-secondary">İptal</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Filter for Providers -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterProviders" class="form-control" placeholder="🔍 Servis sağlayıcı ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterProviderCity" class="form-control">
                                            <option value="">Tüm Şehirler</option>
                                            <?php
                                            $cities = array_unique(array_column($data, 'city'));
                                            sort($cities);
                                            foreach ($cities as $city):
                                                if (!empty($city)):
                                            ?>
                                                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('providers')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Providers Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="providersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Şirket</th>
                                                    <th>İletişim</th>
                                                    <th>Telefon</th>
                                                    <th>Şehir</th>
                                                    <th>Hizmetler</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $provider): ?>
                                                    <tr>
                                                        <td><?= $provider['id'] ?></td>
                                                        <td><?= htmlspecialchars($provider['company_name']) ?></td>
                                                        <td><?= htmlspecialchars($provider['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($provider['phone']) ?></td>
                                                        <td><?= htmlspecialchars($provider['city']) ?></td>
                                                        <td><?= htmlspecialchars(substr($provider['services'], 0, 30)) ?>...</td>
                                                        <td class="action-buttons">
                                                            <a href="?action=providers&edit=provider&id=<?= $provider['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_provider">
                                                                <input type="hidden" name="provider_id" value="<?= $provider['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz servis sağlayıcı bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'news'): ?>
                        <!-- News Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-newspaper me-2"></i>Haber Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add/Edit News Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6><?= $editData ? 'Haber Düzenle' : 'Yeni Haber Ekle' ?></h6>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_news' : 'create_news' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="news_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <input type="text" name="title" class="form-control" placeholder="Başlık" value="<?= htmlspecialchars($editData['title'] ?? '') ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="category" class="form-control">
                                                        <option value="general" <?= ($editData['category'] ?? '') == 'general' ? 'selected' : '' ?>>Genel</option>
                                                        <option value="teknoloji" <?= ($editData['category'] ?? '') == 'teknoloji' ? 'selected' : '' ?>>Teknoloji</option>
                                                        <option value="sigorta" <?= ($editData['category'] ?? '') == 'sigorta' ? 'selected' : '' ?>>Sigorta</option>
                                                        <option value="bakım" <?= ($editData['category'] ?? '') == 'bakım' ? 'selected' : '' ?>>Bakım</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="url" name="image_url" class="form-control" placeholder="Resim URL" value="<?= htmlspecialchars($editData['image_url'] ?? '') ?>">
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="excerpt" class="form-control" placeholder="Özet" rows="2"><?= htmlspecialchars($editData['excerpt'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="content" class="form-control" placeholder="İçerik" rows="5" required><?= htmlspecialchars($editData['content'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_featured" class="form-check-input" <?= ($editData['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Öne Çıkan</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_sponsored" class="form-check-input" <?= ($editData['is_sponsored'] ?? 0) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Sponsor Haber</label>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary"><?= $editData ? 'Güncelle' : 'Ekle' ?></button>
                                                    <?php if ($editData): ?>
                                                        <a href="?action=news" class="btn btn-secondary">İptal</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for News -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterNews" class="form-control" placeholder="🔍 Haber ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterNewsCategory" class="form-control">
                                            <option value="">Tüm Kategoriler</option>
                                            <?php
                                            $categories = array_unique(array_column($data, 'category'));
                                            sort($categories);
                                            foreach ($categories as $category):
                                                if (!empty($category)):
                                            ?>
                                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterNewsType" class="form-control">
                                            <option value="">Tüm Tipler</option>
                                            <option value="Normal">Normal</option>
                                            <option value="Sponsor">Sponsor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('news')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- News Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="newsTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Başlık</th>
                                                    <th>Kategori</th>
                                                    <th>Tip</th>
                                                    <th>Yazar</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $news): ?>
                                                    <tr>
                                                        <td><?= $news['id'] ?></td>
                                                        <td><?= htmlspecialchars(substr($news['title'], 0, 40)) ?>...</td>
                                                        <td><span class="badge bg-info"><?= $news['category'] ?></span></td>
                                                        <td><span class="badge bg-<?= $news['is_sponsored'] ? 'warning' : 'primary' ?>"><?= $news['news_type'] ?></span></td>
                                                        <td><?= htmlspecialchars($news['author']) ?></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=news&edit=news&id=<?= $news['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_news">
                                                                <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz haber bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'sliders'): ?>
                        <!-- Slider Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-images me-2"></i>Slider Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add/Edit Slider Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6><?= $editData ? 'Slider Düzenle' : 'Yeni Slider Ekle' ?></h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_slider' : 'create_slider' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="slider_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            <div class="col-md-4">
                                                <input type="text" name="title" class="form-control" placeholder="Başlık" value="<?= htmlspecialchars($editData['title'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="url" name="image_url" class="form-control" placeholder="Resim URL" value="<?= htmlspecialchars($editData['image_url'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="url" name="link_url" class="form-control" placeholder="Link URL" value="<?= htmlspecialchars($editData['link_url'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="sort_order" class="form-control" placeholder="Sıra" value="<?= $editData['sort_order'] ?? 0 ?>">
                                            </div>
                                            <div class="col-12">
                                                <textarea name="description" class="form-control" placeholder="Açıklama" rows="2"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
                                            </div>
                                            <?php if ($editData): ?>
                                                <div class="col-md-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_active" class="form-check-input" <?= ($editData['is_active'] ?? 1) ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Aktif</label>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary"><?= $editData ? 'Güncelle' : 'Ekle' ?></button>
                                                <?php if ($editData): ?>
                                                    <a href="?action=sliders" class="btn btn-secondary">İptal</a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for Sliders -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterSliders" class="form-control" placeholder="🔍 Slider ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterSliderStatus" class="form-control">
                                            <option value="">Tüm Durumlar</option>
                                            <option value="Aktif">Aktif</option>
                                            <option value="Pasif">Pasif</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('sliders')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Sliders Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="slidersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Başlık</th>
                                                    <th>Açıklama</th>
                                                    <th>Sıra</th>
                                                    <th>Tıklama</th>
                                                    <th>Aktif</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $slider): ?>
                                                    <tr>
                                                        <td><?= $slider['id'] ?></td>
                                                        <td><?= htmlspecialchars($slider['title']) ?></td>
                                                        <td><?= htmlspecialchars(substr($slider['description'], 0, 30)) ?>...</td>
                                                        <td><?= $slider['sort_order'] ?></td>
                                                        <td><span class="badge bg-primary"><?= $slider['click_count'] ?? 0 ?></span></td>
                                                        <td><span class="badge bg-<?= $slider['is_active'] ? 'success' : 'secondary' ?>"><?= $slider['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=sliders&edit=slider&id=<?= $slider['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_slider">
                                                                <input type="hidden" name="slider_id" value="<?= $slider['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz slider bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'quotes'): ?>
                        <!-- Quotes Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-comments me-2"></i>Teklif Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Edit Quote Form -->
                                <?php if ($editData): ?>
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h6>Teklif Düzenle</h6>
                                            <form method="POST" class="row g-3">
                                                <input type="hidden" name="action" value="update_quote">
                                                <input type="hidden" name="quote_id" value="<?= $editData['id'] ?>">
                                                <div class="col-md-4">
                                                    <input type="text" name="title" class="form-control" placeholder="Başlık" value="<?= htmlspecialchars($editData['title']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="status" class="form-control">
                                                        <option value="pending" <?= $editData['status'] == 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                                                        <option value="approved" <?= $editData['status'] == 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                                                        <option value="rejected" <?= $editData['status'] == 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
                                                        <option value="completed" <?= $editData['status'] == 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="description" class="form-control" placeholder="Açıklama" rows="3"><?= htmlspecialchars($editData['description']) ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                                    <a href="?action=quotes" class="btn btn-secondary">İptal</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Filter for Quotes -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterQuotes" class="form-control" placeholder="🔍 Teklif ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterQuoteStatus" class="form-control">
                                            <option value="">Tüm Durumlar</option>
                                            <option value="pending">Bekliyor</option>
                                            <option value="approved">Onaylandı</option>
                                            <option value="rejected">Reddedildi</option>
                                            <option value="completed">Tamamlandı</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('quotes')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Quotes Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="quotesTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Başlık</th>
                                                    <th>Kullanıcı</th>
                                                    <th>Telefon</th>
                                                    <th>Araç</th>
                                                    <th>Durum</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $quote): ?>
                                                    <tr>
                                                        <td><?= $quote['id'] ?></td>
                                                        <td><?= htmlspecialchars($quote['title']) ?></td>
                                                        <td><?= htmlspecialchars($quote['user_name']) ?></td>
                                                        <td><?= htmlspecialchars($quote['user_phone']) ?></td>
                                                        <td><?= htmlspecialchars($quote['brand'] . ' ' . $quote['model']) ?></td>
                                                        <td><span class="badge bg-info"><?= $quote['status'] ?></span></td>
                                                        <td class="action-buttons">
                                                            <a href="?view_quote=1&id=<?= $quote['id'] ?>" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#quoteModal">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="?action=quotes&edit=quote&id=<?= $quote['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_quote_request">
                                                                <input type="hidden" name="quote_request_id" value="<?= $quote['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Henüz teklif talebi bulunmuyor.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'profile'): ?>
                        <!-- Profile Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user-cog me-2"></i>Profil Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="col-md-6">
                                        <label class="form-label">Ad Soyad</label>
                                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_user']['full_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_user']['email']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_SESSION['admin_user']['phone']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Yeni Şifre (Boş bırakılabilir)</label>
                                        <input type="password" name="password" class="form-control" placeholder="Yeni şifre girmek için tıklayın">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Profili Güncelle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($action === 'settings'): ?>
                        <!-- Settings Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs me-2"></i>Sistem Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="update_settings">
                                    <div class="col-md-6">
                                        <label class="form-label">Uygulama Adı</label>
                                        <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($settings['app_name']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">İletişim Telefonu</label>
                                        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">İletişim Email</label>
                                        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" name="maintenance_mode" class="form-check-input" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Bakım Modu</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Uygulama Açıklaması</label>
                                        <textarea name="app_description" class="form-control" rows="3"><?= htmlspecialchars($settings['app_description']) ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Ayarları Kaydet
                                        </button>
                                    </div>
                                </form>

                                <!-- Veritabanı Migrasyonu -->
                                <hr class="my-4">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-database me-2"></i>Veritabanı Migrasyonu</h6>
                                    <p class="mb-3">Servis sağlayıcıları ayrı tablodan users tablosuna taşınacak. Bu işlem:</p>
                                    <ul class="mb-3">
                                        <li>Users tablosuna gerekli alanları ekler</li>
                                        <li>service_providers tablosundaki verileri users tablosuna aktarır</li>
                                        <li>Demo kullanıcıyı servis sağlayıcı olarak ayarlar</li>
                                    </ul>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="migrate_to_users_table">
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('Bu işlem geri alınamaz! Devam etmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-database me-2"></i>Migrasyonu Başlat
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($action === 'notifications'): ?>
                        <!-- Notification Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bell me-2"></i>Bildirim Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add Notification Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>📢 Yeni Bildirim Oluştur</h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="create_notification">

                                            <div class="col-md-6">
                                                <label class="form-label">Başlık</label>
                                                <input type="text" name="title" class="form-control" placeholder="🔔 Bildirim başlığı" required>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Bildirim Türü</label>
                                                <select name="notification_type" class="form-control" required>
                                                    <option value="general">🔔 Genel</option>
                                                    <option value="reminder">⏰ Hatırlatma</option>
                                                    <option value="campaign">🎁 Kampanya</option>
                                                    <option value="news">📰 Haber</option>
                                                    <option value="alert">⚠️ Uyarı</option>
                                                    <option value="maintenance">🔧 Bakım</option>
                                                    <option value="offer">💰 Teklif</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Hedef Kitle</label>
                                                <select name="target_type" class="form-control" required onchange="toggleTargetValue(this.value)">
                                                    <option value="all">👥 Tüm Kullanıcılar</option>
                                                    <option value="brand">🚗 Araç Markası</option>
                                                    <option value="model">🚙 Araç Modeli</option>
                                                    <option value="service_due">🔧 Servis Yaklaşan</option>
                                                    <option value="insurance_due">🛡️ Sigorta Yaklaşan</option>
                                                    <option value="inspection_due">📋 Muayene Yaklaşan</option>
                                                    <option value="city">🏙️ Şehir</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6" id="targetValueDiv" style="display: none;">
                                                <label class="form-label">Hedef Değer</label>
                                                <input type="text" name="target_value" class="form-control" placeholder="Marka/Model/Şehir adı">
                                                <small class="text-muted">Örnek: Toyota, İstanbul, Corolla</small>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Mesaj</label>
                                                <textarea name="message" class="form-control" rows="3" placeholder="📝 Bildirim mesajınızı buraya yazın..." required></textarea>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-paper-plane me-2"></i>Bildirimi Gönder
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter for Notifications -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="filterNotifications" class="form-control" placeholder="🔍 Bildirim ara...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterNotificationType" class="form-control">
                                            <option value="">Tüm Türler</option>
                                            <option value="general">Genel</option>
                                            <option value="reminder">Hatırlatma</option>
                                            <option value="campaign">Kampanya</option>
                                            <option value="news">Haber</option>
                                            <option value="alert">Uyarı</option>
                                            <option value="maintenance">Bakım</option>
                                            <option value="offer">Teklif</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterTargetType" class="form-control">
                                            <option value="">Tüm Hedef Kitle</option>
                                            <option value="all">Tüm Kullanıcılar</option>
                                            <option value="brand">Araç Markası</option>
                                            <option value="model">Araç Modeli</option>
                                            <option value="service_due">Servis Yaklaşan</option>
                                            <option value="insurance_due">Sigorta Yaklaşan</option>
                                            <option value="inspection_due">Muayene Yaklaşan</option>
                                            <option value="city">Şehir</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('notifications')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Notifications Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="notificationsTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Başlık</th>
                                                    <th>Tür</th>
                                                    <th>Hedef Kitle</th>
                                                    <th>Mesaj</th>
                                                    <th>Tarih</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $notification): ?>
                                                    <tr>
                                                        <td><?= $notification['id'] ?></td>
                                                        <td><?= htmlspecialchars($notification['title']) ?></td>
                                                        <td>
                                                            <?php
                                                            $typeEmojis = [
                                                                'general' => '🔔',
                                                                'reminder' => '⏰',
                                                                'campaign' => '🎁',
                                                                'news' => '📰',
                                                                'alert' => '⚠️',
                                                                'maintenance' => '🔧',
                                                                'offer' => '💰'
                                                            ];
                                                            echo $typeEmojis[$notification['notification_type']] ?? '🔔';
                                                            echo ' ' . ucfirst($notification['notification_type']);
                                                            ?>
                                                        </td>
                                                        <td><span class="badge bg-info"><?= htmlspecialchars($notification['target_display']) ?></span></td>
                                                        <td title="<?= htmlspecialchars($notification['message']) ?>">
                                                            <?= htmlspecialchars(substr($notification['message'], 0, 50)) ?>...
                                                        </td>
                                                        <td><?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?></td>
                                                        <td class="action-buttons">
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_notification">
                                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu bildirimi silmek istediğinizden emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Stats -->
                                    <div class="mt-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>İstatistikler:</strong> Toplam <?= count($data) ?> bildirim
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Henüz bildirim oluşturulmamış.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($action === 'inspection_schedule'): ?>
                        <!-- Inspection Schedule Management -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-check me-2"></i>Muayene Tarihi Yönetimi</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add Inspection Schedule Form -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>📅 Yeni Muayene Tarihi Ekle</h6>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="action" value="<?= $editData ? 'update_inspection_schedule' : 'create_inspection_schedule' ?>">
                                            <?php if ($editData): ?>
                                                <input type="hidden" name="schedule_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>

                                            <div class="col-md-2">
                                                <label class="form-label">Yıl</label>
                                                <select name="year" class="form-control" required>
                                                    <option value="">Yıl Seçin</option>
                                                    <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                                        <option value="<?= $y ?>" <?= ($editData['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Plaka Kodu</label>
                                                <select name="plate_code" class="form-control" required>
                                                    <option value="">Plaka Kodu</option>
                                                    <?php 
                                                    $plateCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '61', '62', '63', '64', '65', '66', '67', '68', '69', '70', '71', '72', '73', '74', '75', '76', '77', '78', '79', '80', '81'];
                                                    foreach ($plateCodes as $code): ?>
                                                        <option value="<?= $code ?>" <?= ($editData['plate_code'] ?? '') == $code ? 'selected' : '' ?>><?= $code ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Araç Tipi</label>
                                                <select name="vehicle_type" class="form-control" required>
                                                    <option value="">Araç Tipi</option>
                                                    <option value="A" <?= ($editData['vehicle_type'] ?? '') == 'A' ? 'selected' : '' ?>>A - Otomobil</option>
                                                    <option value="D" <?= ($editData['vehicle_type'] ?? '') == 'D' ? 'selected' : '' ?>>D - Ticari</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Muayene Başlangıç Tarihi</label>
                                                <input type="date" name="inspection_start_date" class="form-control" value="<?= htmlspecialchars($editData['inspection_start_date'] ?? '') ?>" required>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Muayene Bitiş Tarihi</label>
                                                <input type="date" name="inspection_end_date" class="form-control" value="<?= htmlspecialchars($editData['inspection_end_date'] ?? '') ?>" required>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i><?= $editData ? 'Güncelle' : 'Ekle' ?>
                                                </button>
                                                <?php if ($editData): ?>
                                                    <a href="?action=inspection_schedule" class="btn btn-secondary">
                                                        <i class="fas fa-times me-2"></i>İptal
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Bulk Assignment Section -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-header">
                                                <h6><i class="fas fa-magic me-2"></i>Toplu Muayene Tarihi Atama</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST" class="row g-3">
                                                    <input type="hidden" name="action" value="assign_inspection_dates">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Yıl Seçin</label>
                                                        <select name="year" class="form-control" required>
                                                            <option value="">Yıl Seçin</option>
                                                            <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                                                <option value="<?= $y ?>"><?= $y ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <label class="form-label">İşlem</label><br>
                                                        <button type="submit" class="btn btn-success" onclick="return confirm('Seçilen yıl için tüm araçlara muayene tarihleri atanacak. Devam etmek istediğinizden emin misiniz?')">
                                                            <i class="fas fa-calendar-plus me-2"></i>Tüm Araçlara Muayene Tarihi Ata
                                                        </button>
                                                    </div>
                                                </form>
                                                <div class="alert alert-info mt-3">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Bilgi:</strong> Bu işlem tüm kayıtlı araçların plaka kodları ve araç tiplerine göre muayene tarihlerini otomatik olarak atayacaktır.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter for Inspection Schedule -->
                                <div class="row mb-3">
                                    <div class="col-md-2">
                                        <select id="filterYear" class="form-control">
                                            <option value="">Tüm Yıllar</option>
                                            <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                                <option value="<?= $y ?>"><?= $y ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select id="filterPlateCode" class="form-control">
                                            <option value="">Tüm Plaka Kodları</option>
                                            <?php 
                                            $plateCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '61', '62', '63', '64', '65', '66', '67', '68', '69', '70', '71', '72', '73', '74', '75', '76', '77', '78', '79', '80', '81'];
                                            foreach ($plateCodes as $code): ?>
                                                <option value="<?= $code ?>"><?= $code ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select id="filterVehicleType" class="form-control">
                                            <option value="">Tüm Araç Tipleri</option>
                                            <option value="A">A - Otomobil</option>
                                            <option value="D">D - Ticari</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary" onclick="clearFilters('inspection')">
                                            <i class="fas fa-times"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <!-- Inspection Schedule Table -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="inspectionTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Yıl</th>
                                                    <th>Plaka Kodu</th>
                                                    <th>Araç Tipi</th>
                                                    <th>Muayene Başlangıç</th>
                                                    <th>Muayene Bitiş</th>
                                                    <th>Oluşturulma</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $schedule): ?>
                                                    <tr>
                                                        <td><?= $schedule['id'] ?></td>
                                                        <td><span class="badge bg-primary"><?= $schedule['year'] ?></span></td>
                                                        <td><span class="badge bg-info"><?= $schedule['plate_code'] ?></span></td>
                                                        <td>
                                                            <span class="badge bg-<?= $schedule['vehicle_type'] == 'A' ? 'success' : 'warning' ?>">
                                                                <?= $schedule['vehicle_type'] == 'A' ? 'A - Otomobil' : 'D - Ticari' ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('d/m/Y', strtotime($schedule['inspection_start_date'])) ?></td>
                                                        <td><?= date('d/m/Y', strtotime($schedule['inspection_end_date'])) ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($schedule['created_at'])) ?></td>
                                                        <td class="action-buttons">
                                                            <a href="?action=inspection_schedule&edit=schedule&id=<?= $schedule['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete_inspection_schedule">
                                                                <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu muayene tarihi kaydını silmek istediğinizden emin misiniz?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Stats -->
                                    <div class="mt-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>İstatistikler:</strong> Toplam <?= count($data) ?> muayene tarihi programı
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Henüz muayene tarihi programı oluşturulmamış.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Bu bölüm yakında eklenecek.
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quote Detail Modal -->
    <?php if ($quoteDetails): ?>
        <div class="modal fade" id="quoteModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-file-alt me-2"></i>Teklif Detayları #<?= $quoteDetails['id'] ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <!-- Kullanıcı Bilgileri -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-user me-2"></i>Müşteri Bilgileri
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($quoteDetails['user_name']) ?></p>
                                        <p><strong>Telefon:</strong>
                                            <a href="tel:<?= htmlspecialchars($quoteDetails['user_phone']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($quoteDetails['user_phone']) ?>
                                            </a>
                                        </p>
                                        <p><strong>Email:</strong>
                                            <?php if ($quoteDetails['user_email']): ?>
                                                <a href="mailto:<?= htmlspecialchars($quoteDetails['user_email']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($quoteDetails['user_email']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Belirtilmemiş</span>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Şehir:</strong>
                                            <span class="badge bg-info"><?= htmlspecialchars($quoteDetails['city'] ?? 'Lefkoşa') ?></span>
                                        </p>
                                        <p><strong>Telefon Paylaşımı:</strong>
                                            <?php if ($quoteDetails['share_phone']): ?>
                                                <span class="badge bg-success">İzinli</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">İzinsiz</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Araç Bilgileri -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-success text-white">
                                        <i class="fas fa-car me-2"></i>Araç Bilgileri
                                    </div>
                                    <div class="card-body">
                                        <?php if ($quoteDetails['brand']): ?>
                                            <p><strong>Marka/Model:</strong> <?= htmlspecialchars($quoteDetails['brand'] . ' ' . $quoteDetails['model']) ?></p>
                                            <p><strong>Yıl:</strong> <?= htmlspecialchars($quoteDetails['year']) ?></p>
                                            <p><strong>Plaka:</strong>
                                                <span class="badge bg-dark"><?= htmlspecialchars($quoteDetails['plate']) ?></span>
                                            </p>
                                            <?php if ($quoteDetails['color']): ?>
                                                <p><strong>Renk:</strong> <?= htmlspecialchars($quoteDetails['color']) ?></p>
                                            <?php endif; ?>
                                            <?php if ($quoteDetails['last_service_date']): ?>
                                                <p><strong>Son Servis:</strong>
                                                    <small class="text-muted"><?= date('d.m.Y', strtotime($quoteDetails['last_service_date'])) ?></small>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($quoteDetails['insurance_expiry_date']): ?>
                                                <p><strong>Sigorta Bitişi:</strong>
                                                    <small class="text-muted"><?= date('d.m.Y', strtotime($quoteDetails['insurance_expiry_date'])) ?></small>
                                                </p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-muted">Araç bilgisi belirtilmemiş</p>
                                        <?php endif; ?>

                                        <!-- Extended Vehicle Details -->
                                        <?php if (!empty($quoteDetails['vehicle_details_parsed'])): ?>
                                            <hr>
                                            <h6 class="text-muted">Detaylı Araç Bilgileri:</h6>
                                            <div class="small">
                                                <?php foreach ($quoteDetails['vehicle_details_parsed'] as $key => $value): ?>
                                                    <?php if ($value && !in_array($key, ['id', 'user_id'])): ?>
                                                        <div class="mb-1">
                                                            <strong><?= ucfirst(str_replace('_', ' ', $key)) ?>:</strong>
                                                            <?= htmlspecialchars($value) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teklif Detayları -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-warning">
                                        <i class="fas fa-clipboard-list me-2"></i>Teklif Detayları
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Servis Türü:</strong>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($quoteDetails['service_type']) ?></span>
                                                </p>
                                                <p><strong>Başlık:</strong> <?= htmlspecialchars($quoteDetails['title']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Durum:</strong>
                                                    <?php
                                                    $statusColors = [
                                                        'pending' => 'warning',
                                                        'quoted' => 'info',
                                                        'accepted' => 'success',
                                                        'rejected' => 'danger',
                                                        'completed' => 'success',
                                                        'cancelled' => 'secondary'
                                                    ];
                                                    $statusColor = $statusColors[$quoteDetails['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $statusColor ?>"><?= ucfirst($quoteDetails['status']) ?></span>
                                                </p>
                                                <p><strong>Tarih:</strong>
                                                    <small class="text-muted"><?= date('d.m.Y H:i', strtotime($quoteDetails['created_at'])) ?></small>
                                                </p>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="mb-3">
                                            <strong>Açıklama:</strong>
                                            <div class="mt-2 p-3 bg-light border rounded">
                                                <?= nl2br(htmlspecialchars($quoteDetails['description'])) ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($quoteDetails['user_notes'])): ?>
                                            <div class="mb-3">
                                                <strong>Kullanıcı Notları:</strong>
                                                <div class="mt-2 p-3 bg-info bg-opacity-10 border border-info rounded">
                                                    <?= nl2br(htmlspecialchars($quoteDetails['user_notes'])) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Belgeler ve Ek Dosyalar -->
                        <?php if (
                            !empty($quoteDetails['attachments_parsed']) ||
                            ($quoteDetails['driving_license_photo'] && in_array($quoteDetails['service_type'], ['insurance', 'kasko']))
                        ): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-danger text-white">
                                            <i class="fas fa-paperclip me-2"></i>Belgeler ve Ek Dosyalar
                                        </div>
                                        <div class="card-body">
                                            <!-- Ehliyet Belgesi (Sigorta/Kasko için) -->
                                            <?php if ($quoteDetails['driving_license_photo'] && in_array($quoteDetails['service_type'], ['insurance', 'kasko'])): ?>
                                                <div class="mb-3">
                                                    <h6><i class="fas fa-id-card me-2"></i>Ehliyet Belgesi:</h6>
                                                    <div class="border rounded p-2">
                                                        <img src="<?= htmlspecialchars($quoteDetails['driving_license_photo']) ?>"
                                                            alt="Ehliyet Belgesi"
                                                            class="img-thumbnail"
                                                            style="max-width: 300px; max-height: 200px;">
                                                        <br>
                                                        <small class="text-muted">Sigorta/Kasko teklifi için otomatik eklendi</small>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Diğer Ek Dosyalar -->
                                            <?php if (!empty($quoteDetails['attachments_parsed'])): ?>
                                                <div class="mb-3">
                                                    <h6><i class="fas fa-file me-2"></i>Ek Dosyalar:</h6>
                                                    <div class="row">
                                                        <?php foreach ($quoteDetails['attachments_parsed'] as $key => $attachment): ?>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="border rounded p-2">
                                                                    <strong><?= ucfirst(str_replace('_', ' ', $key)) ?>:</strong><br>
                                                                    <?php if (is_string($attachment) && (strpos($attachment, '.jpg') !== false || strpos($attachment, '.png') !== false || strpos($attachment, '.jpeg') !== false)): ?>
                                                                        <img src="<?= htmlspecialchars($attachment) ?>"
                                                                            alt="<?= htmlspecialchars($key) ?>"
                                                                            class="img-thumbnail mt-1"
                                                                            style="max-width: 150px; max-height: 100px;">
                                                                    <?php else: ?>
                                                                        <a href="<?= htmlspecialchars($attachment) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-download me-1"></i>İndir
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Kapat
                        </button>
                        <a href="?action=quotes&edit=quote&id=<?= $quoteDetails['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Düzenle
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 3000);

        // Edit functions
        function editItem(type, id) {
            window.location.href = `?action=${type}&edit=${type}&id=${id}`;
        }

        // Auto-show modal if quote details are loaded
        <?php if ($quoteDetails): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('quoteModal'));
                modal.show();
            });
        <?php endif; ?>

        // Filtering functions
        function setupFilters() {
            // Users filtering
            const userFilter = document.getElementById('filterUsers');
            const userRoleFilter = document.getElementById('filterUserRole');
            if (userFilter && userRoleFilter) {
                userFilter.addEventListener('input', filterUsers);
                userRoleFilter.addEventListener('change', filterUsers);
            }

            // Providers filtering
            const providerFilter = document.getElementById('filterProviders');
            const providerCityFilter = document.getElementById('filterProviderCity');
            if (providerFilter && providerCityFilter) {
                providerFilter.addEventListener('input', filterProviders);
                providerCityFilter.addEventListener('change', filterProviders);
            }

            // News filtering
            const newsFilter = document.getElementById('filterNews');
            const newsCategoryFilter = document.getElementById('filterNewsCategory');
            const newsTypeFilter = document.getElementById('filterNewsType');
            if (newsFilter && newsCategoryFilter && newsTypeFilter) {
                newsFilter.addEventListener('input', filterNews);
                newsCategoryFilter.addEventListener('change', filterNews);
                newsTypeFilter.addEventListener('change', filterNews);
            }

            // Sliders filtering
            const sliderFilter = document.getElementById('filterSliders');
            const sliderStatusFilter = document.getElementById('filterSliderStatus');
            if (sliderFilter && sliderStatusFilter) {
                sliderFilter.addEventListener('input', filterSliders);
                sliderStatusFilter.addEventListener('change', filterSliders);
            }

            // Quotes filtering
            const quoteFilter = document.getElementById('filterQuotes');
            const quoteStatusFilter = document.getElementById('filterQuoteStatus');
            if (quoteFilter && quoteStatusFilter) {
                quoteFilter.addEventListener('input', filterQuotes);
                quoteStatusFilter.addEventListener('change', filterQuotes);
            }

            // Packages filtering
            const packageFilter = document.getElementById('filterPackages');
            const minPriceFilter = document.getElementById('filterMinPrice');
            const maxPriceFilter = document.getElementById('filterMaxPrice');
            if (packageFilter && minPriceFilter && maxPriceFilter) {
                packageFilter.addEventListener('input', filterPackages);
                minPriceFilter.addEventListener('input', filterPackages);
                maxPriceFilter.addEventListener('input', filterPackages);
            }

            // Notifications filtering
            const notificationFilter = document.getElementById('filterNotifications');
            const notificationTypeFilter = document.getElementById('filterNotificationType');
            const notificationTargetFilter = document.getElementById('filterTargetType');
            if (notificationFilter && notificationTypeFilter && notificationTargetFilter) {
                notificationFilter.addEventListener('input', filterNotifications);
                notificationTypeFilter.addEventListener('change', filterNotifications);
                notificationTargetFilter.addEventListener('change', filterNotifications);
            }

            // Inspection filtering
            const yearFilter = document.getElementById('filterYear');
            const plateCodeFilter = document.getElementById('filterPlateCode');
            const vehicleTypeFilter = document.getElementById('filterVehicleType');
            if (yearFilter && plateCodeFilter && vehicleTypeFilter) {
                yearFilter.addEventListener('change', filterInspection);
                plateCodeFilter.addEventListener('change', filterInspection);
                vehicleTypeFilter.addEventListener('change', filterInspection);
            }
        }

        function filterUsers() {
            const searchText = document.getElementById('filterUsers').value.toLowerCase();
            const selectedRole = document.getElementById('filterUserRole').value;
            const table = document.getElementById('usersTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const name = row.cells[1].textContent.toLowerCase();
                const phone = row.cells[2].textContent.toLowerCase();
                const email = row.cells[3].textContent.toLowerCase();
                const role = row.cells[4].textContent.trim();

                const matchesSearch = name.includes(searchText) || phone.includes(searchText) || email.includes(searchText);
                const matchesRole = !selectedRole || role.includes(selectedRole);

                row.style.display = matchesSearch && matchesRole ? '' : 'none';
            }
        }

        function filterProviders() {
            const searchText = document.getElementById('filterProviders').value.toLowerCase();
            const selectedCity = document.getElementById('filterProviderCity').value;
            const table = document.getElementById('providersTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const company = row.cells[1].textContent.toLowerCase();
                const contact = row.cells[2].textContent.toLowerCase();
                const phone = row.cells[3].textContent.toLowerCase();
                const city = row.cells[4].textContent.trim();
                const services = row.cells[5].textContent.toLowerCase();

                const matchesSearch = company.includes(searchText) || contact.includes(searchText) ||
                    phone.includes(searchText) || services.includes(searchText);
                const matchesCity = !selectedCity || city === selectedCity;

                row.style.display = matchesSearch && matchesCity ? '' : 'none';
            }
        }

        function filterNews() {
            const searchText = document.getElementById('filterNews').value.toLowerCase();
            const selectedCategory = document.getElementById('filterNewsCategory').value;
            const selectedType = document.getElementById('filterNewsType').value;
            const table = document.getElementById('newsTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const title = row.cells[1].textContent.toLowerCase();
                const category = row.cells[2].textContent.trim();
                const type = row.cells[3].textContent.trim();
                const author = row.cells[4].textContent.toLowerCase();

                const matchesSearch = title.includes(searchText) || author.includes(searchText);
                const matchesCategory = !selectedCategory || category.includes(selectedCategory);
                const matchesType = !selectedType || type.includes(selectedType);

                row.style.display = matchesSearch && matchesCategory && matchesType ? '' : 'none';
            }
        }

        function filterSliders() {
            const searchText = document.getElementById('filterSliders').value.toLowerCase();
            const selectedStatus = document.getElementById('filterSliderStatus').value;
            const table = document.getElementById('slidersTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const title = row.cells[1].textContent.toLowerCase();
                const description = row.cells[2].textContent.toLowerCase();
                const status = row.cells[5].textContent.trim();

                const matchesSearch = title.includes(searchText) || description.includes(searchText);
                const matchesStatus = !selectedStatus || status.includes(selectedStatus);

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            }
        }

        function filterQuotes() {
            const searchText = document.getElementById('filterQuotes').value.toLowerCase();
            const selectedStatus = document.getElementById('filterQuoteStatus').value;
            const table = document.getElementById('quotesTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const title = row.cells[1].textContent.toLowerCase();
                const user = row.cells[2].textContent.toLowerCase();
                const phone = row.cells[3].textContent.toLowerCase();
                const vehicle = row.cells[4].textContent.toLowerCase();
                const status = row.cells[5].textContent.trim().toLowerCase();

                const matchesSearch = title.includes(searchText) || user.includes(searchText) ||
                    phone.includes(searchText) || vehicle.includes(searchText);
                const matchesStatus = !selectedStatus || status.includes(selectedStatus.toLowerCase());

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            }
        }

        function filterPackages() {
            const searchText = document.getElementById('filterPackages').value.toLowerCase();
            const minPrice = parseFloat(document.getElementById('filterMinPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('filterMaxPrice').value) || Infinity;
            const table = document.getElementById('packagesTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const name = row.cells[1].textContent.toLowerCase();
                const priceText = row.cells[2].textContent;
                const price = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;

                const matchesSearch = name.includes(searchText);
                const matchesPrice = price >= minPrice && price <= maxPrice;

                row.style.display = matchesSearch && matchesPrice ? '' : 'none';
            }
        }

        function filterNotifications() {
            const searchText = document.getElementById('filterNotifications').value.toLowerCase();
            const selectedType = document.getElementById('filterNotificationType').value;
            const selectedTarget = document.getElementById('filterTargetType').value;
            const table = document.getElementById('notificationsTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const title = row.cells[1].textContent.toLowerCase();
                const type = row.cells[2].textContent.toLowerCase();
                const target = row.cells[3].textContent.toLowerCase();
                const message = row.cells[4].textContent.toLowerCase();

                const matchesSearch = title.includes(searchText) || message.includes(searchText);
                const matchesType = !selectedType || type.includes(selectedType.toLowerCase());
                const matchesTarget = !selectedTarget || target.includes(selectedTarget.toLowerCase());

                row.style.display = matchesSearch && matchesType && matchesTarget ? '' : 'none';
            }
        }

        function filterInspection() {
            const selectedYear = document.getElementById('filterYear').value;
            const selectedPlateCode = document.getElementById('filterPlateCode').value;
            const selectedVehicleType = document.getElementById('filterVehicleType').value;
            const table = document.getElementById('inspectionTable');

            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const year = row.cells[1].textContent.trim();
                const plateCode = row.cells[2].textContent.trim();
                const vehicleType = row.cells[3].textContent.trim();

                const matchesYear = !selectedYear || year === selectedYear;
                const matchesPlateCode = !selectedPlateCode || plateCode === selectedPlateCode;
                const matchesVehicleType = !selectedVehicleType || vehicleType.includes(selectedVehicleType);

                row.style.display = matchesYear && matchesPlateCode && matchesVehicleType ? '' : 'none';
            }
        }

        function toggleTargetValue(targetType) {
            const targetValueDiv = document.getElementById('targetValueDiv');
            const targetValueInput = document.querySelector('input[name="target_value"]');

            if (targetType === 'brand' || targetType === 'model' || targetType === 'city') {
                targetValueDiv.style.display = 'block';
                targetValueInput.required = true;

                // Update placeholder based on target type
                const placeholders = {
                    'brand': 'Araç markası (örn: Toyota, Honda)',
                    'model': 'Araç modeli (örn: Corolla, Civic)',
                    'city': 'Şehir adı (örn: İstanbul, Ankara)'
                };
                targetValueInput.placeholder = placeholders[targetType] || 'Değer girin';
            } else {
                targetValueDiv.style.display = 'none';
                targetValueInput.required = false;
                targetValueInput.value = '';
            }
        }

        function clearFilters(type) {
            switch (type) {
                case 'users':
                    document.getElementById('filterUsers').value = '';
                    document.getElementById('filterUserRole').value = '';
                    filterUsers();
                    break;
                case 'providers':
                    document.getElementById('filterProviders').value = '';
                    document.getElementById('filterProviderCity').value = '';
                    filterProviders();
                    break;
                case 'news':
                    document.getElementById('filterNews').value = '';
                    document.getElementById('filterNewsCategory').value = '';
                    document.getElementById('filterNewsType').value = '';
                    filterNews();
                    break;
                case 'sliders':
                    document.getElementById('filterSliders').value = '';
                    document.getElementById('filterSliderStatus').value = '';
                    filterSliders();
                    break;
                case 'quotes':
                    document.getElementById('filterQuotes').value = '';
                    document.getElementById('filterQuoteStatus').value = '';
                    filterQuotes();
                    break;
                case 'packages':
                    document.getElementById('filterPackages').value = '';
                    document.getElementById('filterMinPrice').value = '';
                    document.getElementById('filterMaxPrice').value = '';
                    filterPackages();
                    break;
                case 'notifications':
                    document.getElementById('filterNotifications').value = '';
                    document.getElementById('filterNotificationType').value = '';
                    document.getElementById('filterTargetType').value = '';
                    filterNotifications();
                    break;
                case 'inspection':
                    document.getElementById('filterYear').value = '';
                    document.getElementById('filterPlateCode').value = '';
                    document.getElementById('filterVehicleType').value = '';
                    filterInspection();
                    break;
            }
        }

        // Initialize filters when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupFilters();
        });
    </script>
</body>

</html>
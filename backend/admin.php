<?php
require_once 'config/database.php';

session_start();

// Admin authentication check
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

// Handle all POST actions (CRUD operations)
if ($_POST) {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'login':
            $token = $_POST['token'] ?? '';

            if ($admin = authenticateAdmin($token)) {
                $_SESSION['admin_token'] = $token;
                $_SESSION['admin_user'] = $admin;
                header('Location: admin.php');
                exit;
            } else {
                $error = "Geçersiz admin token!";
            }
            break;

        // ========== KULLANICI YÖNETİMİ ==========
        case 'create_user':
            if (isAdminLoggedIn()) {
                $result = createUser($_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'update_user':
            if (isAdminLoggedIn()) {
                $result = updateUser($_POST['user_id'], $_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'delete_user':
            if (isAdminLoggedIn()) {
                $result = deleteUser($_POST['user_id']);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        // ========== PAKET YÖNETİMİ ==========
        case 'create_package':
            if (isAdminLoggedIn()) {
                $result = createPackage($_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'update_package':
            if (isAdminLoggedIn()) {
                $result = updatePackage($_POST['package_id'], $_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'delete_package':
            if (isAdminLoggedIn()) {
                $result = deletePackage($_POST['package_id']);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'assign_package':
            if (isAdminLoggedIn()) {
                $result = assignPackageToProvider($_POST['provider_id'], $_POST['package_id']);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        // ========== HABER YÖNETİMİ ==========
        case 'create_news':
            if (isAdminLoggedIn()) {
                $result = createNews($_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'update_news':
            if (isAdminLoggedIn()) {
                $result = updateNews($_POST['news_id'], $_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'delete_news':
            if (isAdminLoggedIn()) {
                $result = deleteNews($_POST['news_id']);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        // ========== SLIDER YÖNETİMİ ==========
        case 'create_slider':
            if (isAdminLoggedIn()) {
                $result = createSlider($_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'update_slider':
            if (isAdminLoggedIn()) {
                $result = updateSlider($_POST['slider_id'], $_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'delete_slider':
            if (isAdminLoggedIn()) {
                $result = deleteSlider($_POST['slider_id']);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        // ========== TEKLİF YÖNETİMİ ==========
        case 'update_quote_request':
            if (isAdminLoggedIn()) {
                $result = updateQuoteRequest($_POST['quote_request_id'], $_POST);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;

        case 'delete_quote_request':
            if (isAdminLoggedIn()) {
                $result = deleteQuoteRequest($_POST['quote_request_id']);
                $message = $result['success'] ? $result['message'] : $result['error'];
            }
            break;
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ============ CRUD FUNCTIONS ============

// User Management Functions
function createUser($data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            INSERT INTO users (full_name, email, phone, role_id, is_active) 
            VALUES (?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['role_id'] ?? 1
        ]);

        return ['success' => true, 'message' => 'Kullanıcı başarıyla oluşturuldu'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Kullanıcı oluşturulamadı: ' . $e->getMessage()];
    }
}

function updateUser($id, $data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, role_id = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['role_id'],
            $id
        ]);

        return ['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Kullanıcı güncellenemedi: ' . $e->getMessage()];
    }
}

function deleteUser($id)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Kullanıcı başarıyla silindi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Kullanıcı silinemedi: ' . $e->getMessage()];
    }
}

// Package Management Functions
function createPackage($data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            INSERT INTO subscription_packages (name, description, price, duration_months, max_requests_per_month, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['duration_months'],
            $data['max_requests_per_month'] ?: null
        ]);

        return ['success' => true, 'message' => 'Paket başarıyla oluşturuldu'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Paket oluşturulamadı: ' . $e->getMessage()];
    }
}

function updatePackage($id, $data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            UPDATE subscription_packages 
            SET name = ?, description = ?, price = ?, duration_months = ?, max_requests_per_month = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['duration_months'],
            $data['max_requests_per_month'] ?: null,
            $id
        ]);

        return ['success' => true, 'message' => 'Paket başarıyla güncellendi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Paket güncellenemedi: ' . $e->getMessage()];
    }
}

function deletePackage($id)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("UPDATE subscription_packages SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Paket başarıyla silindi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Paket silinemedi: ' . $e->getMessage()];
    }
}

function assignPackageToProvider($providerId, $packageId)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        // Get package duration
        $stmt = $conn->prepare("SELECT duration_months FROM subscription_packages WHERE id = ?");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$package) {
            return ['success' => false, 'error' => 'Paket bulunamadı'];
        }

        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $package['duration_months'] . ' months'));

        // Deactivate existing subscriptions
        $stmt = $conn->prepare("UPDATE subscriptions SET is_active = 0 WHERE provider_id = ?");
        $stmt->execute([$providerId]);

        // Create new subscription
        $stmt = $conn->prepare("
            INSERT INTO subscriptions (provider_id, package_id, start_date, end_date, is_active, payment_status)
            VALUES (?, ?, ?, ?, 1, 'paid')
        ");

        $stmt->execute([$providerId, $packageId, $startDate, $endDate]);

        return ['success' => true, 'message' => 'Paket başarıyla atandı'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Paket atanamadı: ' . $e->getMessage()];
    }
}

// News Management Functions
function createNews($data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            INSERT INTO news (title, content, excerpt, image_url, category, is_featured, is_sponsored, author, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? '',
            $data['image_url'] ?? '',
            $data['category'] ?? 'general',
            isset($data['is_featured']) ? 1 : 0,
            isset($data['is_sponsored']) ? 1 : 0,
            $_SESSION['admin_user']['full_name'] ?? 'Admin'
        ]);

        return ['success' => true, 'message' => 'Haber başarıyla oluşturuldu'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Haber oluşturulamadı: ' . $e->getMessage()];
    }
}

function updateNews($id, $data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            UPDATE news 
            SET title = ?, content = ?, excerpt = ?, image_url = ?, category = ?, is_featured = ?, is_sponsored = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['excerpt'],
            $data['image_url'],
            $data['category'],
            isset($data['is_featured']) ? 1 : 0,
            isset($data['is_sponsored']) ? 1 : 0,
            $id
        ]);

        return ['success' => true, 'message' => 'Haber başarıyla güncellendi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Haber güncellenemedi: ' . $e->getMessage()];
    }
}

function deleteNews($id)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Haber başarıyla silindi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Haber silinemedi: ' . $e->getMessage()];
    }
}

// Slider Management Functions
function createSlider($data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        // Create sliders table if not exists
        $conn->exec("
            CREATE TABLE IF NOT EXISTS sliders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                image_url VARCHAR(500),
                link_url VARCHAR(500),
                is_active TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $stmt = $conn->prepare("
            INSERT INTO sliders (title, description, image_url, link_url, sort_order, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['image_url'] ?? '',
            $data['link_url'] ?? '',
            $data['sort_order'] ?? 0
        ]);

        return ['success' => true, 'message' => 'Slider başarıyla oluşturuldu'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Slider oluşturulamadı: ' . $e->getMessage()];
    }
}

function updateSlider($id, $data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            UPDATE sliders 
            SET title = ?, description = ?, image_url = ?, link_url = ?, sort_order = ?, is_active = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['image_url'],
            $data['link_url'],
            $data['sort_order'],
            isset($data['is_active']) ? 1 : 0,
            $id
        ]);

        return ['success' => true, 'message' => 'Slider başarıyla güncellendi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Slider güncellenemedi: ' . $e->getMessage()];
    }
}

function deleteSlider($id)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("DELETE FROM sliders WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Slider başarıyla silindi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Slider silinemedi: ' . $e->getMessage()];
    }
}

// Quote Management Functions
function updateQuoteRequest($id, $data)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            UPDATE quote_requests 
            SET title = ?, description = ?, status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['status'],
            $id
        ]);

        return ['success' => true, 'message' => 'Teklif talebi başarıyla güncellendi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Teklif talebi güncellenemedi: ' . $e->getMessage()];
    }
}

function deleteQuoteRequest($id)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("DELETE FROM quote_requests WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Teklif talebi başarıyla silindi'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Teklif talebi silinemedi: ' . $e->getMessage()];
    }
}

// Get dashboard stats
function getDashboardStats()
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stats = [];

        // Total users
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total providers
        $stmt = $conn->query("SELECT COUNT(*) as count FROM service_providers WHERE is_active = 1");
        $stats['total_providers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Active subscriptions
        $stmt = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE is_active = 1");
        $stats['active_subscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total quotes this month
        $stmt = $conn->query("SELECT COUNT(*) as count FROM quote_requests WHERE MONTH(created_at) = MONTH(NOW())");
        $stats['monthly_quotes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return $stats;
    } catch (Exception $e) {
        return [
            'total_users' => 0,
            'total_providers' => 0,
            'active_subscriptions' => 0,
            'monthly_quotes' => 0
        ];
    }
}

// Get data based on action
function getData($action)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'users':
                $stmt = $conn->query("
                    SELECT u.*, ur.name as role_name 
                    FROM users u
                    LEFT JOIN user_roles ur ON u.role_id = ur.id
                    ORDER BY u.created_at DESC LIMIT 50
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'providers':
                $stmt = $conn->query("
                    SELECT sp.*, u.full_name, u.phone, u.email, s.end_date as subscription_end, pkg.name as package_name
                    FROM service_providers sp
                    LEFT JOIN users u ON sp.user_id = u.id
                    LEFT JOIN subscriptions s ON sp.id = s.provider_id AND s.is_active = 1
                    LEFT JOIN subscription_packages pkg ON s.package_id = pkg.id
                    ORDER BY sp.created_at DESC LIMIT 50
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'packages':
                $stmt = $conn->query("
                    SELECT *, (SELECT COUNT(*) FROM subscriptions WHERE package_id = subscription_packages.id AND is_active = 1) as active_subscriptions
                    FROM subscription_packages 
                    ORDER BY price ASC
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'news':
                $stmt = $conn->query("
                    SELECT *, 
                           CASE WHEN is_sponsored = 1 THEN 'Sponsor' ELSE 'Normal' END as news_type
                    FROM news 
                    ORDER BY is_sponsored DESC, created_at DESC LIMIT 50
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'sliders':
                // Create sliders table if not exists
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS sliders (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        description TEXT,
                        image_url VARCHAR(500),
                        link_url VARCHAR(500),
                        is_active TINYINT(1) DEFAULT 1,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                $stmt = $conn->query("
                    SELECT * FROM sliders 
                    ORDER BY sort_order ASC, created_at DESC
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'quotes':
                $stmt = $conn->query("
                    SELECT qr.*, u.full_name as user_name, u.phone as user_phone, v.brand, v.model, v.plate
                    FROM quote_requests qr
                    JOIN users u ON qr.user_id = u.id
                    LEFT JOIN vehicles v ON qr.vehicle_id = v.id
                    ORDER BY qr.created_at DESC LIMIT 50
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'provider_requests':
                // Create provider_requests table if not exists
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS provider_requests (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        provider_id INT NOT NULL,
                        request_type ENUM('sponsorship', 'slider', 'advertisement', 'other') NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        description TEXT,
                        content TEXT,
                        budget DECIMAL(10,2),
                        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                        admin_notes TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                $stmt = $conn->query("
                    SELECT pr.*, sp.company_name as provider_name, u.full_name as contact_person
                    FROM provider_requests pr
                    JOIN service_providers sp ON pr.provider_id = sp.id
                    JOIN users u ON sp.user_id = u.id
                    ORDER BY pr.created_at DESC
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            default:
                return [];
        }
    } catch (Exception $e) {
        return [];
    }
}

$action = $_GET['action'] ?? 'dashboard';
$data = [];
$stats = [];

if (isAdminLoggedIn()) {
    $stats = getDashboardStats();
    if ($action !== 'dashboard') {
        $data = getData($action);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oto Asist - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .login-section {
            padding: 40px;
            text-align: center;
        }

        .auth-form {
            max-width: 400px;
            margin: 0 auto;
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }

        .btn {
            width: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .admin-panel {
            padding: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(45deg, #45b7d1, #96c93d);
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(45deg, #f093fb, #f5576c);
        }

        .stat-card h3 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
        }

        .nav-btn {
            background: white;
            border: 2px solid #e0e0e0;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            transition: all 0.3s;
        }

        .nav-btn:hover,
        .nav-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .data-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #2c3e50;
            color: white;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background: #e3f2fd;
        }

        .error {
            background: #ff6b6b;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .credentials {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .credentials h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .cred-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .cred-item:last-child {
            border-bottom: none;
        }

        .logout-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            float: right;
            margin-bottom: 20px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Oto Asist</h1>
            <p>Admin Panel Yönetim Sistemi</p>
            <?php if (isAdminLoggedIn()): ?>
                <p>Hoş geldiniz, <?= htmlspecialchars($_SESSION['admin_user']['full_name']) ?>!</p>
            <?php endif; ?>
        </div>

        <?php if (!isAdminLoggedIn()): ?>
            <div class="login-section">
                <div class="credentials">
                    <h3>📋 Admin Giriş Bilgileri</h3>
                    <div class="cred-item">
                        <strong>Token:</strong>
                        <span>+905551234567</span>
                    </div>
                    <div class="cred-item">
                        <strong>Kullanıcı:</strong>
                        <span>Ahmet Yılmaz</span>
                    </div>
                    <div class="cred-item">
                        <strong>Alternatif:</strong>
                        <span>333333333</span>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="auth-form">
                    <h2 style="margin-bottom: 20px; color: #2c3e50;">Admin Girişi</h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="form-group">
                            <label for="token">Admin Token:</label>
                            <input type="text" id="token" name="token" value="+905551234567" required>
                        </div>

                        <button type="submit" class="btn">🔐 Giriş Yap</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-panel">
                <a href="?logout=1" class="logout-btn">🚪 Çıkış</a>
                <div style="clear: both;"></div>

                <h2 style="margin-bottom: 30px; color: #2c3e50;">📊 Dashboard İstatistikleri</h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?= $stats['total_users'] ?></h3>
                        <p>Toplam Kullanıcı</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $stats['total_providers'] ?></h3>
                        <p>Servis Sağlayıcı</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $stats['active_subscriptions'] ?></h3>
                        <p>Aktif Abonelik</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $stats['monthly_quotes'] ?></h3>
                        <p>Aylık Teklif</p>
                    </div>
                </div>

                <h2 style="margin-bottom: 20px; color: #2c3e50;">🛠️ Yönetim İşlemleri</h2>

                <div class="navigation">
                    <a href="?action=dashboard" class="nav-btn <?= $action === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
                    <a href="?action=users" class="nav-btn <?= $action === 'users' ? 'active' : '' ?>">👥 Kullanıcılar</a>
                    <a href="?action=providers" class="nav-btn <?= $action === 'providers' ? 'active' : '' ?>">🏢 Servis Sağlayıcılar</a>
                    <a href="?action=packages" class="nav-btn <?= $action === 'packages' ? 'active' : '' ?>">📦 Paketler</a>
                    <a href="?action=news" class="nav-btn <?= $action === 'news' ? 'active' : '' ?>">📰 Haberler</a>
                    <a href="?action=sliders" class="nav-btn <?= $action === 'sliders' ? 'active' : '' ?>">🖼️ Sliderlar</a>
                    <a href="?action=quotes" class="nav-btn <?= $action === 'quotes' ? 'active' : '' ?>">💬 Teklifler</a>
                    <a href="?action=provider_requests" class="nav-btn <?= $action === 'provider_requests' ? 'active' : '' ?>">📋 Provider Talepleri</a>
                </div>

                <?php if (isset($message)): ?>
                    <div class="<?= strpos($message, 'başarıyla') !== false ? 'success' : 'error' ?>" style="background: <?= strpos($message, 'başarıyla') !== false ? '#4ecdc4' : '#ff6b6b' ?>; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- CRUD Forms & Content -->
                <?php if ($action === 'users'): ?>
                    <div style="margin-bottom: 30px;">
                        <h3>👥 Kullanıcı Yönetimi</h3>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <h4>Yeni Kullanıcı Ekle</h4>
                            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                                <input type="hidden" name="action" value="create_user">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ad Soyad:</label>
                                    <input type="text" name="full_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email:</label>
                                    <input type="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Telefon:</label>
                                    <input type="text" name="phone" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Rol:</label>
                                    <select name="role_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="1">User</option>
                                        <option value="2">Provider</option>
                                        <option value="3">Admin</option>
                                    </select>
                                </div>
                                <button type="submit" style="background: #4ecdc4; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">➕ Ekle</button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($action === 'packages'): ?>
                    <div style="margin-bottom: 30px;">
                        <h3>📦 Paket Yönetimi</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                                <h4>Yeni Paket Ekle</h4>
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_package">
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Paket Adı:</label>
                                        <input type="text" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Açıklama:</label>
                                        <textarea name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                                        <div>
                                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Fiyat (₺):</label>
                                            <input type="number" name="price" step="0.01" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                        <div>
                                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Süre (Ay):</label>
                                            <input type="number" name="duration_months" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                    </div>
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Max İstek (Aylık):</label>
                                        <input type="number" name="max_requests_per_month" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <button type="submit" style="background: #4ecdc4; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; width: 100%;">➕ Paket Ekle</button>
                                </form>
                            </div>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                                <h4>Paket Ata</h4>
                                <form method="POST">
                                    <input type="hidden" name="action" value="assign_package">
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Provider ID:</label>
                                        <input type="number" name="provider_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Paket ID:</label>
                                        <input type="number" name="package_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <button type="submit" style="background: #45b7d1; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; width: 100%;">🎯 Paket Ata</button>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action === 'news'): ?>
                    <div style="margin-bottom: 30px;">
                        <h3>📰 Haber Yönetimi</h3>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <h4>Yeni Haber Ekle</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_news">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Başlık:</label>
                                        <input type="text" name="title" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kategori:</label>
                                        <select name="category" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="general">Genel</option>
                                            <option value="teknoloji">Teknoloji</option>
                                            <option value="sigorta">Sigorta</option>
                                            <option value="bakım">Bakım</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">İçerik:</label>
                                    <textarea name="content" rows="5" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Özet:</label>
                                    <textarea name="excerpt" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Resim URL:</label>
                                    <input type="url" name="image_url" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; gap: 5px;">
                                        <input type="checkbox" name="is_sponsored"> Sponsor Haber
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 5px;">
                                        <input type="checkbox" name="is_featured"> Öne Çıkan
                                    </label>
                                </div>
                                <button type="submit" style="background: #4ecdc4; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">➕ Haber Ekle</button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($action === 'sliders'): ?>
                    <div style="margin-bottom: 30px;">
                        <h3>🖼️ Slider Yönetimi</h3>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <h4>Yeni Slider Ekle</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_slider">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Başlık:</label>
                                        <input type="text" name="title" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Sıra:</label>
                                        <input type="number" name="sort_order" value="0" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Açıklama:</label>
                                    <textarea name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Resim URL:</label>
                                        <input type="url" name="image_url" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Link URL:</label>
                                        <input type="url" name="link_url" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                </div>
                                <button type="submit" style="background: #4ecdc4; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">➕ Slider Ekle</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Data Tables -->
                <?php if ($action !== 'dashboard' && !empty($data)): ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <?php if ($action === 'users'): ?>
                                        <th>ID</th>
                                        <th>Ad Soyad</th>
                                        <th>Telefon</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>İşlemler</th>
                                    <?php elseif ($action === 'providers'): ?>
                                        <th>ID</th>
                                        <th>Şirket</th>
                                        <th>İletişim</th>
                                        <th>Telefon</th>
                                        <th>Paket</th>
                                        <th>İşlemler</th>
                                    <?php elseif ($action === 'packages'): ?>
                                        <th>ID</th>
                                        <th>Paket Adı</th>
                                        <th>Fiyat</th>
                                        <th>Süre</th>
                                        <th>Aktif Abonelik</th>
                                        <th>İşlemler</th>
                                    <?php elseif ($action === 'news'): ?>
                                        <th>ID</th>
                                        <th>Başlık</th>
                                        <th>Kategori</th>
                                        <th>Tip</th>
                                        <th>Yazar</th>
                                        <th>İşlemler</th>
                                    <?php elseif ($action === 'sliders'): ?>
                                        <th>ID</th>
                                        <th>Başlık</th>
                                        <th>Açıklama</th>
                                        <th>Sıra</th>
                                        <th>Aktif</th>
                                        <th>İşlemler</th>
                                    <?php elseif ($action === 'quotes'): ?>
                                        <th>ID</th>
                                        <th>Başlık</th>
                                        <th>Kullanıcı</th>
                                        <th>Araç</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    <?php elseif ($action === 'provider_requests'): ?>
                                        <th>ID</th>
                                        <th>Provider</th>
                                        <th>Tip</th>
                                        <th>Başlık</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <?php if ($action === 'users'): ?>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['phone']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['role_name'] ?? 'User') ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" onclick="return confirm('Emin misiniz?')" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">🗑️</button>
                                                </form>
                                            </td>
                                        <?php elseif ($action === 'providers'): ?>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                                            <td><?= htmlspecialchars($row['phone']) ?></td>
                                            <td><?= htmlspecialchars($row['package_name'] ?? 'Yok') ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                    <button type="submit" onclick="return confirm('Emin misiniz?')" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">🗑️</button>
                                                </form>
                                            </td>
                                        <?php elseif ($action === 'packages'): ?>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= $row['price'] ?> ₺</td>
                                            <td><?= $row['duration_months'] ?> ay</td>
                                            <td><?= $row['active_subscriptions'] ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_package">
                                                    <input type="hidden" name="package_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" onclick="return confirm('Emin misiniz?')" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">🗑️</button>
                                                </form>
                                            </td>
                                        <?php elseif ($action === 'news'): ?>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars(substr($row['title'], 0, 30)) ?>...</td>
                                            <td><?= htmlspecialchars($row['category']) ?></td>
                                            <td><?= $row['news_type'] ?></td>
                                            <td><?= htmlspecialchars($row['author']) ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_news">
                                                    <input type="hidden" name="news_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" onclick="return confirm('Emin misiniz?')" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">🗑️</button>
                                                </form>
                                            </td>
                                        <?php elseif ($action === 'sliders'): ?>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars(substr($row['description'], 0, 30)) ?>...</td>
                                            <td><?= $row['sort_order'] ?></td>
                                            <td><?= $row['is_active'] ? '✅' : '❌' ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_slider">
                                                    <input type="hidden" name="slider_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" onclick="return confirm('Emin misiniz?')" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">🗑️</button>
                                                </form>
                                            </td>
                                        <?php elseif ($action === 'quotes'): ?>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                                            <td><?= htmlspecialchars($row['brand'] . ' ' . $row['model']) ?></td>
                                            <td><?= htmlspecialchars($row['status']) ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_quote_request">
                                                    <input type="hidden" name="quote_request_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" onclick="return confirm('Emin misiniz?')" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">🗑️</button>
                                                </form>
                                            </td>
                                        <?php elseif ($action === 'provider_requests'): ?>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= htmlspecialchars($row['provider_name']) ?></td>
                                            <td><?= htmlspecialchars($row['request_type']) ?></td>
                                            <td><?= htmlspecialchars($row['title']) ?></td>
                                            <td><?= htmlspecialchars($row['status']) ?></td>
                                            <td><?= $row['created_at'] ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($action !== 'dashboard'): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">📭 Bu bölümde henüz veri bulunmuyor.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
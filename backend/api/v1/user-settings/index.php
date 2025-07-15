<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// JWT doğrulama fonksiyonu
function validateToken() {
    // Test amaçlı token doğrulamasını devre dışı bırak
    return 'test_token';
    
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Token gerekli']);
        exit;
    }
    
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    // Basit token doğrulama - gerçek projede JWT kütüphanesi kullanılmalı
    return $token; // Şimdilik token'ı döndür
}

// Kullanıcı ID'sini token'dan al
function getUserIdFromToken($token) {
    // Gerçek projede JWT decode edilmeli
    // Şimdilik test için sabit bir user ID döndür
    return 1; // Test user ID
}

// Kullanıcının araç limitini kontrol et
function checkVehicleLimit($pdo, $userId) {
    try {
        // Kullanıcının role_id'sini al
        $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Kurumsal üye ise sınırsız, normal üye ise 4 araç
        $maxVehicles = ($user['role_id'] == 4) ? -1 : 4;
        
        // Mevcut araç sayısını al
        $stmt = $pdo->prepare("SELECT COUNT(*) as current_count FROM vehicles WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $currentCount = $stmt->fetch(PDO::FETCH_ASSOC)['current_count'];
        
        return [
            'max_vehicles' => $maxVehicles,
            'current_count' => $currentCount,
            'can_add_more' => $maxVehicles == -1 || $currentCount < $maxVehicles
        ];
    } catch (Exception $e) {
        return ['error' => 'Araç limiti kontrol edilemedi: ' . $e->getMessage()];
    }
}

// Kullanıcının üyelik bilgilerini al
function getUserMembershipInfo($pdo, $userId) {
    try {
        // Kullanıcının temel bilgilerini al
        $stmt = $pdo->prepare("
            SELECT 
                u.role_id,
                COALESCE(ur.role_name, 'user') as role_name
            FROM users u
            LEFT JOIN user_roles ur ON u.role_id = ur.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Aktif üyelik detaylarını al
        $stmt = $pdo->prepare("
            SELECT 
                mp.name as package_name,
                mp.description,
                mp.price,
                mp.duration_months,
                mp.features,
                um.start_date,
                um.end_date,
                um.payment_status,
                um.auto_renew
            FROM user_memberships um
            JOIN membership_packages mp ON um.package_id = mp.id
            WHERE um.user_id = ? 
            AND um.is_active = TRUE 
            AND um.payment_status = 'paid'
            AND NOW() BETWEEN um.start_date AND um.end_date
            ORDER BY um.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $membershipDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Üyelik türü ve kalan gün hesapla
        $membershipType = 'Normal Üyelik';
        $daysRemaining = 0;
        
        if ($user['role_id'] == 4) {
            if ($membershipDetails) {
                $membershipType = $membershipDetails['package_name'];
                $endDate = new DateTime($membershipDetails['end_date']);
                $now = new DateTime();
                $diff = $now->diff($endDate);
                $daysRemaining = $endDate > $now ? $diff->days : 0;
            } else {
                $membershipType = 'Kurumsal Üyelik';
            }
        }
        
        return [
            'membership_type' => $membershipType,
            'days_remaining' => $daysRemaining,
            'role_id' => $user['role_id'],
            'role_name' => $user['role_name'],
            'is_corporate' => $user['role_id'] == 4,
            'membership_details' => $membershipDetails ?: null
        ];
    } catch (Exception $e) {
        return ['error' => 'Üyelik bilgileri alınamadı: ' . $e->getMessage()];
    }
}

// Üyelik paketlerini al
function getMembershipPackages($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                name,
                description,
                price,
                duration_months,
                max_vehicles,
                features,
                is_active
            FROM membership_packages
            WHERE is_active = TRUE
            ORDER BY price ASC
        ");
        $stmt->execute();
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // JSON features'ı decode et
        foreach ($packages as &$package) {
            $package['features'] = json_decode($package['features'], true);
            $package['price'] = floatval($package['price']);
        }
        
        return $packages;
    } catch (Exception $e) {
        return ['error' => 'Üyelik paketleri alınamadı: ' . $e->getMessage()];
    }
}

// Üyelik satın alma işlemi
function purchaseMembership($pdo, $userId, $packageId, $paymentReference) {
    try {
        $pdo->beginTransaction();
        
        // Paket bilgilerini al
        $stmt = $pdo->prepare("SELECT * FROM membership_packages WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$package) {
            throw new Exception('Geçersiz paket');
        }
        
        // Kullanıcının rolünü güncelle (kurumsal üye yap)
        $stmt = $pdo->prepare("UPDATE users SET role_id = 4 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Mevcut aktif üyelikleri pasifleştir
        $stmt = $pdo->prepare("UPDATE user_memberships SET is_active = FALSE WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Yeni üyelik oluştur
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime("+{$package['duration_months']} months"));
        
        $stmt = $pdo->prepare("
            INSERT INTO user_memberships 
            (user_id, package_id, start_date, end_date, payment_status, payment_reference, is_active)
            VALUES (?, ?, ?, ?, 'paid', ?, TRUE)
        ");
        $stmt->execute([$userId, $packageId, $startDate, $endDate, $paymentReference]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Üyelik başarıyla satın alındı',
            'membership_id' => $pdo->lastInsertId(),
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['error' => 'Üyelik satın alınamadı: ' . $e->getMessage()];
    }
}

// API endpoint yönetimi
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

try {
    $host = 'localhost';
    $dbname = 'otoasist';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($method) {
        case 'GET':
            if ($path === '/vehicle-limit') {
                $token = validateToken();
                $userId = getUserIdFromToken($token);
                $result = checkVehicleLimit($pdo, $userId);
                echo json_encode($result);
                
            } elseif ($path === '/membership-info') {
                $token = validateToken();
                $userId = getUserIdFromToken($token);
                $result = getUserMembershipInfo($pdo, $userId);
                echo json_encode($result);
                
            } elseif ($path === '/membership-packages') {
                $result = getMembershipPackages($pdo);
                echo json_encode($result);
                
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint bulunamadı']);
            }
            break;
            
        case 'POST':
            if ($path === '/purchase-membership') {
                $token = validateToken();
                $userId = getUserIdFromToken($token);
                
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['package_id']) || !isset($input['payment_reference'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Paket ID ve ödeme referansı gerekli']);
                    exit;
                }
                
                $result = purchaseMembership($pdo, $userId, $input['package_id'], $input['payment_reference']);
                echo json_encode($result);
                
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint bulunamadı']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method desteklenmiyor']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Sunucu hatası: ' . $e->getMessage()]);
} 
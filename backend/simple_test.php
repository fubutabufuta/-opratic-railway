<?php
// Web güvenli CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With');
header('Access-Control-Allow-Credentials: false');
header('Access-Control-Max-Age: 86400'); // 24 saat cache

// Preflight OPTIONS isteklerini handle et
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'config/database.php';

// Veritabanı bağlantısı
function getConnection() {
    try {
        $database = new Database();
        return $database->getConnection();
    } catch (Exception $e) {
        return null;
    }
}

// Kullanıcının gerçek araç sayısını al
function getRealVehicleCount($userId = 1) {
    try {
        $pdo = getConnection();
        if (!$pdo) return 3; // Fallback
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 3; // Fallback
    }
}

// Kullanıcının üyelik bilgilerini al
function getUserMembership($userId = 1) {
    try {
        $pdo = getConnection();
        if (!$pdo) return null;
        
        $stmt = $pdo->prepare("
            SELECT u.role_id, um.package_id, um.expires_at, mp.name as package_name, mp.max_vehicles, mp.price
            FROM users u
            LEFT JOIN user_memberships um ON u.id = um.user_id AND um.is_active = 1
            LEFT JOIN membership_packages mp ON um.package_id = mp.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

// Slider verilerini al
function getSliders() {
    try {
        $pdo = getConnection();
        if (!$pdo) return [];
        
        // Slider tablosunu oluştur
        $pdo->exec("CREATE TABLE IF NOT EXISTS sliders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            link_url VARCHAR(500),
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Test verilerini ekle
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sliders");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $sliders = [
                ['Kış Lastiği Kampanyası', 'Güvenli sürüş için kış lastiklerinde %25 indirim!', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=400&fit=crop', '#', 1],
                ['Motor Yağı Değişimi', 'Aracınızın performansını artırmak için motor yağı değişimi', 'https://images.unsplash.com/photo-1632823469662-6c4d0f2c3b4c?w=800&h=400&fit=crop', '#', 2],
                ['Ücretsiz Araç Kontrolü', 'Kapsamlı araç kontrolü ile güvenli yolculuk', 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=400&fit=crop', '#', 3]
            ];
            
            foreach ($sliders as $slider) {
                $stmt = $pdo->prepare("INSERT INTO sliders (title, description, image_url, link_url, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute($slider);
            }
        }
        
        $stmt = $pdo->prepare("SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// News verilerini al
function getNews($limit = 6) {
    try {
        $pdo = getConnection();
        if (!$pdo) return [];
        
        // News tablosunu oluştur
        $pdo->exec("CREATE TABLE IF NOT EXISTS news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            content TEXT,
            image_url VARCHAR(500),
            category VARCHAR(100) DEFAULT 'genel',
            is_featured TINYINT(1) DEFAULT 0,
            is_sponsored TINYINT(1) DEFAULT 0,
            author VARCHAR(100),
            view_count INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Test verilerini ekle
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM news");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $news = [
                ['Kış Lastiği Zorunluluğu Başladı', 'Kış mevsimi ile birlikte araçlarda kış lastiği kullanımı zorunlu hale geldi.', 'Meteoroloji açıklamasına göre kış lastiği zorunluluğu 1 Aralık itibariyle başladı. Detaylar haberimizde...', 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=400&h=200&fit=crop', 'trafik', 1, 0, 'Haber Editörü', 125],
                ['Araç Muayene Ücretlerine Güncelleme', 'Araç muayene ücretlerinde yeni düzenleme yapıldı.', 'Araç muayene ücretlerinde %15 oranında artış yapıldı. Yeni tarife bilgileri...', 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=400&h=200&fit=crop', 'mevzuat', 0, 0, 'Haber Editörü', 89],
                ['Elektrikli Araç Teşvikleri', 'Elektrikli araç alımında yeni teşvik paketi açıklandı.', 'Çevre dostu araçlar için önemli destek paketi. Elektrikli araç alımında yeni teşvik...', 'https://images.unsplash.com/photo-1593941707882-a5bac6861d75?w=400&h=200&fit=crop', 'teknoloji', 1, 1, 'Haber Editörü', 156],
                ['Trafik Sigortasında Yeni Dönem', 'Sigorta şirketleri yeni tarife yapısını açıkladı.', '2024 yılı trafik sigortası primlerinde değişiklik oldu. Detaylar...', 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=400&h=200&fit=crop', 'sigorta', 0, 0, 'Haber Editörü', 78]
            ];
            
            foreach ($news as $item) {
                $stmt = $pdo->prepare("INSERT INTO news (title, description, content, image_url, category, is_featured, is_sponsored, author, view_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute($item);
            }
        }
        
        $stmt = $pdo->prepare("SELECT * FROM news WHERE is_active = 1 ORDER BY is_featured DESC, created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Gerçek araç verilerini al
function getRealVehicles($userId = 1) {
    try {
        $pdo = getConnection();
        if (!$pdo) return [];
        
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// URL'den path'i al
$path = $_GET['path'] ?? '';

switch ($path) {
    case 'membership-info':
        $membership = getUserMembership();
        $vehicleCount = getRealVehicleCount();
        
        if ($membership) {
            $maxVehicles = $membership['max_vehicles'] ?? 4;
            $packageName = $membership['package_name'] ?? 'Ücretsiz';
            $expiresAt = $membership['expires_at'];
            $roleId = $membership['role_id'];
            
            // Kalan gün hesapla
            $remainingDays = 0;
            if ($expiresAt) {
                $expiryDate = new DateTime($expiresAt);
                $today = new DateTime();
                $interval = $today->diff($expiryDate);
                $remainingDays = $interval->days;
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'package_name' => $packageName,
                    'max_vehicles' => $maxVehicles,
                    'current_vehicles' => $vehicleCount,
                    'remaining_days' => $remainingDays,
                    'expires_at' => $expiresAt,
                    'role_id' => $roleId,
                    'is_corporate' => $roleId == 4
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'package_name' => 'Ücretsiz',
                    'max_vehicles' => 4,
                    'current_vehicles' => $vehicleCount,
                    'remaining_days' => 0,
                    'expires_at' => null,
                    'role_id' => 1,
                    'is_corporate' => false
                ]
            ]);
        }
        break;
        
    case 'vehicle-limit':
        $membership = getUserMembership();
        $vehicleCount = getRealVehicleCount();
        
        if ($membership && $membership['role_id'] == 4) {
            // Kurumsal üye - sınırsız araç
            echo json_encode([
                'success' => true,
                'data' => [
                    'max_vehicles' => -1,
                    'current_vehicles' => $vehicleCount,
                    'can_add_more' => true,
                    'limit_reached' => false
                ]
            ]);
        } else {
            $maxVehicles = $membership['max_vehicles'] ?? 4;
            echo json_encode([
                'success' => true,
                'data' => [
                    'max_vehicles' => $maxVehicles,
                    'current_vehicles' => $vehicleCount,
                    'can_add_more' => $vehicleCount < $maxVehicles,
                    'limit_reached' => $vehicleCount >= $maxVehicles
                ]
            ]);
        }
        break;
        
    case 'membership-packages':
        $pdo = getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM membership_packages WHERE is_active = 1 ORDER BY price ASC");
            $stmt->execute();
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $packages
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı']);
        }
        break;
        
    case 'purchase-membership':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $packageId = $input['package_id'] ?? null;
            $paymentReference = $input['payment_reference'] ?? 'TEST_' . time();
            
            if (!$packageId) {
                echo json_encode(['success' => false, 'message' => 'Paket ID gerekli']);
                break;
            }
            
            $pdo = getConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı']);
                break;
            }
            
            try {
                $pdo->beginTransaction();
                
                // Paket bilgilerini al
                $stmt = $pdo->prepare("SELECT * FROM membership_packages WHERE id = ?");
                $stmt->execute([$packageId]);
                $package = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$package) {
                    throw new Exception('Paket bulunamadı');
                }
                
                // Kullanıcıyı kurumsal üye yap
                $stmt = $pdo->prepare("UPDATE users SET role_id = 4 WHERE id = 1");
                $stmt->execute();
                
                // Eski üyelikleri pasif yap
                $stmt = $pdo->prepare("UPDATE user_memberships SET is_active = 0 WHERE user_id = 1");
                $stmt->execute();
                
                // Yeni üyelik ekle
                $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $package['duration_days'] . ' days'));
                $stmt = $pdo->prepare("
                    INSERT INTO user_memberships (user_id, package_id, starts_at, expires_at, payment_reference, is_active) 
                    VALUES (1, ?, NOW(), ?, ?, 1)
                ");
                $stmt->execute([$packageId, $expiresAt, $paymentReference]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Kurumsal üyelik başarıyla satın alındı!',
                    'data' => [
                        'package_name' => $package['name'],
                        'expires_at' => $expiresAt,
                        'payment_reference' => $paymentReference
                    ]
                ]);
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo json_encode(['success' => false, 'message' => 'Satın alma hatası: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Sadece POST metodu desteklenir']);
        }
        break;
        
    case 'sliders':
        $sliders = getSliders();
        echo json_encode([
            'success' => true,
            'data' => $sliders
        ]);
        break;
        
    case 'news':
        $limit = $_GET['limit'] ?? 6;
        $news = getNews($limit);
        echo json_encode([
            'success' => true,
            'data' => $news
        ]);
        break;
        
    case 'vehicles':
        $vehicles = getRealVehicles();
        echo json_encode([
            'success' => true,
            'data' => $vehicles
        ]);
        break;
        
    default:
        echo json_encode([
            'error' => 'Unknown endpoint',
            'available' => [
                'membership-info',
                'vehicle-limit',
                'membership-packages',
                'purchase-membership',
                'sliders',
                'news',
                'vehicles'
            ]
        ]);
        break;
}
?> 
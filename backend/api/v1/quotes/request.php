<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!class_exists('Database')) {
    require_once __DIR__ . '/../../config/database.php';
}

class QuoteRequestAPI
{
    private $conn;

    public function __construct()
    {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            error_log("Database connection successful");
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->conn = null; // Continue without database for demo purposes
        }
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($method) {
                case 'GET':
                    $this->getUserQuoteRequests();
                    break;
                case 'POST':
                    $this->createQuoteRequest();
                    break;
                default:
                    $this->sendResponse(405, ['error' => 'Method not allowed']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    // Teklif talebi oluştur
    public function createQuoteRequest()
    {
        $rawInput = file_get_contents('php://input');
        error_log("=== Quote Request Debug ===");
        error_log("Raw input: " . $rawInput);

        // Check if input is empty
        if (empty($rawInput)) {
            $this->sendResponse(400, [
                'error' => 'Request body is empty',
                'debug' => 'No JSON data received'
            ]);
            return;
        }

        $data = json_decode($rawInput, true);

        // Check JSON parsing error
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendResponse(400, [
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
                'debug' => 'Raw input: ' . substr($rawInput, 0, 200),
                'json_error_code' => json_last_error()
            ]);
            return;
        }

        // Check if data is null or not an array
        if ($data === null || !is_array($data)) {
            $this->sendResponse(400, [
                'error' => 'Invalid data format',
                'debug' => 'Parsed data is null or not an array',
                'data_type' => gettype($data),
                'raw_input' => substr($rawInput, 0, 200)
            ]);
            return;
        }

        error_log("Parsed data: " . print_r($data, true));

        try {
            // Validate required fields
            $required = ['user_id', 'vehicle_id', 'service_type'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    error_log("Missing required field: $field, value: " . ($data[$field] ?? 'not set'));
                    $this->sendResponse(400, [
                        'error' => 'Gerekli alanlar eksik',
                        'missing_field' => $field,
                        'provided_data' => array_keys($data),
                        'debug' => "Eksik alan: $field"
                    ]);
                    return;
                }
            }

            // Title yoksa description'dan oluştur veya varsayılan değer ata
            if (empty($data['title'])) {
                if (!empty($data['description'])) {
                    $data['title'] = substr($data['description'], 0, 100);
                } else {
                    // Service type'a göre varsayılan title oluştur
                    $titleMapping = [
                        'service' => 'Servis Talebi',
                        'maintenance' => 'Bakım Talebi',
                        'repair' => 'Tamir Talebi',
                        'insurance' => 'Sigorta Talebi',
                        'parts' => 'Yedek Parça Talebi',
                        'towing' => 'Çekici Talebi',
                        'other' => 'Hizmet Talebi'
                    ];
                    $data['title'] = $titleMapping[$data['service_type']] ?? 'Servis Talebi';
                }
            }

            error_log("All required fields present");

            // Validate service_type enum
            $validServiceTypes = ['maintenance', 'repair', 'insurance', 'parts', 'towing', 'other'];
            $serviceType = $data['service_type'];

            // Map common service types to valid enum values
            $serviceTypeMapping = [
                'service' => 'maintenance',
                'servis' => 'maintenance',
                'bakim' => 'maintenance',
                'sigorta' => 'insurance',
                'kasko' => 'insurance',
                'lastik' => 'parts',
                'parca' => 'parts',
                'yag' => 'maintenance'
            ];

            if (isset($serviceTypeMapping[strtolower($serviceType)])) {
                $serviceType = $serviceTypeMapping[strtolower($serviceType)];
            }

            if (!in_array($serviceType, $validServiceTypes)) {
                $serviceType = 'maintenance'; // Default fallback
            }

            error_log("Service type mapped to: " . $serviceType);

            // Check for existing quote request (prevent duplicates)
            if ($this->conn !== null) {
                $stmt = $this->conn->prepare("
                    SELECT id, status, created_at 
                    FROM quote_requests 
                    WHERE user_id = ? AND vehicle_id = ? AND service_type = ? 
                    AND status IN ('pending', 'quoted') 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$data['user_id'], $data['vehicle_id'], $serviceType]);
                $existing = $stmt->fetch();

                if ($existing) {
                    error_log("Duplicate quote request found: " . print_r($existing, true));
                    $this->sendResponse(409, [
                        'error' => 'Bu araç ve servis türü için zaten bekleyen bir teklif talebiniz var',
                        'existing_request' => $existing
                    ]);
                    return;
                }
            }

            // Get vehicle details if vehicle_id is provided
            $vehicleDetails = null;
            if (!empty($data['vehicle_id']) && $this->conn !== null) {
                try {
                    $vehicleStmt = $this->conn->prepare("SELECT * FROM vehicles WHERE id = ?");
                    $vehicleStmt->execute([$data['vehicle_id']]);
                    $vehicleDetails = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    error_log("Error fetching vehicle details: " . $e->getMessage());
                }
            }

            // Get user details including driving license for insurance/kasko requests
            $userDetails = null;
            $drivingLicensePhoto = null;
            if ($this->conn !== null && in_array($serviceType, ['insurance', 'kasko'])) {
                try {
                    $userStmt = $this->conn->prepare("SELECT driving_license_photo, name, phone, email FROM users WHERE id = ?");
                    $userStmt->execute([$data['user_id']]);
                    $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
                    $drivingLicensePhoto = $userDetails['driving_license_photo'] ?? null;
                } catch (Exception $e) {
                    error_log("Error fetching user details: " . $e->getMessage());
                }
            }

            // Prepare attachments array
            $attachments = [];
            if ($drivingLicensePhoto && in_array($serviceType, ['insurance', 'kasko'])) {
                $attachments['driving_license'] = $drivingLicensePhoto;
            }
            if (!empty($data['attachments'])) {
                $attachments = array_merge($attachments, $data['attachments']);
            }

            // Create quote request
            if ($this->conn !== null) {
                try {
                    // Detect database type and use appropriate syntax
                    $driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
                    $timestampFunction = ($driver === 'sqlite') ? "datetime('now')" : "NOW()";

                    $stmt = $this->conn->prepare("
                        INSERT INTO quote_requests 
                        (user_id, vehicle_id, service_type, title, description, user_notes, share_phone, vehicle_details, attachments, city, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', $timestampFunction)
                    ");

                    $result = $stmt->execute([
                        $data['user_id'],
                        $data['vehicle_id'],
                        $serviceType,
                        $data['title'],
                        $data['description'] ?? '',
                        $data['user_notes'] ?? '',
                        $data['share_phone'] ? 1 : 0,
                        $vehicleDetails ? json_encode($vehicleDetails) : null,
                        !empty($attachments) ? json_encode($attachments) : null,
                        $data['city'] ?? 'Lefkoşa'
                    ]);

                    if (!$result) {
                        error_log("Database insert failed: " . print_r($stmt->errorInfo(), true));
                        $this->sendResponse(500, ['error' => 'Teklif talebi kaydedilemedi: ' . $stmt->errorInfo()[2]]);
                        return;
                    }

                    $requestId = $this->conn->lastInsertId();
                    error_log("Quote request created with ID: " . $requestId);

                    // Servis sağlayıcılara bildirim gönder
                    $this->notifyProviders($requestId, $serviceType, $data);

                    $this->sendResponse(201, [
                        'success' => true,
                        'message' => 'Teklif talebi başarıyla oluşturuldu',
                        'request_id' => $requestId,
                        'service_type' => $serviceType
                    ]);
                } catch (Exception $dbError) {
                    error_log("Database operation failed: " . $dbError->getMessage());
                    $this->sendResponse(500, ['error' => 'Veritabanı hatası: ' . $dbError->getMessage()]);
                }
            } else {
                // No database connection - return demo response
                error_log("No database connection, returning demo response");
                $this->sendResponse(201, [
                    'success' => true,
                    'message' => 'Teklif talebi başarıyla oluşturuldu (demo mode)',
                    'request_id' => rand(1000, 9999),
                    'debug' => 'Database not available, using demo mode'
                ]);
            }
        } catch (Exception $e) {
            error_log("Quote request error: " . $e->getMessage());
            $this->sendResponse(500, ['error' => 'Sunucu hatası: ' . $e->getMessage()]);
        }
    }

    // Kullanıcının teklif taleplerini getir
    public function getUserQuoteRequests()
    {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId) {
            $this->sendResponse(400, ['error' => 'User ID gerekli']);
            return;
        }

        error_log("Getting quote requests for user ID: " . $userId);

        try {
            if ($this->conn === null) {
                error_log("No database connection, returning empty data");
                $this->sendResponse(200, ['success' => true, 'data' => []]);
                return;
            }

            $stmt = $this->conn->prepare("
                SELECT qr.*, 
                       v.brand, v.model, v.year, v.plate,
                       qr.service_type as service_name,
                       0 as quote_count
                FROM quote_requests qr
                LEFT JOIN vehicles v ON qr.vehicle_id = v.id
                WHERE qr.user_id = ?
                ORDER BY qr.created_at DESC
            ");

            $stmt->execute([$userId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Found " . count($requests) . " quote requests for user " . $userId);

            $this->sendResponse(200, ['success' => true, 'data' => $requests]);
        } catch (Exception $e) {
            error_log("Get user quotes error: " . $e->getMessage());
            // Fallback to empty data
            $this->sendResponse(200, ['success' => true, 'data' => []]);
        }
    }

    // Servis sağlayıcılara bildirim gönder
    private function notifyProviders($requestId, $serviceType, $data)
    {
        if ($this->conn === null) {
            error_log("No database connection for notifications");
            return;
        }

        try {
            // Önce quote request detaylarını al
            $stmt = $this->conn->prepare("
                SELECT qr.*, u.name, v.brand, v.model 
                FROM quote_requests qr 
                JOIN users u ON qr.user_id = u.id 
                LEFT JOIN vehicles v ON qr.vehicle_id = v.id 
                WHERE qr.id = ?
            ");
            $stmt->execute([$requestId]);
            $quoteRequest = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$quoteRequest) {
                error_log("Quote request not found for ID: " . $requestId);
                return;
            }

            // Kullanıcının şehrini al
            $userCity = $quoteRequest['city'] ?? $data['city'] ?? 'Lefkoşa'; // Default olarak Lefkoşa

            // Hizmet eşleştirmesi - daha geniş kapsamlı eşleştirme
            $serviceMapping = [
                'maintenance' => 'Servis ve Bakım',
                'service' => 'Servis ve Bakım',
                'repair' => 'Motor Tamiri',
                'parts' => 'Lastik Değişimi',
                'towing' => 'Çekici Hizmeti',
                'insurance' => 'Sigorta',
                'other' => 'Servis ve Bakım'
            ];

            $mappedService = $serviceMapping[$serviceType] ?? 'Servis ve Bakım';

            error_log("Looking for providers in city: $userCity, service_type: $serviceType, mapped_service: $mappedService");

            // Daha esnek provider arama - önce spesifik şehir ve hizmet ile ara
            $stmt = $this->conn->prepare("
                SELECT sp.*, u.id as user_id, u.full_name, u.phone, u.email 
                FROM service_providers sp 
                JOIN users u ON sp.user_id = u.id 
                WHERE sp.city = ? 
                AND (
                    sp.services LIKE ? OR 
                    sp.services LIKE ? OR 
                    sp.services LIKE ? OR
                    sp.services LIKE ? OR
                    sp.services LIKE ?
                )
                AND u.role_id = 2
                LIMIT 10
            ");

            $stmt->execute([
                $userCity,
                '%' . $mappedService . '%',
                '%Servis%',
                '%Bakım%',
                '%Genel Servis%',
                '%' . $serviceType . '%'
            ]);
            $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Found " . count($providers) . " providers for service: " . $mappedService . " in city: " . $userCity);

            // Eğer şehirde provider bulunamadıysa, tüm provider'lara bildirim gönder
            if (empty($providers)) {
                error_log("No providers found in city $userCity, searching all providers with matching services");

                $stmt = $this->conn->prepare("
                    SELECT sp.*, u.id as user_id, u.full_name, u.phone, u.email 
                    FROM service_providers sp 
                    JOIN users u ON sp.user_id = u.id 
                    WHERE (
                        sp.services LIKE ? OR 
                        sp.services LIKE ? OR 
                        sp.services LIKE ? OR
                        sp.services LIKE ? OR
                        sp.services LIKE ?
                    )
                    AND u.role_id = 2
                    LIMIT 10
                ");

                $stmt->execute([
                    '%' . $mappedService . '%',
                    '%Servis%',
                    '%Bakım%',
                    '%Genel Servis%',
                    '%' . $serviceType . '%'
                ]);
                $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                error_log("Found " . count($providers) . " providers with matching services across all cities");
            }

            // Her servis sağlayıcıya bildirim gönder
            foreach ($providers as $provider) {
                try {
                    // Notifications tablosunu oluştur
                    $this->conn->exec("CREATE TABLE IF NOT EXISTS notifications (
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

                    // Bildirim oluştur
                    $notifStmt = $this->conn->prepare("
                        INSERT INTO notifications 
                        (title, message, user_id, notification_type, status, created_at)
                        VALUES (?, ?, ?, 'quote_request', 'active', NOW())
                    ");

                    $title = "🚗 Yeni Teklif Talebi";
                    $message = sprintf(
                        "%s kullanıcısından %s hizmeti için yeni teklif talebi. Araç: %s %s. Teklif ID: %s",
                        $quoteRequest['name'],
                        $mappedService,
                        $quoteRequest['brand'] ?? 'Bilinmeyen',
                        $quoteRequest['model'] ?? 'Model',
                        $requestId
                    );

                    $result = $notifStmt->execute([
                        $title,
                        $message,
                        $provider['user_id']
                    ]);

                    if ($result) {
                        error_log("Notification sent to provider: " . ($provider['company_name'] ?? $provider['full_name']) . " (User ID: " . $provider['user_id'] . ")");
                    } else {
                        error_log("Failed to send notification to provider: " . ($provider['company_name'] ?? $provider['full_name']));
                    }
                } catch (Exception $e) {
                    error_log("Error sending notification to provider " . ($provider['company_name'] ?? $provider['full_name']) . ": " . $e->getMessage());
                }
            }

            // Eğer hiç provider bulunamadıysa genel bildirim gönder
            if (empty($providers)) {
                error_log("No providers found for any criteria, sending general notification to all active providers");

                // Tüm aktif provider'lara genel bildirim gönder
                $stmt = $this->conn->prepare("
                    SELECT sp.*, u.id as user_id, u.full_name 
                    FROM service_providers sp 
                    JOIN users u ON sp.user_id = u.id 
                    WHERE u.role_id = 2
                    AND sp.is_active = 1
                    LIMIT 5
                ");
                $stmt->execute();
                $allProviders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($allProviders as $provider) {
                    try {
                        $notifStmt = $this->conn->prepare("
                            INSERT INTO notifications 
                            (title, message, user_id, notification_type, status, created_at)
                            VALUES (?, ?, ?, 'quote_request', 'active', NOW())
                        ");

                        $title = "📢 Genel Teklif Talebi";
                        $message = "Yeni bir teklif talebi alındı. Detaylar için paneli kontrol edin. Teklif ID: " . $requestId;

                        $notifStmt->execute([$title, $message, $provider['user_id']]);
                        error_log("General notification sent to provider: " . ($provider['company_name'] ?? $provider['full_name']));
                    } catch (Exception $e) {
                        error_log("Error sending general notification: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error in notifyProviders: " . $e->getMessage());
        }
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$api = new QuoteRequestAPI();
$api->handleRequest();

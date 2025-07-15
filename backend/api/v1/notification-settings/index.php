<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        getNotificationSettings();
        break;
    case 'POST':
        updateNotificationSettings($input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

function getNotificationSettings()
{
    try {
        $database = new Database();
        $db = $database->getConnection();

        $user_id = $_GET['user_id'] ?? null;

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID gerekli']);
            return;
        }

        // Kullanıcının notification ayarlarını getir
        $query = "SELECT * FROM notification_settings WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            // Varsayılan ayarları oluştur
            $insertQuery = "INSERT INTO notification_settings (user_id, reminders, campaigns, news, maintenance, insurance, inspection, emergencies, location_based) VALUES (?, 1, 1, 1, 1, 1, 1, 1, 0)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$user_id]);

            // Yeni oluşturulan ayarları getir
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Boolean değerleri düzelt
        foreach (['reminders', 'campaigns', 'news', 'maintenance', 'insurance', 'inspection', 'emergencies', 'location_based'] as $field) {
            if (isset($settings[$field])) {
                $settings[$field] = (bool)$settings[$field];
            }
        }

        echo json_encode([
            "success" => true,
            "data" => $settings
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function updateNotificationSettings($input)
{
    try {
        $database = new Database();
        $db = $database->getConnection();

        $user_id = $input['user_id'] ?? null;

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID gerekli']);
            return;
        }

        // Ayarları güncelle veya oluştur
        $query = "
            INSERT INTO notification_settings (
                user_id, reminders, campaigns, news, maintenance, 
                insurance, inspection, emergencies, location_based
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                reminders = VALUES(reminders),
                campaigns = VALUES(campaigns),
                news = VALUES(news),
                maintenance = VALUES(maintenance),
                insurance = VALUES(insurance),
                inspection = VALUES(inspection),
                emergencies = VALUES(emergencies),
                location_based = VALUES(location_based),
                updated_at = NOW()
        ";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $user_id,
            $input['reminders'] ?? 1,
            $input['campaigns'] ?? 1,
            $input['news'] ?? 1,
            $input['maintenance'] ?? 1,
            $input['insurance'] ?? 1,
            $input['inspection'] ?? 1,
            $input['emergencies'] ?? 1,
            $input['location_based'] ?? 0
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Bildirim ayarları güncellendi"
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

updateFCMToken($input);

function updateFCMToken($input)
{
    try {
        $database = new Database();
        $db = $database->getConnection();

        $user_id = $input['user_id'] ?? null;
        $fcm_token = $input['fcm_token'] ?? null;

        if (!$user_id || !$fcm_token) {
            echo json_encode([
                "success" => false,
                "message" => "Kullanıcı ID ve FCM token gerekli"
            ]);
            return;
        }

        // FCM token'ı users tablosuna kaydet
        $query = "UPDATE users SET fcm_token = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);

        if ($stmt->execute([$fcm_token, $user_id])) {
            echo json_encode([
                "success" => true,
                "message" => "FCM token güncellendi"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "FCM token güncellenemedi"
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

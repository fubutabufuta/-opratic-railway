<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// B2B API Key kontrolü
$headers = getallheaders();
$apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';

if (!validateB2BApiKey($apiKey)) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Geçersiz API anahtarı"
    ]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

// B2B müşteri bilgilerini al
$b2bClient = getB2BClient($db, $apiKey);

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// Kampanya bildirimi gönder
sendCampaignNotification($db, $input, $b2bClient);

function validateB2BApiKey($apiKey)
{
    // API key formatı: b2b_xxxxxxxxxxxxxxxx
    return !empty($apiKey) && strpos($apiKey, 'b2b_') === 0;
}

function getB2BClient($db, $apiKey)
{
    $query = "SELECT * FROM b2b_clients WHERE api_key = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $apiKey);
    $stmt->execute();

    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "B2B müşteri bulunamadı veya aktif değil"
        ]);
        exit;
    }

    return $client;
}

function sendCampaignNotification($db, $input, $b2bClient)
{
    // Parametreleri kontrol et
    $title = $input['title'] ?? '';
    $message = $input['message'] ?? '';
    $targetType = $input['target_type'] ?? 'all'; // all, city, vehicle_brand
    $targetValue = $input['target_value'] ?? null;
    $campaignId = $input['campaign_id'] ?? null;
    $imageUrl = $input['image_url'] ?? null;
    $actionUrl = $input['action_url'] ?? null;

    if (empty($title) || empty($message)) {
        echo json_encode([
            "success" => false,
            "message" => "Başlık ve mesaj zorunludur"
        ]);
        return;
    }

    // B2B müşteri kotasını kontrol et
    if (!checkB2BQuota($db, $b2bClient['id'])) {
        echo json_encode([
            "success" => false,
            "message" => "Aylık bildirim kotanız dolmuştur"
        ]);
        return;
    }

    // Hedefleme kurallarını kontrol et
    if (!validateTargeting($targetType, $targetValue, $b2bClient)) {
        echo json_encode([
            "success" => false,
            "message" => "Geçersiz hedefleme parametreleri"
        ]);
        return;
    }

    // Bildirimi oluştur
    $query = "INSERT INTO notifications 
              (title, message, target_type, target_value, campaign_id, notification_type, 
               status, created_at, b2b_client_id, image_url, action_url) 
              VALUES (?, ?, ?, ?, ?, 'campaign', 'active', NOW(), ?, ?, ?)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $title);
    $stmt->bindParam(2, $message);
    $stmt->bindParam(3, $targetType);
    $stmt->bindParam(4, $targetValue);
    $stmt->bindParam(5, $campaignId);
    $stmt->bindParam(6, $b2bClient['id']);
    $stmt->bindParam(7, $imageUrl);
    $stmt->bindParam(8, $actionUrl);

    if ($stmt->execute()) {
        $notificationId = $db->lastInsertId();

        // Hedef kullanıcıları belirle ve bildirimleri gönder
        $targetUsers = getTargetUsers($db, $targetType, $targetValue);
        $sentCount = 0;

        foreach ($targetUsers as $user) {
            // Kullanıcının kampanya bildirimlerini kabul edip etmediğini kontrol et
            if (userAcceptsCampaigns($db, $user['id'])) {
                createUserNotification($db, $notificationId, $user['id']);

                // Push notification gönder
                if ($user['fcm_token']) {
                    // TODO: FCM push notification
                    // sendPushNotification($user['fcm_token'], $title, $message, $imageUrl, $actionUrl);
                }

                $sentCount++;
            }
        }

        // B2B müşteri kullanımını güncelle
        updateB2BUsage($db, $b2bClient['id'], $sentCount);

        // B2B bildirim logunu kaydet
        logB2BNotification($db, $b2bClient['id'], $notificationId, $sentCount);

        echo json_encode([
            "success" => true,
            "message" => "Kampanya bildirimi gönderildi",
            "notification_id" => $notificationId,
            "sent_count" => $sentCount,
            "target_count" => count($targetUsers)
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Bildirim oluşturulamadı"
        ]);
    }
}

function checkB2BQuota($db, $clientId)
{
    // Bu ay gönderilen bildirim sayısını kontrol et
    $query = "SELECT COUNT(*) as count FROM b2b_notification_logs 
              WHERE client_id = ? 
              AND MONTH(created_at) = MONTH(CURRENT_DATE())
              AND YEAR(created_at) = YEAR(CURRENT_DATE())";

    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $clientId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Müşteri kotasını al
    $query = "SELECT monthly_quota FROM b2b_clients WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $clientId);
    $stmt->execute();
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['count'] < $client['monthly_quota'];
}

function validateTargeting($targetType, $targetValue, $b2bClient)
{
    // B2B müşteri hedefleme izinlerini kontrol et
    $allowedTargets = json_decode($b2bClient['allowed_targets'], true) ?? ['all'];

    if (!in_array($targetType, $allowedTargets)) {
        return false;
    }

    // Hedef değeri kontrolü
    if ($targetType != 'all' && empty($targetValue)) {
        return false;
    }

    return true;
}

function getTargetUsers($db, $targetType, $targetValue)
{
    switch ($targetType) {
        case 'all':
            $query = "SELECT id, fcm_token FROM users WHERE status = 'active'";
            $stmt = $db->prepare($query);
            break;

        case 'city':
            $query = "SELECT id, fcm_token FROM users WHERE city = ? AND status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $targetValue);
            break;

        case 'vehicle_brand':
            $query = "SELECT DISTINCT u.id, u.fcm_token 
                      FROM users u 
                      JOIN vehicles v ON u.id = v.user_id 
                      WHERE v.brand = ? AND u.status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $targetValue);
            break;

        default:
            return [];
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function userAcceptsCampaigns($db, $userId)
{
    $query = "SELECT campaigns_enabled FROM notification_settings WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $userId);
    $stmt->execute();

    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ayar yoksa varsayılan olarak kabul ediyor sayılır
    return !$settings || $settings['campaigns_enabled'] == 1;
}

function createUserNotification($db, $notificationId, $userId)
{
    $query = "INSERT INTO user_notifications (notification_id, user_id, is_read, created_at) 
              VALUES (?, ?, 0, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $notificationId);
    $stmt->bindParam(2, $userId);
    $stmt->execute();
}

function updateB2BUsage($db, $clientId, $sentCount)
{
    $query = "UPDATE b2b_clients 
              SET total_notifications_sent = total_notifications_sent + ?,
                  last_notification_at = NOW()
              WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $sentCount);
    $stmt->bindParam(2, $clientId);
    $stmt->execute();
}

function logB2BNotification($db, $clientId, $notificationId, $sentCount)
{
    $query = "INSERT INTO b2b_notification_logs 
              (client_id, notification_id, sent_count, created_at) 
              VALUES (?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $clientId);
    $stmt->bindParam(2, $notificationId);
    $stmt->bindParam(3, $sentCount);
    $stmt->execute();
}

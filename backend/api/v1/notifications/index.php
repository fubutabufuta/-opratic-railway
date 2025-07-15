<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        getNotifications();
        break;
    case 'POST':
        handleNotificationPost($input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

function getNotifications()
{
    try {
        $database = new Database();
        $db = $database->getConnection();

        $user_id = $_GET['user_id'] ?? null;
        $unread_only = $_GET['unread_only'] ?? false;
        $limit = (int)($_GET['limit'] ?? 20);

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID gerekli']);
            return;
        }

        // Kullanıcının bildirimlerini getir
        $query = "
            SELECT 
                n.id,
                n.title,
                n.message,
                n.notification_type,
                n.target_type,
                n.target_value,
                n.campaign_id,
                n.status,
                n.is_read,
                n.created_at,
                n.image_url,
                n.action_url
            FROM notifications n 
            WHERE (n.user_id = ? OR n.user_id = 0 OR n.target_type = 'all')
            AND n.status = 'active'
            ORDER BY n.created_at DESC
            LIMIT ?
        ";

        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sadece okunmamışları filtrele
        if ($unread_only) {
            $notifications = array_filter($notifications, function ($n) {
                return !$n['is_read'];
            });
        }

        // Format response
        foreach ($notifications as &$notification) {
            $notification['is_read'] = (bool)$notification['is_read'];
            $notification['id'] = (int)$notification['id'];
            $notification['campaign_id'] = $notification['campaign_id'] ? (int)$notification['campaign_id'] : null;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => array_values($notifications)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function handleNotificationPost($input)
{
    try {
        $database = new Database();
        $db = $database->getConnection();

        $action = $input['action'] ?? '';

        if ($action === 'mark_read') {
            $notification_id = $input['notification_id'] ?? null;
            $user_id = $input['user_id'] ?? null;

            if (!$notification_id || !$user_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Notification ID ve User ID gerekli']);
                return;
            }

            // Bildirimi okundu olarak işaretle
            $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$notification_id, $user_id]);

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Bildirim okundu olarak işaretlendi']);
        } else {
            // Create notification
            $title = $input['title'] ?? '';
            $message = $input['message'] ?? '';
            $notification_type = $input['notification_type'] ?? 'general';
            $target_type = $input['target_type'] ?? 'all';
            $target_value = $input['target_value'] ?? null;
            $user_id = $input['user_id'] ?? 0;

            if (empty($title) || empty($message)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Title ve message gerekli']);
                return;
            }

            $query = "
                INSERT INTO notifications (
                    user_id, title, message, notification_type, 
                    target_type, target_value, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $user_id,
                $title,
                $message,
                $notification_type,
                $target_type,
                $target_value
            ]);

            $notification_id = $db->lastInsertId();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Bildirim oluşturuldu',
                'notification_id' => $notification_id
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

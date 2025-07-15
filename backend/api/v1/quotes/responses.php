<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    exit(0);
}

require_once __DIR__ . "/../../../config/database.php";

class QuoteResponsesAPI
{
    private $conn;

    public function __construct()
    {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->conn = null;
        }
    }

    public function handleRequest()
    {
        $method = $_SERVER["REQUEST_METHOD"];

        switch ($method) {
            case "GET":
                $this->getUserQuoteResponses();
                break;
            case "POST":
                $this->markAsRead();
                break;
            default:
                $this->sendResponse(405, ["error" => "Method not allowed"]);
                break;
        }
    }

    public function getUserQuoteResponses()
    {
        $userId = $_GET["user_id"] ?? null;
        if (!$userId) {
            $this->sendResponse(400, ["error" => "User ID gerekli"]);
            return;
        }

        try {
            if ($this->conn === null) {
                $this->sendResponse(200, [
                    "success" => true,
                    "data" => [],
                    "unread_count" => 0,
                    "total_count" => 0
                ]);
                return;
            }

            // Okunmamış yanıt sayısını hesapla
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as unread_count
                FROM quote_responses qres
                JOIN quote_requests qr ON qres.quote_request_id = qr.id
                WHERE qr.user_id = ? AND (qres.is_read = 0 OR qres.is_read IS NULL)
            ");
            $stmt->execute([$userId]);
            $unreadCount = $stmt->fetchColumn() ?: 0;

            $this->sendResponse(200, [
                "success" => true,
                "unread_count" => (int)$unreadCount
            ]);

        } catch (Exception $e) {
            error_log("Get quote responses error: " . $e->getMessage());
            $this->sendResponse(200, [
                "success" => true,
                "unread_count" => 0
            ]);
        }
    }

    public function markAsRead()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data["response_id"])) {
            $this->sendResponse(400, ["error" => "Response ID gerekli"]);
            return;
        }

        try {
            if ($this->conn === null) {
                $this->sendResponse(200, ["success" => true, "message" => "Demo mode"]);
                return;
            }

            $stmt = $this->conn->prepare("
                UPDATE quote_responses 
                SET is_read = 1 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$data["response_id"]]);

            $this->sendResponse(200, [
                "success" => true,
                "message" => "Yanıt okundu olarak işaretlendi"
            ]);

        } catch (Exception $e) {
            error_log("Mark as read error: " . $e->getMessage());
            $this->sendResponse(500, ["error" => "Sunucu hatası: " . $e->getMessage()]);
        }
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data);
    }
}

$api = new QuoteResponsesAPI();
$api->handleRequest();
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Eğer responses endpoint'i çağrılıyorsa responses.php'ye yönlendir
$requestUri = $_SERVER['REQUEST_URI'];
if (strpos($requestUri, '/responses') !== false) {
    include 'responses.php';
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

class QuoteAPI
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
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->getUserQuoteRequests();
                break;
            case 'POST':
                include 'request.php';
                break;
            default:
                $this->sendResponse(405, ['error' => 'Method not allowed']);
                break;
        }
    }

    public function getUserQuoteRequests()
    {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId) {
            $this->sendResponse(400, ['error' => 'User ID gerekli']);
            return;
        }

        try {
            if ($this->conn === null) {
                // Fallback demo data
                $this->sendResponse(200, [
                    'success' => true,
                    'data' => [],
                    'count' => 0
                ]);
                return;
            }

            $stmt = $this->conn->prepare("
                SELECT qr.*, 
                       v.brand, v.model, v.year, v.plate,
                       0 as quote_count
                FROM quote_requests qr
                LEFT JOIN vehicles v ON qr.vehicle_id = v.id
                WHERE qr.user_id = ?
                ORDER BY qr.created_at DESC
            ");

            $stmt->execute([$userId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->sendResponse(200, [
                'success' => true,
                'data' => $requests,
                'count' => count($requests)
            ]);
        } catch (Exception $e) {
            error_log("Get user quotes error: " . $e->getMessage());
            $this->sendResponse(500, ['error' => 'Sunucu hatası: ' . $e->getMessage()]);
        }
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data);
    }
}

// Initialize and handle request
$api = new QuoteAPI();
$api->handleRequest();

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Handle track endpoint
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '/track') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
            
            error_log("Analytics track request: " . print_r($data, true));
            
            // For now, just return success
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Event tracked']);
            exit();
        }
        
        // Default POST handler
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Analytics endpoint']);
    } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // Return basic analytics info
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => 1,
                'total_vehicles' => 0,
                'total_reminders' => 0
            ]
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 
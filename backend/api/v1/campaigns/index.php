<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // Query parameters
        $active_only = $_GET['active_only'] ?? 'true';
        $limit = (int)($_GET['limit'] ?? 20);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        // Build query
        $where = [];
        $params = [];

        if ($active_only === 'true') {
            $where[] = 'is_active = 1';
            $where[] = 'end_date > NOW()';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) FROM campaigns $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();

        // Get campaigns
        $sql = "SELECT id, title, description, image_url, is_active, created_at FROM campaigns $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'success' => true,
            'data' => $campaigns,
            'meta' => [
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ];
        http_response_code(200);
        echo json_encode($response);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

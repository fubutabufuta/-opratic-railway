<?php
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

        // Create sliders table if not exists
        $conn->exec("CREATE TABLE IF NOT EXISTS sliders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            link_url VARCHAR(500),
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            click_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Add click_count column if it doesn't exist
        try {
            $conn->exec("ALTER TABLE sliders ADD COLUMN click_count INT DEFAULT 0");
        } catch (PDOException $e) {
            // Column already exists, ignore error
        }

        // Check if sliders table is empty and add sample data
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM sliders");
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count == 0) {
            // Add sample sliders
            $sampleSliders = [
                [
                    'title' => 'Kış Lastiği Kampanyası',
                    'description' => 'Güvenli sürüş için kış lastiklerinde %25 indirim!',
                    'image_url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=400&fit=crop',
                    'link_url' => '#',
                    'sort_order' => 1
                ],
                [
                    'title' => 'Motor Yağı Değişimi',
                    'description' => 'Aracınızın performansını artırmak için motor yağı değişimi',
                    'image_url' => 'https://images.unsplash.com/photo-1632823469662-6c4d0f2c3b4c?w=800&h=400&fit=crop',
                    'link_url' => '#',
                    'sort_order' => 2
                ],
                [
                    'title' => 'Ücretsiz Araç Kontrolü',
                    'description' => 'Kapsamlı araç kontrolü ile güvenli yolculuk',
                    'image_url' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=400&fit=crop',
                    'link_url' => '#',
                    'sort_order' => 3
                ]
            ];

            foreach ($sampleSliders as $slider) {
                $insertStmt = $conn->prepare("INSERT INTO sliders (title, description, image_url, link_url, sort_order) VALUES (?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $slider['title'],
                    $slider['description'],
                    $slider['image_url'],
                    $slider['link_url'],
                    $slider['sort_order']
                ]);
            }
        }

        // Query parameters
        $active_only = $_GET['active_only'] ?? 'true';
        $limit = (int)($_GET['limit'] ?? 10);

        // Build query
        $where = [];
        $params = [];

        if ($active_only === 'true') {
            $where[] = 'is_active = 1';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get sliders
        $sql = "SELECT id, title, description, image_url, link_url, sort_order, is_active, 
                       COALESCE(click_count, 0) as click_count, created_at 
                FROM sliders 
                $whereClause 
                ORDER BY sort_order ASC, created_at DESC 
                LIMIT $limit";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format response
        foreach ($sliders as &$slider) {
            $slider['is_active'] = (bool)$slider['is_active'];
            $slider['sort_order'] = (int)$slider['sort_order'];
            $slider['click_count'] = (int)$slider['click_count'];
            $slider['created_at'] = date('Y-m-d H:i:s', strtotime($slider['created_at']));
        }

        // Return standardized API format
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $sliders
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

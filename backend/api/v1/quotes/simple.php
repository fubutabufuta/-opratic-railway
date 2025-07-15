<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create quotes table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS quote_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        vehicle_id INT DEFAULT NULL,
        service_type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = $_GET['user_id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID gerekli']);
            exit;
        }

        // Get user's quote requests
        $stmt = $db->prepare("SELECT * FROM quote_requests WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add some example quotes if none exist
        if (empty($quotes)) {
            $sampleQuotes = [
                ['Servis Teklifi', 'Aracım için servis teklifi istiyorum', 'Servis'],
                ['Sigorta Teklifi', 'Araç sigortası için teklif', 'Sigorta'],
                ['Lastik Değişimi', 'Kış lastiği takımı teklifi', 'Lastik'],
                ['Yağ Değişimi', 'Motor yağı değişimi teklifi', 'Yağ Değişimi'],
                ['Kasko Teklifi', 'Kasko poliçesi için teklif', 'Kasko']
            ];

            foreach ($sampleQuotes as $quote) {
                $stmt = $db->prepare("INSERT INTO quote_requests (user_id, title, description, service_type, status) VALUES (?, ?, ?, ?, 'completed')");
                $stmt->execute([$userId, $quote[0], $quote[1], $quote[2]]);
            }

            // Get updated quotes
            $stmt = $db->prepare("SELECT * FROM quote_requests WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'data' => $quotes,
            'count' => count($quotes)
        ]);
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['user_id'], $data['title'], $data['service_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Gerekli alanlar eksik']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO quote_requests (user_id, vehicle_id, title, description, service_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['user_id'],
            $data['vehicle_id'] ?? null,
            $data['title'],
            $data['description'] ?? '',
            $data['service_type']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Teklif talebi oluşturuldu',
            'id' => $db->lastInsertId()
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

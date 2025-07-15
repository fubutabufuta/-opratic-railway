<?php
// Ana API router - GET parametreleri ile çalışır

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $endpoint = $_GET['endpoint'] ?? '';
    
    switch ($endpoint) {
        case 'reminders':
            handleReminders($db);
            break;
        case 'campaigns':
            handleCampaigns($db);
            break;
        case 'sliders':
            handleSliders($db);
            break;
        case 'vehicles':
            handleVehicles($db);
            break;
        default:
            // Default response
            echo json_encode([
                'status' => 'success',
                'message' => 'OtoAsist API is working',
                'endpoint' => $endpoint,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// Reminders handler
function handleReminders($db) {
    $user_id = 1; // Test için sabit
    
    if (isset($_GET['action']) && $_GET['action'] === 'upcoming') {
        // Yaklaşan yenilemeler
        $query = "
            SELECT 
                'reminder' as type,
                r.vehicle_id,
                v.plate,
                v.brand,
                v.model,
                v.year,
                r.date as due_date,
                r.title,
                DATEDIFF(r.date, CURDATE()) as days_remaining
            FROM reminders r
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE v.user_id = ? 
                AND r.date >= CURDATE()
                AND DATEDIFF(r.date, CURDATE()) <= 90
                AND r.is_completed = 0
            ORDER BY r.date ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $renewals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($renewals);
    } else {
        // Normal reminders
        $vehicle_id = $_GET['vehicle_id'] ?? null;
        
        if ($vehicle_id) {
            $query = "
                SELECT r.*, v.plate as vehicle_plate, v.brand as vehicle_brand, v.model as vehicle_model
                FROM reminders r
                JOIN vehicles v ON r.vehicle_id = v.id
                WHERE v.user_id = ? AND r.vehicle_id = ?
                ORDER BY r.date ASC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $vehicle_id]);
        } else {
            $query = "
                SELECT r.*, v.plate as vehicle_plate, v.brand as vehicle_brand, v.model as vehicle_model
                FROM reminders r
                JOIN vehicles v ON r.vehicle_id = v.id
                WHERE v.user_id = ?
                ORDER BY r.date ASC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
        }
        
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $reminders
        ]);
    }
}

// Campaigns handler
function handleCampaigns($db) {
    $query = "SELECT * FROM campaigns WHERE is_active = 1 ORDER BY created_at DESC LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $campaigns
    ]);
}

// Sliders handler
function handleSliders($db) {
    $query = "SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $sliders
    ]);
}

// Vehicles handler
function handleVehicles($db) {
    $user_id = 1; // Test için sabit
    
    $query = "SELECT * FROM vehicles WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $vehicles
    ]);
}
?>

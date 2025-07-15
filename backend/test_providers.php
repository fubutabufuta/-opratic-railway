<?php
require_once "config/database.php";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT sp.*, u.full_name, u.role_id FROM service_providers sp JOIN users u ON sp.user_id = u.id");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Provider sayısı: " . count($providers) . "\n";
    foreach ($providers as $provider) {
        echo "Company: {$provider["company_name"]}\n";
        echo "User ID: {$provider["user_id"]}\n";
        echo "Role: {$provider["role_id"]}\n";
        echo "Services: {$provider["services"]}\n";
        echo "City: {$provider["city"]}\n\n";
    }
    
    // Test quote oluştur
    $stmt = $db->prepare("INSERT INTO quote_requests (user_id, service_type, title, description, city, status, created_at) VALUES (1, \"service\", \"Test Servis\", \"Test açıklama\", \"Lefkoşa\", \"pending\", NOW())");
    $stmt->execute();
    $requestId = $db->lastInsertId();
    echo "Test quote ID: $requestId\n";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>

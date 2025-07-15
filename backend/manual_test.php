<?php
require_once "config/database.php";
require_once "api/v1/quotes/request.php";

echo "=== MANual TEKLİF SİSTEMİ TESTİ ===\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test data
    $testData = [
        "user_id" => "1",
        "vehicle_id" => "1", 
        "service_type" => "service",
        "title" => "Manual Test Servis",
        "description" => "Manuel test için servis talebi",
        "city" => "Lefkoşa",
        "user_notes" => "Test notu",
        "share_phone" => true
    ];
    
    echo "Test verisi:\n";
    print_r($testData);
    
    // Quote request oluştur
    $stmt = $db->prepare("
        INSERT INTO quote_requests 
        (user_id, vehicle_id, service_type, title, description, city, user_notes, share_phone, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, \"pending\", NOW())
    ");
    
    $result = $stmt->execute([
        $testData["user_id"],
        $testData["vehicle_id"],
        "maintenance", // service -> maintenance mapping
        $testData["title"],
        $testData["description"],
        $testData["city"],
        $testData["user_notes"],
        $testData["share_phone"] ? 1 : 0
    ]);
    
    if ($result) {
        $requestId = $db->lastInsertId();
        echo "✅ Teklif oluşturuldu ID: $requestId\n\n";
        
        // Manuel provider bildirim sistemi
        echo "=== MANUEL PROVIDER BİLDİRİM ===\n";
        
        $serviceType = "maintenance";
        $userCity = "Lefkoşa";
        
        // Provider ara
        $stmt = $db->prepare("
            SELECT sp.*, u.id as user_id, u.full_name 
            FROM service_providers sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE sp.city = ? 
            AND (sp.services LIKE ? OR sp.services LIKE ? OR sp.services LIKE ?)
            AND u.role_id = 2
        ");
        
        $stmt->execute([
            $userCity,
            "%Servis%",
            "%Bakım%", 
            "%Genel%"
        ]);
        
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Bulunan provider sayısı: " . count($providers) . "\n";
        
        foreach ($providers as $provider) {
            echo "Provider: {$provider[\"company_name\"]} (User ID: {$provider[\"user_id\"]})\n";
            
            // Bildirim gönder
            $notifStmt = $db->prepare("
                INSERT INTO notifications 
                (title, message, user_id, notification_type, status, created_at)
                VALUES (?, ?, ?, \"quote_request\", \"active\", NOW())
            ");
            
            $title = "🚗 Yeni Teklif Talebi";
            $message = "Test kullanıcısından Servis ve Bakım hizmeti için yeni teklif talebi. Teklif ID: $requestId";
            
            $notifResult = $notifStmt->execute([
                $title,
                $message,
                $provider["user_id"]
            ]);
            
            if ($notifResult) {
                echo "  ✅ Bildirim gönderildi\n";
            } else {
                echo "  ❌ Bildirim gönderilemedi\n";
            }
        }
        
    } else {
        echo "❌ Teklif oluşturulamadı\n";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>

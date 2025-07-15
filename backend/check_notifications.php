<?php
require_once "config/database.php";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== BİLDİRİM KONTROL ===\n";
    
    // Son bildirimleri kontrol et
    $stmt = $db->query("SELECT n.*, u.name FROM notifications n JOIN users u ON n.user_id = u.id WHERE n.notification_type = \"quote_request\" ORDER BY n.created_at DESC LIMIT 10");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Toplam quote_request bildirimi: " . count($notifications) . "\n\n";
    
    foreach ($notifications as $notif) {
        echo "ID: {$notif["id"]}\n";
        echo "Title: {$notif["title"]}\n";
        echo "User: {$notif["name"]} (ID: {$notif["user_id"]})\n";
        echo "Message: {$notif["message"]}\n";
        echo "Created: {$notif["created_at"]}\n\n";
    }
    
    echo "=== TÜM BİLDİRİMLER ===\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM notifications");
    $total = $stmt->fetchColumn();
    echo "Toplam bildirim sayısı: $total\n";
    
    // User ID 4 (ProCar Servis) için bildirimleri kontrol et
    echo "\n=== USER ID 4 (ProCar Servis) BİLDİRİMLERİ ===\n";
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = 4 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $userNotifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userNotifs as $notif) {
        echo "- {$notif["title"]}\n";
        echo "  Message: {$notif["message"]}\n";
        echo "  Date: {$notif["created_at"]}\n\n";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>

<?php
require_once 'config/database.php';

echo "🔍 PROVIDER VE TEKLİF SİSTEMİ TESTİ\n";
echo "================================\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Mevcut provider'ları listele
    echo "1. MEVCUT SERVİS SAĞLAYICILAR:\n";
    $stmt = $db->query("
        SELECT sp.*, u.full_name, u.email, u.role_id 
        FROM service_providers sp 
        JOIN users u ON sp.user_id = u.id 
        ORDER BY sp.id
    ");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($providers)) {
        echo "❌ Hiç servis sağlayıcı bulunamadı!\n\n";

        // Demo provider oluştur
        echo "📝 Demo provider oluşturuluyor...\n";

        // Önce provider user'ı oluştur
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, phone, password_hash, role_id, created_at) 
            VALUES (?, ?, ?, ?, 2, NOW())
        ");
        $stmt->execute([
            'Test Servis',
            'test@servis.com',
            '+905551234567',
            password_hash('123456', PASSWORD_DEFAULT)
        ]);
        $userId = $db->lastInsertId();

        // Provider kaydı oluştur
        $stmt = $db->prepare("
            INSERT INTO service_providers 
            (user_id, company_name, city, services, description, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $userId,
            'Test Servis Merkezi',
            'Lefkoşa',
            'Servis ve Bakım,Motor Tamiri,Genel Servis',
            'Profesyonel otomotiv hizmetleri'
        ]);

        echo "✅ Demo provider oluşturuldu (ID: $userId)\n\n";

        // Provider'ları tekrar listele
        $stmt = $db->query("
            SELECT sp.*, u.full_name, u.email, u.role_id 
            FROM service_providers sp 
            JOIN users u ON sp.user_id = u.id 
            ORDER BY sp.id
        ");
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($providers as $provider) {
        echo "📋 Provider: {$provider['company_name']}\n";
        echo "   - User ID: {$provider['user_id']}\n";
        echo "   - Role ID: {$provider['role_id']}\n";
        echo "   - Şehir: {$provider['city']}\n";
        echo "   - Hizmetler: {$provider['services']}\n";
        echo "   - Aktif: " . ($provider['is_active'] ? 'Evet' : 'Hayır') . "\n\n";
    }

    // 2. Yeni test teklifi oluştur
    echo "2. TEST TEKLİF TALEBİ OLUŞTURULUYOR:\n";

    $stmt = $db->prepare("
        INSERT INTO quote_requests 
        (user_id, vehicle_id, service_type, title, description, city, status, created_at)
        VALUES (1, 1, 'service', 'Test Servis Talebi', 'Sistem testi için servis teklifi', 'Lefkoşa', 'pending', NOW())
    ");
    $stmt->execute();
    $requestId = $db->lastInsertId();

    echo "✅ Test teklifi oluşturuldu (ID: $requestId)\n\n";

    // 3. Provider eşleştirme testini çalıştır
    echo "3. PROVIDER EŞLEŞTİRME TESTİ:\n";

    $serviceType = 'service';
    $mappedService = 'Servis ve Bakım';
    $userCity = 'Lefkoşa';

    echo "🔍 Arama kriterleri:\n";
    echo "   - Service Type: $serviceType\n";
    echo "   - Mapped Service: $mappedService\n";
    echo "   - City: $userCity\n\n";

    // Eşleştirme sorgusu test et
    $stmt = $db->prepare("
        SELECT sp.*, u.id as user_id, u.full_name, u.phone, u.email 
        FROM service_providers sp 
        JOIN users u ON sp.user_id = u.id 
        WHERE sp.city = ? 
        AND (
            sp.services LIKE ? OR 
            sp.services LIKE ? OR 
            sp.services LIKE ? OR
            sp.services LIKE ? OR
            sp.services LIKE ?
        )
        AND u.role_id = 2
        LIMIT 10
    ");

    $stmt->execute([
        $userCity,
        '%' . $mappedService . '%',
        '%Servis%',
        '%Bakım%',
        '%Genel Servis%',
        '%' . $serviceType . '%'
    ]);
    $matchedProviders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "🎯 Eşleşen Provider'lar (" . count($matchedProviders) . "):\n";
    foreach ($matchedProviders as $provider) {
        echo "   ✅ {$provider['company_name']} (User ID: {$provider['user_id']})\n";
        echo "      Hizmetler: {$provider['services']}\n";
    }

    if (empty($matchedProviders)) {
        echo "   ❌ Hiç eşleşen provider bulunamadı!\n";

        // Tüm provider'ları dene
        echo "\n🔍 Tüm provider'ları kontrol ediyor...\n";
        $stmt = $db->prepare("
            SELECT sp.*, u.id as user_id, u.full_name 
            FROM service_providers sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE u.role_id = 2
            LIMIT 10
        ");
        $stmt->execute();
        $allProviders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allProviders as $provider) {
            echo "   📋 {$provider['company_name']} (User ID: {$provider['user_id']})\n";
        }
    }

    // 4. Bildirim sistemini test et
    echo "\n4. BİLDİRİM SİSTEMİ TESTİ:\n";

    if (!empty($matchedProviders)) {
        foreach ($matchedProviders as $provider) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO notifications 
                    (title, message, user_id, notification_type, status, created_at)
                    VALUES (?, ?, ?, 'quote_request', 'active', NOW())
                ");

                $title = "🚗 Test Teklif Talebi";
                $message = "Test kullanıcısından {$mappedService} hizmeti için test teklif talebi. Teklif ID: {$requestId}";

                $result = $stmt->execute([
                    $title,
                    $message,
                    $provider['user_id']
                ]);

                if ($result) {
                    echo "   ✅ Bildirim gönderildi: {$provider['company_name']} (User ID: {$provider['user_id']})\n";
                } else {
                    echo "   ❌ Bildirim gönderilemedi: {$provider['company_name']}\n";
                }
            } catch (Exception $e) {
                echo "   ⚠️ Bildirim hatası: {$provider['company_name']} - " . $e->getMessage() . "\n";
            }
        }
    }

    // 5. Notifications tablosunu kontrol et
    echo "\n5. BİLDİRİM KONTROL:\n";
    $stmt = $db->query("
        SELECT n.*, u.full_name as user_name 
        FROM notifications n 
        JOIN users u ON n.user_id = u.id 
        WHERE n.notification_type = 'quote_request' 
        ORDER BY n.created_at DESC 
        LIMIT 5
    ");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notifications as $notif) {
        echo "   📧 {$notif['title']} -> {$notif['user_name']} (User ID: {$notif['user_id']})\n";
        echo "      Mesaj: {$notif['message']}\n";
        echo "      Tarih: {$notif['created_at']}\n\n";
    }

    echo "🎯 TEST TAMAMLANDI!\n";
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
}

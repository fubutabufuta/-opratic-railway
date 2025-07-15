<?php
include_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Users Tablosunu Güncelleme ===\n";

    // Users tablosuna servis sağlayıcı alanlarını ekle
    $updates = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS company_name VARCHAR(255) NULL COMMENT 'Şirket adı (servis sağlayıcı için)'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL COMMENT 'Şehir (servis sağlayıcı için)'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS services TEXT NULL COMMENT 'Hizmetler (servis sağlayıcı için)'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Puan (servis sağlayıcı için)'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS provider_status VARCHAR(50) DEFAULT 'active' COMMENT 'Durum (servis sağlayıcı için)'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS description TEXT NULL COMMENT 'Açıklama (servis sağlayıcı için)'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL COMMENT 'Adres (servis sağlayıcı için)'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS working_hours TEXT NULL COMMENT 'Çalışma saatleri (servis sağlayıcı için)'"
    ];

    foreach ($updates as $sql) {
        try {
            $db->exec($sql);
            echo "✓ Başarılı: " . substr($sql, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "✗ Hata: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== Mevcut Servis Sağlayıcıları Users Tablosuna Aktarma ===\n";

    // service_providers tablosundaki verileri users tablosuna aktar
    $query = "
        UPDATE users u 
        JOIN service_providers sp ON u.id = sp.user_id 
        SET 
            u.company_name = sp.company_name,
            u.city = sp.city,
            u.services = sp.services,
            u.rating = sp.rating,
            u.provider_status = sp.status,
            u.description = sp.description,
            u.address = sp.address,
            u.working_hours = sp.working_hours,
            u.role_id = 2
        WHERE sp.user_id IS NOT NULL
    ";

    $stmt = $db->prepare($query);
    if ($stmt->execute()) {
        $affected = $stmt->rowCount();
        echo "✓ {$affected} servis sağlayıcı users tablosuna aktarıldı\n";
    }

    echo "\n=== Demo Kullanıcıyı Servis Sağlayıcı Olarak Ayarlama ===\n";

    // Demo kullanıcıyı servis sağlayıcı olarak ayarla
    $demo_update = "
        UPDATE users 
        SET 
            role_id = 2,
            company_name = 'Demo Servis',
            city = 'Lefkoşa',
            services = 'Servis ve Bakım',
            rating = 4.5,
            provider_status = 'active',
            description = 'Demo servis sağlayıcı hesabı',
            address = 'Lefkoşa, KKTC',
            working_hours = '08:00-18:00'
        WHERE phone = '+905551234567'
    ";

    $stmt = $db->prepare($demo_update);
    if ($stmt->execute()) {
        echo "✓ Demo kullanıcı servis sağlayıcı olarak ayarlandı\n";
    }

    echo "\n=== Güncelleme Tamamlandı ===\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

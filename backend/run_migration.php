<?php
require_once 'config/database.php';

echo "=== Servis Sağlayıcı Migrasyonu Başlatılıyor ===\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Veritabanı bağlantısı başarılı\n";

    // Users tablosuna yeni alanları ekle
    echo "Users tablosuna yeni alanlar ekleniyor...\n";

    $columns = [
        'company_name' => 'VARCHAR(255) DEFAULT NULL',
        'city' => 'VARCHAR(100) DEFAULT NULL',
        'services' => 'TEXT DEFAULT NULL',
        'rating' => 'DECIMAL(3,2) DEFAULT NULL',
        'provider_status' => 'VARCHAR(50) DEFAULT NULL',
        'description' => 'TEXT DEFAULT NULL',
        'address' => 'TEXT DEFAULT NULL',
        'working_hours' => 'TEXT DEFAULT NULL'
    ];

    foreach ($columns as $column => $definition) {
        try {
            $sql = "ALTER TABLE users ADD COLUMN $column $definition";
            $pdo->exec($sql);
            echo "✓ $column alanı eklendi\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "! $column alanı zaten mevcut\n";
            } else {
                echo "✗ $column alanı eklenirken hata: " . $e->getMessage() . "\n";
            }
        }
    }

    // Demo kullanıcıyı servis sağlayıcı olarak ayarla
    echo "\nDemo kullanıcı servis sağlayıcı olarak ayarlanıyor...\n";

    $demo_sql = "
        UPDATE users 
        SET 
            role_id = 2,
            company_name = 'Demo Servis',
            city = 'Lefkoşa',
            services = 'Servis ve Bakım',
            rating = 4.5,
            provider_status = 'active',
            description = 'Güvenilir araç servis hizmeti',
            address = 'Demo Mahallesi, Demo Sokak No:1',
            working_hours = 'Pazartesi-Cumartesi 08:00-18:00'
        WHERE phone = '+905551234567'
    ";

    $stmt = $pdo->prepare($demo_sql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "✓ Demo kullanıcı servis sağlayıcı olarak ayarlandı\n";
    } else {
        echo "! Demo kullanıcı bulunamadı\n";
    }

    echo "\n✅ Migrasyon başarıyla tamamlandı!\n";
} catch (Exception $e) {
    echo "❌ Migrasyon hatası: " . $e->getMessage() . "\n";
}

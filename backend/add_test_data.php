<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "Veritabanı bağlantı hatası\n";
        exit();
    }

    echo "Test verisi ekleniyor...\n";

    // Test kullanıcısı ekle (eğer yoksa)
    $check_user = $db->prepare("SELECT id FROM users WHERE phone = '+905551234567'");
    $check_user->execute();
    
    if ($check_user->rowCount() == 0) {
        $insert_user = $db->prepare("INSERT INTO users (phone, password, name, email, is_verified, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
        $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
        $insert_user->execute(['+905551234567', $hashed_password, 'Test Kullanıcı', 'test@test.com']);
        $user_id = $db->lastInsertId();
        echo "Test kullanıcısı eklendi: ID = $user_id\n";
    } else {
        $user = $check_user->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];
        echo "Test kullanıcısı zaten mevcut: ID = $user_id\n";
    }

    // Test araçları ekle
    $vehicles = [
        [
            'brand' => 'BMW',
            'model' => 'X5',
            'year' => '2020',
            'plate' => '34 ABC 123',
            'color' => 'Siyah',
            'last_service_date' => '2024-01-15',
            'last_inspection_date' => '2024-02-01',
            'insurance_expiry_date' => '2024-12-31',
            'kasko_expiry_date' => '2024-12-31',
            'registration_expiry_date' => '2024-11-30',
            'oil_change_date' => '2024-03-01',
            'tire_change_date' => '2024-04-01'
        ],
        [
            'brand' => 'Mercedes',
            'model' => 'C200',
            'year' => '2021',
            'plate' => '06 XYZ 789',
            'color' => 'Beyaz',
            'last_service_date' => '2024-02-20',
            'last_inspection_date' => '2024-03-15',
            'insurance_expiry_date' => '2024-10-31',
            'kasko_expiry_date' => '2024-10-31',
            'registration_expiry_date' => '2024-09-30',
            'oil_change_date' => '2024-04-15',
            'tire_change_date' => '2024-05-01'
        ]
    ];

    foreach ($vehicles as $vehicle) {
        $check_vehicle = $db->prepare("SELECT id FROM vehicles WHERE plate = ? AND user_id = ?");
        $check_vehicle->execute([$vehicle['plate'], $user_id]);
        
        if ($check_vehicle->rowCount() == 0) {
            $insert_vehicle = $db->prepare("
                INSERT INTO vehicles (user_id, brand, model, year, plate, color, 
                                    last_service_date, last_inspection_date, insurance_expiry_date,
                                    kasko_expiry_date, registration_expiry_date, oil_change_date, tire_change_date,
                                    created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $insert_vehicle->execute([
                $user_id,
                $vehicle['brand'],
                $vehicle['model'],
                $vehicle['year'],
                $vehicle['plate'],
                $vehicle['color'],
                $vehicle['last_service_date'],
                $vehicle['last_inspection_date'],
                $vehicle['insurance_expiry_date'],
                $vehicle['kasko_expiry_date'],
                $vehicle['registration_expiry_date'],
                $vehicle['oil_change_date'],
                $vehicle['tire_change_date']
            ]);
            
            $vehicle_id = $db->lastInsertId();
            echo "Test aracı eklendi: {$vehicle['brand']} {$vehicle['model']} - ID = $vehicle_id\n";
        } else {
            echo "Test aracı zaten mevcut: {$vehicle['brand']} {$vehicle['model']}\n";
        }
    }

    // Test hatırlatmaları ekle
    $reminders = [
        [
            'vehicle_id' => 1,
            'title' => 'Yağ değişimi',
            'date' => date('Y-m-d', strtotime('+7 days')),
            'description' => 'Motor yağı değişimi yapılması gerekiyor'
        ],
        [
            'vehicle_id' => 1,
            'title' => 'Fren kontrolü',
            'date' => date('Y-m-d', strtotime('+15 days')),
            'description' => 'Fren sistemi kontrol edilmeli'
        ],
        [
            'vehicle_id' => 2,
            'title' => 'Lastik değişimi',
            'date' => date('Y-m-d', strtotime('+30 days')),
            'description' => 'Lastikler değiştirilmeli'
        ]
    ];

    foreach ($reminders as $reminder) {
        $check_reminder = $db->prepare("SELECT id FROM reminders WHERE title = ? AND vehicle_id = ?");
        $check_reminder->execute([$reminder['title'], $reminder['vehicle_id']]);
        
        if ($check_reminder->rowCount() == 0) {
            $insert_reminder = $db->prepare("
                INSERT INTO reminders (vehicle_id, title, description, date, is_completed, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, NOW(), NOW())
            ");
            
            $insert_reminder->execute([
                $reminder['vehicle_id'],
                $reminder['title'],
                $reminder['description'],
                $reminder['date']
            ]);
            
            echo "Test hatırlatması eklendi: {$reminder['title']}\n";
        } else {
            echo "Test hatırlatması zaten mevcut: {$reminder['title']}\n";
        }
    }

    echo "\nTest verisi ekleme tamamlandı!\n";
    echo "Kullanıcı: +905551234567 / 123456\n";
    echo "Araçlar ve hatırlatmalar eklendi.\n";

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
<?php
include_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        die("Veritabanı bağlantısı kurulamadı\n");
    }

    echo "Duplicate hatırlatıcılar temizleniyor...\n";

    // Önce mevcut duplicate kayıtları temizle
    $cleanQuery = "DELETE r1 FROM reminders r1
                   INNER JOIN reminders r2 
                   WHERE r1.id > r2.id 
                   AND r1.vehicle_id = r2.vehicle_id 
                   AND r1.date = r2.date 
                   AND r1.reminder_time = r2.reminder_time";

    $result = $db->exec($cleanQuery);
    echo "Silinen duplicate kayıt sayısı: $result\n";

    // Unique constraint var mı kontrol et
    $checkQuery = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                   WHERE TABLE_SCHEMA = 'otoasist' 
                   AND TABLE_NAME = 'reminders' 
                   AND CONSTRAINT_NAME = 'unique_vehicle_date_time'";

    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    $constraintExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if (!$constraintExists) {
        echo "Unique constraint ekleniyor...\n";
        $constraintQuery = "ALTER TABLE reminders 
                           ADD CONSTRAINT unique_vehicle_date_time 
                           UNIQUE (vehicle_id, date, reminder_time)";
        $db->exec($constraintQuery);
        echo "Unique constraint başarıyla eklendi\n";
    } else {
        echo "Unique constraint zaten mevcut\n";
    }

    echo "İşlem tamamlandı!\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

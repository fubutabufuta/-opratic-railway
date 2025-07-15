<?php
require_once 'config/database.php';

// Test plakası
$testPlate = "rr 777";
$testVehicleType = "D";

echo "=== Muayene Tarihi Hesaplama Test ===\n";
echo "Test Plaka: $testPlate\n";
echo "Araç Tipi: $testVehicleType\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Plaka kodunu çıkar (aynı logic backend'deki gibi)
    $plateCode = '';
    if (strlen($testPlate) >= 2) {
        $plateCode = strtoupper(trim(substr($testPlate, 0, 2)));
    } else {
        $plateCode = strtoupper(trim($testPlate));
    }
    
    // FIND_IN_SET için boşluk eklemeye gerek yok

    echo "1. Çıkarılan plaka kodu: '$plateCode'\n";
    echo "2. Plaka kodu uzunluğu: " . strlen($plateCode) . "\n\n";

    // Mevcut yılı al (integer olarak)
    $currentYear = (int)date('Y');
    echo "3. Arama yılı: $currentYear\n\n";

    // Önce tablodan RR içeren kayıtları göster
    echo "4. RR içeren tüm kayıtlar:\n";
    $allRRQuery = "SELECT id, year, vehicle_type, plate_code, inspection_date, inspection_end_date 
                   FROM inspection_schedule 
                   WHERE plate_code LIKE '%RR%'";
    $allRRStmt = $db->prepare($allRRQuery);
    $allRRStmt->execute();
    
    while ($row = $allRRStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID: {$row['id']}, Year: {$row['year']}, Type: {$row['vehicle_type']}, Codes: {$row['plate_code']}, Date: {$row['inspection_date']} - {$row['inspection_end_date']}\n";
    }

    // FIND_IN_SET test
    echo "\n5. FIND_IN_SET test:\n";
    $findQuery = "SELECT id, year, vehicle_type, plate_code, inspection_date, inspection_end_date,
                         FIND_IN_SET('RR', plate_code) as find_result
                  FROM inspection_schedule 
                  WHERE year = :year 
                  AND vehicle_type = :vehicle_type";
    
    $findStmt = $db->prepare($findQuery);
    $findStmt->bindParam(':year', $currentYear);
    $findStmt->bindParam(':vehicle_type', $testVehicleType);
    $findStmt->execute();
    
    echo "  Arama kriterleri: year=$currentYear, vehicle_type=$testVehicleType\n";
    while ($row = $findStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID: {$row['id']}, FIND_IN_SET result: {$row['find_result']}, Codes: {$row['plate_code']}\n";
    }

    // Gerçek arama
    echo "\n6. Gerçek arama sorgusu:\n";
    $query = "SELECT inspection_date, inspection_end_date 
              FROM inspection_schedule 
              WHERE year = :year 
              AND vehicle_type = :vehicle_type 
              AND FIND_IN_SET(:plate_code, plate_code) > 0
              LIMIT 1";

    echo "  SQL: $query\n";
    echo "  Parametreler: year=$currentYear, vehicle_type=$testVehicleType, plate_code='$plateCode'\n";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':year', $currentYear);
    $stmt->bindParam(':vehicle_type', $testVehicleType);
    $stmt->bindParam(':plate_code', $plateCode);
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\n✅ BAŞARILI! Muayene tarihleri bulundu:\n";
        echo "  Başlangıç: {$row['inspection_date']}\n";
        echo "  Bitiş: {$row['inspection_end_date']}\n";
    } else {
        echo "\n❌ BAŞARISIZ! Muayene tarihi bulunamadı.\n";
        
        // Hata ayıklama için farklı aramalar dene
        echo "\n7. Hata ayıklama aramaları:\n";
        
        // Sadece yıl ve tip ile arama
        $debugQuery1 = "SELECT * FROM inspection_schedule WHERE year = :year AND vehicle_type = :vehicle_type";
        $debugStmt1 = $db->prepare($debugQuery1);
        $debugStmt1->bindParam(':year', $currentYear);
        $debugStmt1->bindParam(':vehicle_type', $testVehicleType);
        $debugStmt1->execute();
        echo "  Year + Type ile bulunan kayıt sayısı: " . $debugStmt1->rowCount() . "\n";
        
        // LIKE ile arama
        $debugQuery2 = "SELECT * FROM inspection_schedule WHERE year = :year AND vehicle_type = :vehicle_type AND plate_code LIKE :plate_like";
        $plateLike = "%$plateCode%";
        $debugStmt2 = $db->prepare($debugQuery2);
        $debugStmt2->bindParam(':year', $currentYear);
        $debugStmt2->bindParam(':vehicle_type', $testVehicleType);
        $debugStmt2->bindParam(':plate_like', $plateLike);
        $debugStmt2->execute();
        echo "  LIKE ile bulunan kayıt sayısı: " . $debugStmt2->rowCount() . "\n";
    }

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
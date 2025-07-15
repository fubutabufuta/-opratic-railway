<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Inspection Schedule Table Check ===\n";

    // Tablo var mı kontrol et
    $tableCheck = $db->query("SHOW TABLES LIKE 'inspection_schedule'");
    if ($tableCheck->rowCount() == 0) {
        echo "ERROR: inspection_schedule tablosu bulunamadı!\n";
        echo "Tablo oluşturmak için: php backend/create_inspection_table.php\n";
        exit;
    }

    // Tablo yapısını göster
    echo "\n=== Table Structure ===\n";
    $result = $db->query("DESCRIBE inspection_schedule");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    // Toplam kayıt sayısı
    $countResult = $db->query("SELECT COUNT(*) as total FROM inspection_schedule");
    $totalRows = $countResult->fetch(PDO::FETCH_ASSOC)['total'];
    echo "\nToplam kayıt sayısı: $totalRows\n";

    if ($totalRows == 0) {
        echo "UYARI: Tablo boş! Örnek veri eklemek için:\n";
        echo "mysql -u root -p otoasist_db < backend/create_inspection_schedule_table.sql\n";
        exit;
    }

    // RR plaka kodu için kontrol
    echo "\n=== RR Plaka Kodu Kontrolü ===\n";
    $query = "SELECT * FROM inspection_schedule WHERE FIND_IN_SET('RR', plate_code) > 0 AND vehicle_type = 'D' LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "RR plaka kodu için kayıtlar bulundu:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Year: " . $row['year'] . ", Type: " . $row['vehicle_type'] . ", Codes: " . $row['plate_code'] . ", Inspection: " . $row['inspection_date'] . " - " . $row['inspection_end_date'] . "\n";
        }
    } else {
        echo "RR plaka kodu için kayıt bulunamadı.\n";
        
        // Hangi plaka kodları var göster
        echo "\nMevcut plaka kodları (ilk 10 kayıt):\n";
        $allCodes = $db->query("SELECT vehicle_type, plate_code, inspection_date FROM inspection_schedule LIMIT 10");
        while ($row = $allCodes->fetch(PDO::FETCH_ASSOC)) {
            echo "Type: " . $row['vehicle_type'] . ", Codes: " . $row['plate_code'] . ", Date: " . $row['inspection_date'] . "\n";
        }
    }

    // 34 plaka kodu için kontrol (test için)
    echo "\n=== 34 Plaka Kodu Kontrolü (Test) ===\n";
    $query34 = "SELECT * FROM inspection_schedule WHERE FIND_IN_SET('34', plate_code) > 0 AND vehicle_type = 'D' LIMIT 3";
    $stmt34 = $db->prepare($query34);
    $stmt34->execute();
    
    if ($stmt34->rowCount() > 0) {
        echo "34 plaka kodu için kayıtlar:\n";
        while ($row = $stmt34->fetch(PDO::FETCH_ASSOC)) {
            echo "Year: " . $row['year'] . ", Type: " . $row['vehicle_type'] . ", Codes: " . $row['plate_code'] . ", Inspection: " . $row['inspection_date'] . " - " . $row['inspection_end_date'] . "\n";
        }
    } else {
        echo "34 plaka kodu için de kayıt bulunamadı.\n";
    }

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
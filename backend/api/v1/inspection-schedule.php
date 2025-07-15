<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Input verilerini al
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!$input) {
            echo json_encode([
                "success" => false,
                "message" => "Geçersiz JSON verisi"
            ]);
            exit;
        }

        $plateCode = $input['plate_code'] ?? null;
        $vehicleType = $input['vehicle_type'] ?? null;
        $year = $input['year'] ?? date('Y');

        if (!$plateCode || !$vehicleType) {
            echo json_encode([
                "success" => false,
                "message" => "Plaka kodu ve araç tipi gerekli"
            ]);
            exit;
        }

        // Plaka kodunu büyük harfe çevir ve temizle
        $plateCode = strtoupper(trim($plateCode));
        
        // Plaka kodu 2 karakterse boşluk ekle (AB -> "AB ")
        if (strlen($plateCode) == 2) {
            $plateCode = $plateCode . ' ';
        }

        // inspection_schedule tablosunda arama yap
        $query = "SELECT inspection_date, inspection_end_date 
                  FROM inspection_schedule 
                  WHERE year = :year 
                  AND vehicle_type = :vehicle_type 
                  AND FIND_IN_SET(:plate_code, plate_code) > 0
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':vehicle_type', $vehicleType);
        $stmt->bindParam(':plate_code', $plateCode);
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "message" => "Muayene tarihleri bulundu",
                "data" => [
                    "inspection_date" => $row['inspection_date'],
                    "inspection_end_date" => $row['inspection_end_date'],
                    "plate_code" => $plateCode,
                    "vehicle_type" => $vehicleType,
                    "year" => $year
                ]
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Bu plaka kodu ve araç tipi için muayene tarihi bulunamadı",
                "data" => null,
                "search_params" => [
                    "plate_code" => $plateCode,
                    "vehicle_type" => $vehicleType,
                    "year" => $year
                ]
            ]);
        }

    } else {
        echo json_encode([
            "success" => false,
            "message" => "Sadece POST metodu desteklenir"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Veritabanı hatası: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Genel hata: " . $e->getMessage()
    ]);
}
?> 
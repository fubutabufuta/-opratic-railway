<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // SQL dosyasını oku
    $sql = file_get_contents('create_inspection_schedule_table.sql');
    
    if (!$sql) {
        throw new Exception("SQL dosyası okunamadı");
    }

    // SQL komutlarını ayır ve çalıştır
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Muayene programı tablosu başarıyla oluşturuldu ve veriler eklendi"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Hata: " . $e->getMessage()
    ]);
}
?> 
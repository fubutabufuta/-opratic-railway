<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Veritabanı bağlantısını test et
try {
    $host = "127.0.0.1";
    $db_name = "otoasist";
    $username = "root";
    $password = "";

    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Basit bir sorgu çalıştır
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "message" => "Veritabanı bağlantısı başarılı",
        "user_count" => $result['user_count'],
        "server_info" => $pdo->getAttribute(PDO::ATTR_SERVER_INFO)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Veritabanı bağlantı hatası: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Genel hata: " . $e->getMessage()
    ]);
}

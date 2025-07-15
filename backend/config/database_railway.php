<?php
// Railway.app Database Configuration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Railway environment variables
$host = $_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? 'localhost';
$port = $_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? '3306';
$database = $_ENV['MYSQL_DATABASE'] ?? $_SERVER['MYSQL_DATABASE'] ?? 'railway';
$username = $_ENV['MYSQL_USER'] ?? $_SERVER['MYSQL_USER'] ?? 'root';
$password = $_ENV['MYSQL_PASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? '';

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
    
    // Test connection
    $pdo->query("SELECT 1");
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'connection_info' => [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username
        ]
    ]);
    exit;
}

// Return PDO instance
return $pdo;
?> 
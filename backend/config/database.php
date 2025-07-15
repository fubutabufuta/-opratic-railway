<?php
// OtoAsist Database Configuration - Railway Production
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Railway environment variables (otomatik set edilir)
$host = $_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? 'localhost';
$port = $_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? '3306';
$database = $_ENV['MYSQL_DATABASE'] ?? $_SERVER['MYSQL_DATABASE'] ?? 'railway';
$username = $_ENV['MYSQL_USER'] ?? $_SERVER['MYSQL_USER'] ?? 'root';
$password = $_ENV['MYSQL_PASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? '';

// Fallback to DATABASE_URL if individual vars not available
if (!isset($_ENV['MYSQL_HOST']) && isset($_ENV['DATABASE_URL'])) {
    $url = parse_url($_ENV['DATABASE_URL']);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? '3306';
    $database = ltrim($url['path'], '/') ?? 'railway';
    $username = $url['user'] ?? 'root';
    $password = $url['pass'] ?? '';
}

// Database connection with Railway optimizations
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
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_PERSISTENT => false,
        ]
    );
    
    // Test connection
    $pdo->query("SELECT 1");
    
} catch (PDOException $e) {
    // Development debugging
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        error_log("Database connection failed: " . $e->getMessage());
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'details' => ($_ENV['APP_ENV'] ?? 'production') === 'development' ? $e->getMessage() : 'Check server logs',
        'connection_info' => [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'has_password' => !empty($password)
        ]
    ]);
    exit;
}

// Return PDO instance
return $pdo;
?>

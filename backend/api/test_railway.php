<?php
// Railway Database Test
require_once '../config/database_railway.php';

try {
    // Test database connection
    $pdo = require '../config/database_railway.php';
    
    // Get all tables
    $tablesQuery = $pdo->query("SHOW TABLES");
    $tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);
    
    // Count records in main tables
    $counts = [];
    $mainTables = ['users', 'vehicles', 'reminders', 'campaigns', 'news', 'quotes'];
    
    foreach ($mainTables as $table) {
        if (in_array($table, $tables)) {
            $countQuery = $pdo->query("SELECT COUNT(*) FROM $table");
            $counts[$table] = $countQuery->fetchColumn();
        }
    }
    
    // Test a simple query
    $testQuery = $pdo->query("SELECT VERSION() as mysql_version");
    $version = $testQuery->fetch();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Railway database connection successful!',
        'connection_info' => [
            'mysql_version' => $version['mysql_version'],
            'total_tables' => count($tables),
            'tables' => $tables,
            'record_counts' => $counts
        ],
        'environment' => [
            'host' => $_ENV['MYSQL_HOST'] ?? 'not set',
            'port' => $_ENV['MYSQL_PORT'] ?? 'not set',
            'database' => $_ENV['MYSQL_DATABASE'] ?? 'not set',
            'username' => $_ENV['MYSQL_USER'] ?? 'not set'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database test failed: ' . $e->getMessage(),
        'environment_check' => [
            'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? 'not set',
            'MYSQL_PORT' => $_ENV['MYSQL_PORT'] ?? 'not set',
            'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'] ?? 'not set',
            'MYSQL_USER' => $_ENV['MYSQL_USER'] ?? 'not set',
            'MYSQL_PASSWORD' => isset($_ENV['MYSQL_PASSWORD']) ? '***set***' : 'not set'
        ]
    ]);
}
?> 
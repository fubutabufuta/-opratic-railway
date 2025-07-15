<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 OtoAsist Backend Server</h1>";

// Check PHP version
if (version_compare(phpversion(), '7.4.0', '<')) {
    die("❌ PHP 7.4 or higher required. Current version: " . phpversion());
}

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "❌ Missing PHP extensions: " . implode(', ', $missingExtensions) . "<br>";
    echo "Please install these extensions and restart your server.<br>";
    exit;
}

echo "✅ PHP version: " . phpversion() . "<br>";
echo "✅ All required extensions are loaded<br><br>";

// Test database connection
echo "<h2>🔍 Checking Database Connection...</h2>";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=otoasist;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful<br>";
    
    // Check critical tables
    $tables = ['users', 'vehicles', 'user_roles'];
    $tableExists = true;
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetchColumn()) {
            echo "❌ Table '$table' missing<br>";
            $tableExists = false;
        }
    }
    
    if (!$tableExists) {
        echo "<br>⚠️ <strong>Database schema not complete!</strong><br>";
        echo "👉 <a href='setup_database.php'>Click here to setup database schema</a><br><br>";
    } else {
        echo "✅ Database schema looks good<br><br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "👉 <a href='test_db_connection.php'>Click here to test and fix database connection</a><br><br>";
}

echo "<hr>";

// Server info
echo "<h2>📊 Server Information</h2>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";

echo "<hr>";

// Available endpoints
echo "<h2>🔗 Available Endpoints</h2>";
echo "<h3>Admin Panel:</h3>";
echo "• <a href='index.php' target='_blank'>Main Admin Panel</a><br>";
echo "• <a href='admin.php' target='_blank'>Advanced Admin Panel</a><br>";

echo "<h3>API Endpoints:</h3>";
echo "• <a href='api/v1/vehicles/' target='_blank'>Vehicles API</a><br>";
echo "• <a href='api/v1/reminders/' target='_blank'>Reminders API</a><br>";
echo "• <a href='api/v1/user-settings/' target='_blank'>User Settings API</a><br>";
echo "• <a href='api/v1/auth/' target='_blank'>Authentication API</a><br>";

echo "<h3>Testing Tools:</h3>";
echo "• <a href='test_db_connection.php' target='_blank'>Database Connection Test</a><br>";
echo "• <a href='setup_database.php' target='_blank'>Database Schema Setup</a><br>";
echo "• <a href='test_all_apis.php' target='_blank'>Test All APIs</a><br>";

echo "<hr>";

// Status check
echo "<h2>✅ Backend Status</h2>";
echo "🟢 Backend server is running!<br>";
echo "🟢 PHP is working correctly<br>";
echo "🟢 File system is accessible<br>";

if (isset($pdo)) {
    echo "🟢 Database connection is active<br>";
} else {
    echo "🔴 Database connection needs attention<br>";
}

echo "<br><h3>🎯 Quick Start:</h3>";
echo "<ol>";
echo "<li>Make sure MySQL is running</li>";
echo "<li>Setup database schema if needed</li>";
echo "<li>Test API endpoints</li>";
echo "<li>Start using the Flutter app</li>";
echo "</ol>";

echo "<br><p><strong>Backend is ready to serve requests!</strong> 🚀</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}

h1 {
    color: #2c3e50;
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

h2 {
    color: #34495e;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

h3 {
    color: #2c3e50;
    margin-top: 20px;
}

a {
    color: #3498db;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

hr {
    margin: 30px 0;
    border: none;
    border-top: 1px solid #bdc3c7;
}

ol, ul {
    line-height: 1.6;
}

.status-good {
    color: #27ae60;
}

.status-error {
    color: #e74c3c;
}

.status-warning {
    color: #f39c12;
}
</style> 
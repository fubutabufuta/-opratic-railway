<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>OtoAsist Database Connection Test</h1>";

// Test MySQL connection
echo "<h2>1. MySQL Connection Test</h2>";
try {
    $host = "127.0.0.1";
    $username = "root";
    $password = "";
    $port = 3306;
    
    echo "Testing MySQL connection to $host:$port...<br>";
    
    // Test basic connection first
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ MySQL server connection successful!<br>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'otoasist'");
    $db_exists = $stmt->fetchColumn();
    
    if ($db_exists) {
        echo "✅ Database 'otoasist' exists<br>";
        
        // Connect to specific database
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=otoasist;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Connected to 'otoasist' database successfully!<br>";
        
        // Check tables
        echo "<h3>Tables in database:</h3>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "⚠️ No tables found in database<br>";
        } else {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "❌ Database 'otoasist' does not exist<br>";
        echo "Creating database...<br>";
        
        $pdo->exec("CREATE DATABASE otoasist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database 'otoasist' created successfully!<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ MySQL Connection Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    
    // Common solutions
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li>Make sure MySQL/MariaDB is running</li>";
    echo "<li>Check if port 3306 is not blocked</li>";
    echo "<li>Verify MySQL root password (currently empty)</li>";
    echo "<li>For XAMPP: Start MySQL from XAMPP Control Panel</li>";
    echo "<li>For WAMP: Start MySQL from WAMP interface</li>";
    echo "<li>For Laragon: Start MySQL from Laragon</li>";
    echo "</ul>";
}

echo "<br><hr><br>";

// Test API config
echo "<h2>2. API Config Test</h2>";
try {
    if (isset($pdo) && $pdo) {
        echo "✅ API Database connection successful!<br>";
    } else {
        echo "❌ API Database connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ API Database Error: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";

// Test main config
echo "<h2>3. Main Config Test</h2>";
try {
    if (isset($pdo) && $pdo) {
        echo "✅ Main Database connection successful!<br>";
    } else {
        echo "❌ Main Database connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Main Database Error: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";

// Environment info
echo "<h2>4. Environment Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? '✅ Yes' : '❌ No') . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Script Path: " . __FILE__ . "<br>";

// Check if we can create database schema
echo "<br><hr><br>";
echo "<h2>5. Database Schema Check</h2>";
if (file_exists('database_schema_update.sql')) {
    echo "✅ Database schema file found<br>";
    
    if (isset($pdo) && $pdo) {
        echo "<a href='setup_database.php' target='_blank'>🔧 Click here to setup database schema</a><br>";
    }
} else {
    echo "❌ Database schema file not found<br>";
}

echo "<br><hr><br>";

echo "<h2>6. Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='setup_database.php'>Setup Database Schema</a></li>";
echo "<li><a href='test_db_direct.php'>Direct Database Test</a></li>";
echo "<li><a href='api/test_db.php'>API Database Test</a></li>";
echo "<li><a href='index.php'>Admin Panel</a></li>";
echo "</ul>";

echo "<br><p><strong>If you see connection errors, please:</strong></p>";
echo "<ol>";
echo "<li>Start your MySQL server (XAMPP/WAMP/Laragon)</li>";
echo "<li>Make sure port 3306 is not blocked</li>";
echo "<li>Check MySQL root password settings</li>";
echo "<li>Try running this test again</li>";
echo "</ol>";
?> 
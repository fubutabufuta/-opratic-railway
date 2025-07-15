<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>OtoAsist Database Schema Setup</h1>";

// Database connection parameters
$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "otoasist";

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL server<br>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '$database' created or already exists<br>";
    
    // Connect to specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to '$database' database<br><br>";
    
    // Read and execute schema file
    $schemaFile = 'database_schema_update.sql';
    if (file_exists($schemaFile)) {
        echo "<h2>Executing Database Schema...</h2>";
        
        $sql = file_get_contents($schemaFile);
        
        // Split SQL by semicolon (simple approach)
        $statements = explode(';', $sql);
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            // Skip DELIMITER statements (not supported in PDO)
            if (strpos($statement, 'DELIMITER') !== false) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $successCount++;
                echo "✅ Executed: " . substr($statement, 0, 50) . "...<br>";
            } catch (PDOException $e) {
                $errorCount++;
                echo "❌ Error: " . $e->getMessage() . "<br>";
                echo "Statement: " . substr($statement, 0, 100) . "...<br><br>";
            }
        }
        
        echo "<br><h3>Summary:</h3>";
        echo "✅ Successful statements: $successCount<br>";
        echo "❌ Errors: $errorCount<br>";
        
    } else {
        echo "❌ Schema file not found: $schemaFile<br>";
    }
    
    // Test some key tables
    echo "<br><h2>Verifying Tables...</h2>";
    
    $tables = ['users', 'vehicles', 'reminders', 'user_roles', 'membership_packages'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetchColumn()) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' missing<br>";
            }
        } catch (PDOException $e) {
            echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    // Add test data
    echo "<br><h2>Adding Test Data...</h2>";
    
    try {
        // Check if test user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute(['+905321123456']);
        
        if (!$stmt->fetch()) {
            // Add test user
            $stmt = $pdo->prepare("INSERT INTO users (phone, email, full_name, password_hash, is_verified, role_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                '+905321123456',
                'test@example.com',
                'Test User',
                password_hash('123456', PASSWORD_DEFAULT),
                1,
                1
            ]);
            echo "✅ Test user created<br>";
        } else {
            echo "✅ Test user already exists<br>";
        }
        
        // Check if test vehicle exists
        $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE plate = ?");
        $stmt->execute(['34TEST123']);
        
        if (!$stmt->fetch()) {
            // Add test vehicle
            $stmt = $pdo->prepare("INSERT INTO vehicles (user_id, brand, model, year, plate, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([1, 'Toyota', 'Corolla', 2020, '34TEST123', 1]);
            echo "✅ Test vehicle created<br>";
        } else {
            echo "✅ Test vehicle already exists<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Error adding test data: " . $e->getMessage() . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Connection Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}

echo "<br><hr><br>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li><a href='test_db_connection.php'>Test Database Connection</a></li>";
echo "<li><a href='api/test_db.php'>Test API Database</a></li>";
echo "<li><a href='index.php'>Open Admin Panel</a></li>";
echo "<li><a href='test_all_apis.php'>Test All APIs</a></li>";
echo "</ol>";

echo "<br><p><strong>Backend Ready!</strong> You can now start using the OtoAsist backend.</p>";
?> 
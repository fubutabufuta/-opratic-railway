<?php
echo "OtoAsist Backend Server Test\n";
echo "Server is running on: " . $_SERVER['HTTP_HOST'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PHP Version: " . phpversion() . "\n";

// Test database connection
try {
    include_once 'api/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "Database connection: SUCCESS\n";
    } else {
        echo "Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 
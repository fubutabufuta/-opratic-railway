<?php
include_once 'config/database.php';

echo "Testing database connection...\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "ERROR: Database connection failed\n";
        exit(1);
    }

    echo "SUCCESS: Database connected\n";

    // Test users table
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();

    echo "Users in database: " . $result['count'] . "\n";

    // Check specific user
    $query = "SELECT id, phone, full_name, is_verified FROM users WHERE phone = '+905551234567'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        echo "User found:\n";
        echo "  ID: " . $user['id'] . "\n";
        echo "  Phone: " . $user['phone'] . "\n";
        echo "  Name: " . $user['full_name'] . "\n";
        echo "  Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "\n";

        // Check password
        $query = "SELECT password FROM users WHERE phone = '+905551234567'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $user = $stmt->fetch();

        echo "  Password hash: " . substr($user['password'], 0, 20) . "...\n";
        echo "  Password check (123): " . (password_verify('123', $user['password']) ? 'MATCH' : 'NO MATCH') . "\n";
    } else {
        echo "User NOT found with phone +905551234567\n";

        // Show all users
        $query = "SELECT phone, full_name FROM users LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();

        echo "Available users:\n";
        while ($user = $stmt->fetch()) {
            echo "  - " . $user['phone'] . " (" . $user['full_name'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

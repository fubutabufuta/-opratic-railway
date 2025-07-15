<?php
include_once 'config/database.php';

echo "Updating user password...\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "ERROR: Database connection failed\n";
        exit(1);
    }

    // Hash the password
    $password = '123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    echo "New password hash: " . $hashedPassword . "\n";

    // Update user password
    $query = "UPDATE users SET password = :password WHERE phone = '+905551234567'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashedPassword);

    if ($stmt->execute()) {
        echo "SUCCESS: Password updated for user +905551234567\n";

        // Verify the update
        $query = "SELECT password FROM users WHERE phone = '+905551234567'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $user = $stmt->fetch();

        echo "Verification: " . (password_verify('123', $user['password']) ? 'MATCH' : 'NO MATCH') . "\n";
    } else {
        echo "ERROR: Failed to update password\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

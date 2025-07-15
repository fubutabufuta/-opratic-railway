<?php
include_once 'api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "Veritabanı bağlantı hatası\n";
        exit();
    }

    // Test kullanıcısını güncelle
    $phone = '+905551234567';
    $password = password_hash('123456', PASSWORD_DEFAULT);

    $query = "UPDATE users SET password = :password WHERE phone = :phone";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":password", $password);
    $stmt->bindParam(":phone", $phone);

    if ($stmt->execute()) {
        echo "Test kullanıcısı güncellendi\n";
        echo "Phone: $phone\n";
        echo "Password: 123456\n";
        echo "Hash: $password\n";

        // Verify test
        echo "Verify test: " . (password_verify('123456', $password) ? 'SUCCESS' : 'FAILED') . "\n";
    } else {
        echo "Kullanıcı güncellenemedi\n";
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

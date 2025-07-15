<?php
include_once 'api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "Veritabanı bağlantı hatası\n";
        exit();
    }

    // Test kullanıcıları ve şifreleri
    $users = [
        ['+905551234567', '123456'],
        ['+905559876543', '123456'],
        ['+905554567890', '123456'],
        ['+90333333333', '333333']
    ];

    foreach ($users as $user) {
        $phone = $user[0];
        $plainPassword = $user[1];
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        // Kullanıcıyı güncelle
        $query = "UPDATE users SET password = :password WHERE phone = :phone";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":phone", $phone);

        if ($stmt->execute() && $stmt->rowCount() > 0) {
            echo "✓ $phone güncellendi (şifre: $plainPassword)\n";
            echo "  Hash: $hashedPassword\n";
            echo "  Verify: " . (password_verify($plainPassword, $hashedPassword) ? 'OK' : 'FAIL') . "\n\n";
        } else {
            echo "✗ $phone güncellenemedi\n\n";
        }
    }

    echo "Tüm kullanıcılar işlendi.\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

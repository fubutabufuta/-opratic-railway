<?php
include_once 'api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "Veritabanı bağlantı hatası\n";
        exit();
    }

    $query = "SELECT id, phone, password FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo "Kullanıcı şifreleri:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . ", Phone: " . $row['phone'] . "\n";
        echo "Password Hash: " . $row['password'] . "\n";
        echo "123456 verify: " . (password_verify('123456', $row['password']) ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

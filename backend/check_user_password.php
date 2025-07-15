<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test kullanıcısını bul
    $phone = '5551234567';
    $query = "SELECT id, phone, password, name, email FROM users WHERE phone = :phone";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":phone", $phone);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Kullanıcı bulundu:\n";
        echo "ID: {$user['id']}\n";
        echo "Phone: {$user['phone']}\n";
        echo "Name: {$user['name']}\n";
        echo "Email: {$user['email']}\n";
        echo "Password hash: {$user['password']}\n";
        
        // Şifre kontrolü
        $test_password = '123456';
        if (password_verify($test_password, $user['password'])) {
            echo "✓ Şifre doğru!\n";
        } else {
            echo "✗ Şifre yanlış!\n";
            
            // Yeni şifre oluştur
            $new_password = password_hash('123456', PASSWORD_DEFAULT);
            echo "Yeni şifre hash: $new_password\n";
            
            // Şifreyi güncelle
            $update_query = "UPDATE users SET password = :password WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":password", $new_password);
            $update_stmt->bindParam(":id", $user['id']);
            $update_stmt->execute();
            
            echo "Şifre güncellendi!\n";
        }
    } else {
        echo "Kullanıcı bulunamadı!\n";
        
        // Test kullanıcısı oluştur
        $name = 'Test User';
        $email = 'test@example.com';
        $password = password_hash('123456', PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (name, email, phone, password, user_type) VALUES (:name, :email, :phone, :password, 'user')";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(":name", $name);
        $insert_stmt->bindParam(":email", $email);
        $insert_stmt->bindParam(":phone", $phone);
        $insert_stmt->bindParam(":password", $password);
        $insert_stmt->execute();
        
        echo "Test kullanıcısı oluşturuldu!\n";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
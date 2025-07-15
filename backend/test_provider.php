<?php
include_once __DIR__ . "/config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Servis Sağlayıcı Kullanıcıları ===\n";

    // Demo provider kullanıcısını kontrol et
    $demo_phone = "+905551234567";
    $query = "SELECT u.*, sp.id as provider_id, sp.company_name 
              FROM users u 
              LEFT JOIN service_providers sp ON u.id = sp.user_id 
              WHERE u.phone = :phone";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":phone", $demo_phone);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        echo "Demo kullanıcı bulunamadı!\n";
    } else {
        $demo_user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Demo kullanıcı bulundu:\n";
        echo "ID: {$demo_user["id"]}\n";
        echo "Phone: {$demo_user["phone"]}\n";
        echo "Name: {$demo_user["full_name"]}\n";
        echo "Role ID: {$demo_user["role_id"]}\n";
        echo "Provider ID: {$demo_user["provider_id"]}\n";
        echo "Company: {$demo_user["company_name"]}\n";

        // Şifreyi test et
        if (password_verify("123456", $demo_user["password"])) {
            echo "Şifre doğru!\n";
        } else {
            echo "Şifre yanlış!\n";
        }

        // Eğer role_id 2 değilse güncelle
        if ($demo_user["role_id"] != 2) {
            echo "Role ID güncelleniyor...\n";
            $update_query = "UPDATE users SET role_id = 2 WHERE phone = :phone";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":phone", $demo_phone);

            if ($update_stmt->execute()) {
                echo "Role ID başarıyla güncellendi!\n";
            }
        }

        // Eğer service_provider kaydı yoksa oluştur
        if (empty($demo_user["provider_id"])) {
            echo "Service provider kaydı oluşturuluyor...\n";
            $insert_query = "INSERT INTO service_providers (user_id, company_name, city, services, phone, email, status) 
                             VALUES (:user_id, :company_name, :city, :services, :phone, :email, \"active\")";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(":user_id", $demo_user["id"]);
            $insert_stmt->bindValue(":company_name", "Demo Servis");
            $insert_stmt->bindValue(":city", "Lefkoşa");
            $insert_stmt->bindValue(":services", "Servis ve Bakım");
            $insert_stmt->bindParam(":phone", $demo_user["phone"]);
            $insert_stmt->bindParam(":email", $demo_user["email"]);

            if ($insert_stmt->execute()) {
                echo "Service provider kaydı başarıyla oluşturuldu!\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

<?php
echo "🔧 DATABASE ŞEMASI GÜNCELLENİYOR...\n";
echo "==================================\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database bağlantısı başarılı\n";

    // 1. Users tablosuna ehliyet fotoğrafı alanı ekle
    echo "\n👤 Users tablosu güncelleniyor...\n";
    try {
        $db->exec("ALTER TABLE users ADD COLUMN driving_license_photo VARCHAR(500) NULL");
        echo "✅ driving_license_photo alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ driving_license_photo alanı zaten mevcut\n";
        } else {
            echo "❌ driving_license_photo hatası: " . $e->getMessage() . "\n";
        }
    }

    try {
        $db->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(500) NULL");
        echo "✅ profile_photo alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ profile_photo alanı zaten mevcut\n";
        } else {
            echo "❌ profile_photo hatası: " . $e->getMessage() . "\n";
        }
    }

    // 2. Quote requests tablosunu genişlet
    echo "\n💼 Quote requests tablosu güncelleniyor...\n";
    try {
        $db->exec("ALTER TABLE quote_requests ADD COLUMN vehicle_details JSON NULL");
        echo "✅ vehicle_details alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ vehicle_details alanı zaten mevcut\n";
        } else {
            echo "❌ vehicle_details hatası: " . $e->getMessage() . "\n";
        }
    }

    try {
        $db->exec("ALTER TABLE quote_requests ADD COLUMN attachments JSON NULL");
        echo "✅ attachments alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ attachments alanı zaten mevcut\n";
        } else {
            echo "❌ attachments hatası: " . $e->getMessage() . "\n";
        }
    }

    try {
        $db->exec("ALTER TABLE quote_requests ADD COLUMN customer_notes TEXT NULL");
        echo "✅ customer_notes alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ customer_notes alanı zaten mevcut\n";
        } else {
            echo "❌ customer_notes hatası: " . $e->getMessage() . "\n";
        }
    }

    try {
        $db->exec("ALTER TABLE quote_requests ADD COLUMN city VARCHAR(100) DEFAULT 'Lefkoşa'");
        echo "✅ city alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ city alanı zaten mevcut\n";
        } else {
            echo "❌ city hatası: " . $e->getMessage() . "\n";
        }
    }

    // 3. File uploads tablosu oluştur
    echo "\n📁 File uploads tablosu oluşturuluyor...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS file_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_type ENUM('profile_photo', 'driving_license', 'vehicle_photo', 'quote_attachment') NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT DEFAULT 0,
        mime_type VARCHAR(100),
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active TINYINT(1) DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ file_uploads tablosu oluşturuldu\n";

    // 4. Demo user'a ehliyet fotoğrafı ekle
    echo "\n👨‍💼 Demo user güncelleniyor...\n";
    $stmt = $db->prepare("UPDATE users SET driving_license_photo = ? WHERE id = 1");
    $stmt->execute(['/uploads/licenses/demo_license.jpg']);
    echo "✅ Demo user'a ehliyet fotoğrafı eklendi\n";

    echo "\n🎯 DATABASE ŞEMASI BAŞARIYLA GÜNCELLENDİ!\n";
    echo "===========================================\n";
    echo "✅ Ehliyet fotoğrafı sistemi hazır\n";
    echo "✅ Teklif detayları sistemi hazır\n";
    echo "✅ Dosya yükleme sistemi hazır\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== TABLO KONTROL ===\n";

    // Test users tablosu
    try {
        $result = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $result->fetch();
        echo "✅ Users tablosu mevcut! Kayıt sayısı: " . $count['count'] . "\n";

        // Test kullanıcı bilgileri
        $result = $db->query("SELECT id, phone, name, is_verified FROM users LIMIT 3");
        echo "\n👥 Örnek kullanıcılar:\n";
        while ($row = $result->fetch()) {
            echo "  - ID: {$row['id']}, Phone: {$row['phone']}, Name: {$row['name']}, Verified: {$row['is_verified']}\n";
        }

        // Test login için şifre kontrol
        $result = $db->query("SELECT phone, password FROM users WHERE phone = '+905551234567' LIMIT 1");
        $user = $result->fetch();
        if ($user) {
            echo "\n🔐 Test kullanıcısı bulundu: {$user['phone']}\n";
            echo "   Password hash exists: " . (strlen($user['password']) > 10 ? "✅ Yes" : "❌ No") . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Users tablosu yok! Hata: " . $e->getMessage() . "\n";
    }

    // Diğer önemli tabloları kontrol et
    $tables = ['vehicles', 'reminders', 'campaigns', 'notifications'];
    foreach ($tables as $table) {
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $result->fetch();
            echo "✅ $table: " . $count['count'] . " kayıt\n";
        } catch (Exception $e) {
            echo "❌ $table tablosu yok\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

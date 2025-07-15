<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    echo "=== Hatırlatıcı Tablosu Güncelleniyor ===\n";

    // reminder_days alanını ekle
    try {
        $pdo->exec("ALTER TABLE reminders ADD COLUMN reminder_days INT DEFAULT 1 COMMENT 'Kaç gün önce hatırlatılacak'");
        echo "✓ reminder_days alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "- reminder_days alanı zaten mevcut\n";
        } else {
            throw $e;
        }
    }

    // Mevcut hatırlatıcıları 1 gün olarak güncelle
    $pdo->exec("UPDATE reminders SET reminder_days = 1 WHERE reminder_days IS NULL");
    echo "✓ Mevcut hatırlatıcılar güncellendi\n";

    // Örnek veriler ekle (test için)
    try {
        $stmt = $pdo->prepare("INSERT INTO reminders (vehicle_id, title, description, reminder_date, type, reminder_days) VALUES (?, ?, ?, ?, ?, ?)");

        $reminders = [
            [1, 'Seyrüsefer Yenileme Hatırlatıcısı', 'Seyrüsefer aboneliği yenileme zamanı yaklaşıyor', '2025-09-24', 'navigation', 30],
            [1, 'Seyrüsefer Yenileme Hatırlatıcısı', 'Seyrüsefer aboneliği yenileme zamanı yaklaşıyor', '2025-09-30', 'navigation', 7],
            [1, 'Seyrüsefer Yenileme Hatırlatıcısı', 'Seyrüsefer aboneliği yenileme zamanı yaklaşıyor', '2025-10-01', 'navigation', 1],
        ];

        foreach ($reminders as $reminder) {
            $stmt->execute($reminder);
        }
        echo "✓ Örnek hatırlatıcılar eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "- Örnek hatırlatıcılar zaten mevcut\n";
        } else {
            echo "⚠ Örnek veriler eklenirken hata: " . $e->getMessage() . "\n";
        }
    }

    // Güncel tablo yapısını göster
    echo "\n=== Güncel Reminders Tablo Yapısı ===\n";
    $stmt = $pdo->query("DESCRIBE reminders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        printf(
            "%-20s | %-20s | %-8s | %s\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Key'] ?: '-'
        );
    }

    echo "\n✅ Hatırlatıcı tablosu güncellemeleri tamamlandı!\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

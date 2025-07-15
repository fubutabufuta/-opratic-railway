<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "Veritabanı güncellemeleri başlatılıyor...\n\n";

    // Kasko bitiş tarihi alanını ekle
    $sql1 = "ALTER TABLE vehicles ADD COLUMN kasko_expiry_date DATE NULL AFTER insurance_expiry_date";
    try {
        $conn->exec($sql1);
        echo "✓ kasko_expiry_date alanı eklendi\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "- kasko_expiry_date alanı zaten mevcut\n";
        } else {
            echo "✗ kasko_expiry_date eklenirken hata: " . $e->getMessage() . "\n";
        }
    }

    // Diğer tarih alanlarını kontrol et ve ekle
    $fields = [
        'last_service_date' => 'DATE NULL',
        'last_inspection_date' => 'DATE NULL',
        'insurance_expiry_date' => 'DATE NULL',
        'registration_expiry_date' => 'DATE NULL',
        'oil_change_date' => 'DATE NULL',
        'tire_change_date' => 'DATE NULL'
    ];

    foreach ($fields as $field => $type) {
        $sql = "ALTER TABLE vehicles ADD COLUMN $field $type";
        try {
            $conn->exec($sql);
            echo "✓ $field alanı eklendi\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- $field alanı zaten mevcut\n";
            } else {
                echo "✗ $field eklenirken hata: " . $e->getMessage() . "\n";
            }
        }
    }

    // Mevcut tablo yapısını göster
    echo "\n=== Güncel Vehicles Tablo Yapısı ===\n";
    $stmt = $conn->query("DESCRIBE vehicles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        echo sprintf(
            "%-25s %-15s %-5s\n",
            $column['Field'],
            $column['Type'],
            $column['Null']
        );
    }

    echo "\n✅ Veritabanı güncellemeleri tamamlandı!\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

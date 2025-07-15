<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "=== Vehicles Tablo Yapısı ===\n\n";

    $stmt = $conn->query("SHOW COLUMNS FROM vehicles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        echo sprintf(
            "%-25s | %-20s | %-5s | %s\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Key']
        );
    }

    echo "\n=== Tarih Alanları Kontrolü ===\n";
    $date_fields = [
        'last_service_date',
        'last_inspection_date',
        'insurance_expiry_date',
        'kasko_expiry_date',
        'registration_expiry_date',
        'oil_change_date',
        'tire_change_date'
    ];

    foreach ($date_fields as $field) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] == $field) {
                $found = true;
                echo "✓ $field mevcut\n";
                break;
            }
        }
        if (!$found) {
            echo "✗ $field EKSİK!\n";
        }
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}

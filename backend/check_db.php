<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Veritabanı tabloları:\n";
    
    // Tabloları listele
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\nVehicles tablosu yapısı:\n";
    $columns = $db->query("DESCRIBE vehicles")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\nUsers tablosu yapısı:\n";
    $columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\nReminders tablosu yapısı:\n";
    $columns = $db->query("DESCRIBE reminders")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\nMevcut veriler:\n";
    echo "Users: " . $db->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
    echo "Vehicles: " . $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn() . "\n";
    echo "Reminders: " . $db->query("SELECT COUNT(*) FROM reminders")->fetchColumn() . "\n";
    echo "Campaigns: " . $db->query("SELECT COUNT(*) FROM campaigns")->fetchColumn() . "\n";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 
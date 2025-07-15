<?php
include_once 'config/database.php';

echo "Cleaning blob URLs from database...\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        echo "ERROR: Database connection failed\n";
        exit(1);
    }

    // Blob URL'leri temizle
    $query = "UPDATE vehicles SET image = NULL WHERE image LIKE 'blob:%'";
    $stmt = $db->prepare($query);

    if ($stmt->execute()) {
        $affectedRows = $stmt->rowCount();
        echo "SUCCESS: Cleaned $affectedRows blob URLs from vehicles table\n";
    } else {
        echo "ERROR: Failed to clean blob URLs\n";
    }

    // Güncellenmiş durumu göster
    $query = "SELECT COUNT(*) as total, 
                     SUM(CASE WHEN image IS NOT NULL AND image != '' THEN 1 ELSE 0 END) as with_image,
                     SUM(CASE WHEN image LIKE 'blob:%' THEN 1 ELSE 0 END) as blob_urls
              FROM vehicles";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();

    echo "\nVehicle image statistics:\n";
    echo "  Total vehicles: " . $result['total'] . "\n";
    echo "  With images: " . $result['with_image'] . "\n";
    echo "  Blob URLs remaining: " . $result['blob_urls'] . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

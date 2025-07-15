<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Year Field Debug ===\n";

// RR içeren kayıtları kontrol et
$result = $db->query("SELECT id, year, vehicle_type, plate_code FROM inspection_schedule WHERE plate_code LIKE '%RR%' LIMIT 3");

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}\n";
    echo "Year: {$row['year']} (type: " . gettype($row['year']) . ")\n";
    echo "Vehicle Type: {$row['vehicle_type']}\n";
    echo "Plate Codes: {$row['plate_code']}\n";
    echo "---\n";
}

$currentYear = date('Y');
echo "\nCurrent year: $currentYear (type: " . gettype($currentYear) . ")\n";

// Direkt karşılaştırma test
echo "\nDirect comparison test:\n";
$testResult = $db->query("SELECT COUNT(*) as count FROM inspection_schedule WHERE year = 2025 AND vehicle_type = 'D'");
$count = $testResult->fetch(PDO::FETCH_ASSOC)['count'];
echo "Records with year=2025 and type=D: $count\n";

// String karşılaştırma test
$testResult2 = $db->query("SELECT COUNT(*) as count FROM inspection_schedule WHERE year = '2025' AND vehicle_type = 'D'");
$count2 = $testResult2->fetch(PDO::FETCH_ASSOC)['count'];
echo "Records with year='2025' and type=D: $count2\n";
?> 
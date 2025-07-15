<?php
echo "=== Oto Asist Database Export ===\n\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sql_dump = "-- Oto Asist Database Export\n";
    $sql_dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_dump .= "START TRANSACTION;\n";
    $sql_dump .= "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        echo "Processing: $table\n";

        // Table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_dump .= $row['Create Table'] . ";\n\n";

        // Table data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $sql_dump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $escaped = [];
                foreach ($row as $val) {
                    $escaped[] = $val === null ? 'NULL' : "'" . addslashes($val) . "'";
                }
                $values[] = "(" . implode(', ', $escaped) . ")";
            }

            $sql_dump .= implode(",\n", $values) . ";\n\n";
        }
    }

    $sql_dump .= "COMMIT;\n";

    $filename = 'otoasist_production.sql';
    file_put_contents($filename, $sql_dump);

    echo "\n✅ Export completed: $filename\n";
    echo "📊 Size: " . round(filesize($filename) / 1024, 2) . " KB\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

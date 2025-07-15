<?php
echo "Oto Asist Database Export Tool\n";
echo "===============================\n\n";

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sql_dump = "-- Oto Asist Database Export\n";
    $sql_dump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_dump .= "START TRANSACTION;\n";
    $sql_dump .= "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        echo "Exporting table: $table\n";

        // Get table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $sql_dump .= "-- Table structure for table `$table`\n";
        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_dump .= $row['Create Table'] . ";\n\n";

        // Get table data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $sql_dump .= "-- Dumping data for table `$table`\n";
            $sql_dump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $escaped_values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $escaped_values[] = 'NULL';
                    } else {
                        $escaped_values[] = "'" . addslashes($value) . "'";
                    }
                }
                $values[] = "(" . implode(', ', $escaped_values) . ")";
            }

            $sql_dump .= implode(",\n", $values) . ";\n\n";
        }
    }

    $sql_dump .= "COMMIT;\n";

    // Save to file
    $filename = 'otoasist_export_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($filename, $sql_dump);

    echo "\n✅ Database exported successfully!\n";
    echo "📁 File: $filename\n";
    echo "📊 Size: " . round(filesize($filename) / 1024, 2) . " KB\n";
    echo "🗂️ Tables exported: " . count($tables) . "\n";

    echo "\nTables included:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "  - $table: $count records\n";
    }
} catch (Exception $e) {
    echo "❌ Export failed: " . $e->getMessage() . "\n";
}

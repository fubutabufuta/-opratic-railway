<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "🔄 Importing database schema...\n";

    // SQL dosyasını oku
    $sqlFile = '../database/otoasist_mysql_import.sql';
    if (!file_exists($sqlFile)) {
        echo "❌ SQL file not found: $sqlFile\n";
        exit(1);
    }

    $sql = file_get_contents($sqlFile);

    // SQL komutlarını ayrıştır
    $commands = explode(';', $sql);
    $successCount = 0;
    $errorCount = 0;

    foreach ($commands as $command) {
        $command = trim($command);
        if (empty($command) || substr($command, 0, 2) == '--') {
            continue;
        }

        try {
            $db->exec($command);
            $successCount++;
            if (strpos($command, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?([^`\s]+)`?/', $command, $matches);
                if (isset($matches[1])) {
                    echo "✅ Created table: {$matches[1]}\n";
                }
            } else if (strpos($command, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO `?([^`\s]+)`?/', $command, $matches);
                if (isset($matches[1])) {
                    echo "📝 Inserted data into: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            $errorCount++;
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "⚠️ Error executing command: " . substr($command, 0, 50) . "...\n";
                echo "   " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n📊 Import Summary:\n";
    echo "   ✅ Successful commands: $successCount\n";
    echo "   ❌ Failed commands: $errorCount\n";

    // Verify users table
    echo "\n🔍 Verifying users table...\n";
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch();
    echo "👥 Users count: " . $count['count'] . "\n";

    if ($count['count'] > 0) {
        echo "\n✨ Sample users:\n";
        $result = $db->query("SELECT id, phone, name FROM users LIMIT 3");
        while ($row = $result->fetch()) {
            echo "   - ID: {$row['id']}, Phone: {$row['phone']}, Name: {$row['name']}\n";
        }
    }

    echo "\n🎉 Database import completed!\n";
} catch (Exception $e) {
    echo "❌ Import failed: " . $e->getMessage() . "\n";
    exit(1);
}

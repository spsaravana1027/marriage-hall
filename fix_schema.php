<?php
echo "STARTING SCRIPT\n";
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'includes/db.php';
    echo "DB INCLUDED\n";
    
    if (!isset($pdo)) {
        die("PDO NOT SET\n");
    }

    $queries = [
        "ALTER TABLE gallery ADD COLUMN IF NOT EXISTS category ENUM('room', 'mahal') DEFAULT 'mahal' AFTER id",
        "ALTER TABLE gallery ADD COLUMN IF NOT EXISTS title_ta VARCHAR(255) DEFAULT NULL AFTER title",
        "ALTER TABLE gallery ADD COLUMN IF NOT EXISTS description_en TEXT DEFAULT NULL AFTER title_ta",
        "ALTER TABLE gallery ADD COLUMN IF NOT EXISTS description_ta TEXT DEFAULT NULL AFTER description_en"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
        echo "Executed: $q\n";
    }
    echo "ALL SUCCESS\n";
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}

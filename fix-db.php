<?php
require_once __DIR__ . '/helpers/utilities.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';

try {
    $db = Database::connection();
    
    // Add qty
    try {
        $db->exec("ALTER TABLE stock_corrections ADD COLUMN qty DECIMAL(12,4) NOT NULL DEFAULT 0 AFTER raw_material_id");
        echo "✅ Column 'qty' added successfully.<br>";
    } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ Column 'qty' already exists.<br>";
        } else {
            echo "❌ Error adding 'qty': " . $e->getMessage() . "<br>";
        }
    }

    echo "<br><b>Database patch completed. You can delete this file now.</b>";

} catch (Throwable $e) {
    echo "Connection error: " . $e->getMessage();
}

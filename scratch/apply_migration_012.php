<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $pdo = Database::connection();
    $sql = file_get_contents(__DIR__ . '/../database/012_add_print_queue_columns.sql');
    $pdo->exec($sql);
    echo "Migration 012 applied successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist. Skipping.\n";
    } else {
        echo "Database error: " . $e->getMessage() . "\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

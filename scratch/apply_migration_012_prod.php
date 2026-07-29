<?php
require_once __DIR__ . '/../helpers/functions.php';

try {
    $host = app_env('PROD_DB_HOST');
    $db   = app_env('PROD_DB_DATABASE');
    $user = app_env('PROD_DB_USERNAME');
    $pass = app_env('PROD_DB_PASSWORD');
    $charset = app_env('PROD_DB_CHARSET', 'utf8mb4');

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    
    $sql = file_get_contents(__DIR__ . '/../database/012_add_print_queue_columns.sql');
    $pdo->exec($sql);
    echo "Migration 012 applied successfully to PRODUCTION.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist in PRODUCTION. Skipping.\n";
    } else {
        echo "Database error: " . $e->getMessage() . "\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

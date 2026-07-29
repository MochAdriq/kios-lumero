<?php
require_once __DIR__ . '/../helpers/functions.php';
try {
    $dsn = "mysql:host=" . app_env('PROD_DB_HOST') . ";dbname=" . app_env('PROD_DB_DATABASE') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, app_env('PROD_DB_USERNAME'), app_env('PROD_DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    print_r($stmt->fetchAll());
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

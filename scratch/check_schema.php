<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== order_items ===\n";
print_r($pdo->query('DESCRIBE order_items')->fetchAll(PDO::FETCH_ASSOC));

echo "=== menu_items? ===\n";
try {
    print_r($pdo->query('DESCRIBE menu_items')->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

echo "=== price_rules? ===\n";
try {
    print_r($pdo->query('DESCRIBE price_rules')->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

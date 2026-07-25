<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "=== ORDERS TABLE (Lumero) ===\n";
$cols = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";

echo "\n=== ORDER_ITEMS TABLE (Lumero) ===\n";
$cols = $pdo->query("DESCRIBE order_items")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";

echo "\n=== SAMPLE ORDERS (Lumero) ===\n";
$rows = $pdo->query("SELECT id, outlet_id, order_no, payment_method, payment_status, grand_total, status, created_at FROM orders LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

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

echo "=== business_capitals TABLE (Lumero) ===\n";
$cols = $pdo->query("DESCRIBE business_capitals")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";

echo "\n=== vendors TABLE (Lumero) ===\n";
$cols = $pdo->query("DESCRIBE vendors")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";

echo "\n=== purchase_orders TABLE (Lumero) ===\n";
$cols = $pdo->query("DESCRIBE purchase_orders")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";

echo "\n=== purchase_order_items TABLE (Lumero) ===\n";
$cols = $pdo->query("DESCRIBE purchase_order_items")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";

echo "\n=== outlet_raw_materials TABLE (Lumero) ===\n";
$cols = $pdo->query("DESCRIBE outlet_raw_materials")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | null:{$c['Null']}\n";

echo "\n=== Sample raw_materials ===\n";
$rows = $pdo->query("SELECT id, name FROM raw_materials LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  ID {$r['id']}: {$r['name']}\n";

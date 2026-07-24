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

// Check daily_product_stocks structure
echo "=== daily_product_stocks COLUMNS ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM daily_product_stocks")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']} | Default:" . ($c['Default'] ?? 'NULL') . "\n";

echo "\n=== SAMPLE daily_product_stocks (outlet 5, first 5) ===\n";
$stmt = $pdo->query("SELECT * FROM daily_product_stocks WHERE outlet_id = 5 ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

echo "\n=== COUNT daily_product_stocks PER OUTLET ===\n";
$stmt = $pdo->query("SELECT outlet_id, COUNT(*) as cnt FROM daily_product_stocks GROUP BY outlet_id ORDER BY outlet_id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) echo "  Outlet {$r['outlet_id']}: {$r['cnt']} stock rows\n";

// Check product_branch_overrides
echo "\n=== product_branch_overrides COLUMNS ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM product_branch_overrides")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | Null:{$c['Null']}\n";

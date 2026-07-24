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

$SOURCE_OUTLET = 5;
$TARGET_OUTLET = 7;

echo "=== outlet_raw_materials structure ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM outlet_raw_materials")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']}\n";

$rows = $pdo->query("SELECT COUNT(*) FROM outlet_raw_materials WHERE outlet_id = {$SOURCE_OUTLET}")->fetchColumn();
echo "\nTotal raw materials at outlet {$SOURCE_OUTLET}: {$rows}\n";

$rows = $pdo->query("SELECT COUNT(*) FROM outlet_raw_materials WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
echo "Total raw materials at outlet {$TARGET_OUTLET}: {$rows}\n";

// Also we should check if there are recipes that refer to specific variant ids because we cloned the variants!
// Wait! If we cloned the variants (created new product_variant_id), the recipes are still tied to the old product_variant_id!
echo "\n=== RECIPES ===\n";
$stmt = $pdo->query("SELECT product_variant_id, COUNT(*) as cnt FROM recipes GROUP BY product_variant_id LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));


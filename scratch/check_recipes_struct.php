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

echo "=== recipes structure ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM recipes")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']}\n";

echo "=== recipe_items structure ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM recipe_items")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']}\n";

// Map old product variants to new product variants
// We know we cloned from outlet 5 to outlet 7. Let's see the mapping.
$oldVariants = $pdo->query("SELECT id, sku FROM product_variants WHERE outlet_id = 5")->fetchAll(PDO::FETCH_KEY_PAIR);
$newVariants = $pdo->query("SELECT id, sku FROM product_variants WHERE outlet_id = 7")->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Old variants: " . count($oldVariants) . "\n";
echo "New variants: " . count($newVariants) . "\n";

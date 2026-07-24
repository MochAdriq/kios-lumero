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

// Update id 574 menjadi Kalibunder (5)
$pdo->exec("UPDATE recipes SET outlet_id = 5 WHERE id = 574");
echo "Sub-recipe 574 updated to outlet_id = 5 (Kalibunder)\n";

// Duplikasi ke outlet 7 (Midtrans)
$stmt = $pdo->query("SELECT * FROM recipes WHERE id = 574");
$r = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("INSERT INTO recipes (outlet_id, name, recipe_type, yield_qty, yield_unit_id, is_active, created_at, updated_at) VALUES (?, ?, 'sub_recipe', ?, ?, 1, NOW(), NOW())");
$stmt->execute([7, $r['name'], $r['yield_qty'], $r['yield_unit_id']]);
$newRecipeId = $pdo->lastInsertId();

$stmtItems = $pdo->query("SELECT * FROM recipe_items WHERE recipe_id = 574");
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    $stmtIns = $pdo->prepare("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, qty, unit_id, cost_per_unit, total_cost) VALUES (?, ?, ?, ?, ?, 0, 0)");
    $stmtIns->execute([$newRecipeId, $item['item_type'], $item['raw_material_id'], $item['qty'], $item['unit_id']]);
}

echo "Sub-recipe duplicated to outlet 7 (Midtrans) with ID: $newRecipeId\n";


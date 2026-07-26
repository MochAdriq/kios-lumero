<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$outletId = 8;

// 1. Check which Gas ID is used in Pasekon recipes
echo "=== Gas dalam Resep Pasekon (outlet_id=8) ===\n";
$gasInRecipes = $pdo->query("
    SELECT r.id as recipe_id, r.name as recipe_name, r.outlet_id,
           ri.id as item_id, ri.raw_material_id, rm.name as rm_name, rm.outlet_id as rm_outlet_id, rm.id as rm_id,
           ri.qty, u.symbol
    FROM recipe_items ri
    JOIN recipes r ON r.id = ri.recipe_id
    JOIN raw_materials rm ON rm.id = ri.raw_material_id
    LEFT JOIN units u ON u.id = ri.unit_id
    WHERE r.outlet_id = $outletId
      AND rm.name LIKE '%Gas%'
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
print_r($gasInRecipes);

// 2. Sub-recipe items in outlet 8 recipes
echo "\n=== Gas di Sub-Resep yang digunakan Outlet 8 ===\n";
$gasInSubRecipes = $pdo->query("
    SELECT sr.id as sub_recipe_id, sr.name as sub_recipe_name, sr.outlet_id as sub_outlet,
           ri.raw_material_id, rm.name as rm_name, rm.outlet_id as rm_outlet_id, rm.id as rm_id, ri.qty
    FROM recipes sr
    JOIN recipe_items ri ON ri.recipe_id = sr.id
    JOIN raw_materials rm ON rm.id = ri.raw_material_id
    WHERE rm.name LIKE '%Gas%'
      AND sr.id IN (
          SELECT ri2.sub_recipe_id FROM recipe_items ri2
          JOIN recipes r ON r.id = ri2.recipe_id
          WHERE r.outlet_id = $outletId AND ri2.item_type = 'sub_recipe'
      )
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
print_r($gasInSubRecipes);

// 3. Stock of Gas (rm.id=178) in outlet 8
echo "\n=== Status Stok Gas ID 178 (Outlet 8) ===\n";
$gas178 = $pdo->query("
    SELECT rm.id, rm.name, rm.outlet_id, rm.stock_qty as global_stock, rm.average_cost,
           orm.id as orm_id, orm.outlet_id as orm_outlet, orm.stock_qty as outlet_stock, orm.average_cost as orm_cost
    FROM raw_materials rm
    LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id
    WHERE rm.id = 178
")->fetchAll(PDO::FETCH_ASSOC);
print_r($gas178);

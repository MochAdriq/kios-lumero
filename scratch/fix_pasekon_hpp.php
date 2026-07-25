<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== 1. MENG-CLONE OUTLET_RAW_MATERIALS DARI KALIBUNDER KE PASEKON ===\n";
$kalibunderOrms = $pdo->query("SELECT raw_material_id, average_cost FROM outlet_raw_materials WHERE outlet_id = 5")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("INSERT INTO outlet_raw_materials (outlet_id, raw_material_id, average_cost, created_at, updated_at) VALUES (8, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE average_cost = VALUES(average_cost), updated_at = NOW()");

$cloned = 0;
foreach ($kalibunderOrms as $orm) {
    $stmt->execute([$orm['raw_material_id'], $orm['average_cost']]);
    $cloned++;
}
echo "Berhasil meng-clone $cloned ORMs ke Outlet Pasekon (8).\n\n";

echo "=== 2. REKALKULASI HPP SEMUA RESEP DI PASEKON ===\n";
// 2a. Update recipe_items.cost_per_unit and total_cost based on outlet_raw_materials
$pdo->exec("
    UPDATE recipe_items ri
    JOIN recipes r ON r.id = ri.recipe_id
    JOIN outlet_raw_materials orm ON orm.raw_material_id = ri.raw_material_id AND orm.outlet_id = r.outlet_id
    SET ri.cost_per_unit = orm.average_cost,
        ri.total_cost = ri.qty * orm.average_cost
    WHERE r.outlet_id = 8 AND ri.item_type = 'raw_material'
");
echo "Update recipe_items raw_material cost_per_unit and total_cost success.\n";

// 2b. Update recipes total_hpp from recipe_items sum
$pdo->exec("
    UPDATE recipes r
    JOIN (
        SELECT recipe_id, SUM(total_cost) as total_hpp
        FROM recipe_items
        GROUP BY recipe_id
    ) sum_items ON sum_items.recipe_id = r.id
    SET r.total_hpp = sum_items.total_hpp
    WHERE r.outlet_id = 8
");
echo "Update recipes total_hpp success.\n";

// 2c. Update sub-recipe cost in recipe_items where item_type = 'sub_recipe'
$pdo->exec("
    UPDATE recipe_items ri
    JOIN recipes r ON r.id = ri.recipe_id
    JOIN recipes sub ON sub.id = ri.sub_recipe_id
    SET ri.cost_per_unit = (sub.total_hpp / sub.yield_qty),
        ri.total_cost = ri.qty * (sub.total_hpp / sub.yield_qty)
    WHERE r.outlet_id = 8 AND ri.item_type = 'sub_recipe'
");
echo "Update recipe_items sub_recipe cost_per_unit and total_cost success.\n";

// 2d. Re-update recipes total_hpp again (since sub_recipe costs might have changed)
$pdo->exec("
    UPDATE recipes r
    JOIN (
        SELECT recipe_id, SUM(total_cost) as total_hpp
        FROM recipe_items
        GROUP BY recipe_id
    ) sum_items ON sum_items.recipe_id = r.id
    SET r.total_hpp = sum_items.total_hpp
    WHERE r.outlet_id = 8
");
echo "Re-update recipes total_hpp success.\n";

// 2e. Update product_variants hpp and margins
$pdo->exec("
    UPDATE product_variants pv
    JOIN recipes r ON r.product_variant_id = pv.id
    SET pv.hpp = r.total_hpp,
        pv.margin_amount = pv.selling_price - r.total_hpp,
        pv.margin_percent = IF(pv.selling_price > 0, ((pv.selling_price - r.total_hpp) / pv.selling_price) * 100, 0)
    WHERE pv.outlet_id = 8 AND r.recipe_type = 'final'
");
echo "Update product_variants margins success.\n";

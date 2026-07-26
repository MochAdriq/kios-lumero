<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

$wrong_items = $pdo->query("
    SELECT ri.id, ri.recipe_id, ri.sub_recipe_id, sr.name as sr_name, sr.outlet_id as sr_outlet, r.name as recipe_name
    FROM recipe_items ri
    JOIN recipes r ON ri.recipe_id = r.id
    JOIN recipes sr ON ri.sub_recipe_id = sr.id
    WHERE r.outlet_id = $outlet_id AND sr.outlet_id != $outlet_id
")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($wrong_items) . " wrong sub_recipe references.\n";

// Fix them!
foreach ($wrong_items as $wi) {
    // Find matching sub_recipe in outlet 8
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE name = ? AND outlet_id = $outlet_id AND recipe_type = 'sub_recipe' LIMIT 1");
    $stmt->execute([$wi['sr_name']]);
    $correct_sr_id = $stmt->fetchColumn();
    if ($correct_sr_id) {
        $pdo->prepare("UPDATE recipe_items SET sub_recipe_id = ? WHERE id = ?")->execute([$correct_sr_id, $wi['id']]);
        echo "Fixed item {$wi['id']} from sr {$wi['sub_recipe_id']} to $correct_sr_id\n";
    } else {
        echo "Could not find correct sr for {$wi['sr_name']} in outlet 8\n";
    }
}

// Check for wrong raw_material references (pointing to outlet 1 or 5)
$wrong_rms = $pdo->query("
    SELECT ri.id, ri.recipe_id, ri.raw_material_id, rm.name as rm_name, rm.outlet_id as rm_outlet, r.name as recipe_name
    FROM recipe_items ri
    JOIN recipes r ON ri.recipe_id = r.id
    JOIN raw_materials rm ON ri.raw_material_id = rm.id
    WHERE r.outlet_id = $outlet_id AND rm.outlet_id != $outlet_id AND rm.outlet_id != 1
")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($wrong_rms) . " wrong raw_material references.\n";
foreach ($wrong_rms as $wrm) {
    $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ? AND outlet_id = $outlet_id LIMIT 1");
    $stmt->execute([$wrm['rm_name']]);
    $correct_rm_id = $stmt->fetchColumn();
    if ($correct_rm_id) {
        $pdo->prepare("UPDATE recipe_items SET raw_material_id = ? WHERE id = ?")->execute([$correct_rm_id, $wrm['id']]);
        echo "Fixed RM item {$wrm['id']} from {$wrm['raw_material_id']} to $correct_rm_id\n";
    } else {
        echo "Could not find correct RM for {$wrm['rm_name']} in outlet 8\n";
    }
}

// Activate ONLY the Ayam Crispy Original variants
$pdo->query("UPDATE product_variants SET is_active = 1 WHERE outlet_id = $outlet_id AND is_active = 0 AND variant_name LIKE '%Original%' AND variant_name NOT LIKE '%Kentang%'");
echo "Activated inactive Ayam Original variants.\n";

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

echo "--- RAW MATERIALS --- \n";
$stmt = $pdo->prepare("SELECT id, name, sku, unit_id FROM raw_materials WHERE name LIKE '%ijo%' OR name LIKE '%hijau%' OR name LIKE '%cabe%' OR name LIKE '%cabai%' OR name LIKE '%bawang%' OR name LIKE '%minyak%' OR name LIKE '%garam%' OR name LIKE '%gula%' OR name LIKE '%penyedap%' OR name LIKE '%msg%' OR name LIKE '%tomat%' OR name LIKE '%jeruk%'");
$stmt->execute();
$rms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil nama unit
$stmtUnit = $pdo->query("SELECT id, symbol FROM units");
$units = [];
while ($u = $stmtUnit->fetch(PDO::FETCH_ASSOC)) {
    $units[$u['id']] = $u['symbol'];
}

foreach ($rms as $rm) {
    $unit = $units[$rm['unit_id']] ?? '';
    echo "- ID {$rm['id']} | {$rm['name']} ({$unit})\n";
}

echo "\n--- SUB RECIPES (SAMBAL) --- \n";
$stmt = $pdo->prepare("SELECT id, name, yield_qty, yield_unit_id FROM recipes WHERE recipe_type = 'sub_recipe' AND (name LIKE '%sambal%' OR name LIKE '%sambel%') AND is_active = 1 LIMIT 5");
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($recipes as $r) {
    $unit = $units[$r['yield_unit_id']] ?? '';
    echo "\nSub-Recipe: {$r['name']} (ID: {$r['id']}) - Yield: {$r['yield_qty']} {$unit}\n";
    $stmtItems = $pdo->prepare("SELECT item_type, raw_material_id, sub_recipe_id, qty, unit_id FROM recipe_items WHERE recipe_id = ?");
    $stmtItems->execute([$r['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        $itemUnit = $units[$item['unit_id']] ?? '';
        if ($item['item_type'] === 'raw_material') {
            $name = '';
            foreach ($rms as $rm) { if ($rm['id'] == $item['raw_material_id']) $name = $rm['name']; }
            if (!$name) {
                // query
                $n = $pdo->query("SELECT name FROM raw_materials WHERE id = " . $item['raw_material_id'])->fetchColumn();
                $name = $n;
            }
            echo "   -> [RM] {$name} : {$item['qty']} {$itemUnit}\n";
        } else {
            echo "   -> [SR] ID {$item['sub_recipe_id']} : {$item['qty']} {$itemUnit}\n";
        }
    }
}

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

// Mari kita insert semua raw_materials yang dipake di resep outlet 7 ke outlet_raw_materials
$stmt = $pdo->query("
    SELECT DISTINCT ri.raw_material_id
    FROM recipe_items ri
    JOIN recipes r ON r.id = ri.recipe_id
    WHERE r.outlet_id = 7 AND ri.item_type = 'raw_material'
");
$rmIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($rmIds) . " distinct raw_materials used by outlet 7.\n";

$inserted = 0;
foreach ($rmIds as $rmId) {
    if (!$rmId) continue;
    // Check if it already exists
    $exists = $pdo->query("SELECT id FROM outlet_raw_materials WHERE outlet_id = 7 AND raw_material_id = $rmId")->fetchColumn();
    if (!$exists) {
        $pdo->prepare("
            INSERT INTO outlet_raw_materials (outlet_id, raw_material_id, stock_qty, min_stock_qty, average_cost, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ")->execute([
            7,
            $rmId,
            1000.00,
            1.00,
            0.00
        ]);
        $inserted++;
    } else {
        // Update stock to 1000 just in case
        $pdo->prepare("UPDATE outlet_raw_materials SET stock_qty = 1000.00 WHERE outlet_id = 7 AND raw_material_id = ?")->execute([$rmId]);
    }
}
echo "Inserted $inserted missing raw materials for outlet 7.\n";

// Juga kita butuh raw materials dari sub_recipes!
// Untuk aman nya, kita bisa insert SEMUA raw_materials ke outlet 7.
$stmt = $pdo->query("SELECT id FROM raw_materials");
$allRm = $stmt->fetchAll(PDO::FETCH_COLUMN);
$allInserted = 0;
foreach ($allRm as $rmId) {
    $exists = $pdo->query("SELECT id FROM outlet_raw_materials WHERE outlet_id = 7 AND raw_material_id = $rmId")->fetchColumn();
    if (!$exists) {
        $pdo->prepare("
            INSERT INTO outlet_raw_materials (outlet_id, raw_material_id, stock_qty, min_stock_qty, average_cost, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ")->execute([7, $rmId, 1000.00, 1.00, 0.00]);
        $allInserted++;
    }
}
echo "Inserted $allInserted additional raw materials for outlet 7 from global list.\n";

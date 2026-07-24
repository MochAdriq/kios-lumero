<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/pos/POSModel.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$dbObj = new Database(); // dummy wrapper if needed, or pass pdo

$posModel = new \Modules\POS\POSModel($dbObj);
// Wait, POSModel constructor might be expecting something else, let's just do a direct query.

$outletId = 7;
// Emulate the POSModel calculation for a variant:
$mRecipe = new \Modules\Recipes\RecipeModel($dbObj);
// Get first variant for outlet 7
$stmt = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = 7 LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $yield = $mRecipe->calculateMaxYield((int)$row['id'], $outletId);
    echo "Variant {$row['variant_name']} (ID: {$row['id']}) - Max Yield (ready_stock): {$yield}\n";
}

<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';
$rm = new RecipeModel();

$recipes = [1060, 940];
foreach ($recipes as $rId) {
    echo "Recipe ID: $rId\n";
    $bom = $rm->explodeBOM($rId, 1.0);
    foreach ($bom as $rmId => $qty) {
        $mat = $pdo->query("SELECT name, outlet_id FROM raw_materials WHERE id = $rmId")->fetch(PDO::FETCH_ASSOC);
        echo "  - RM ID: $rmId | Name: {$mat['name']} | Outlet: {$mat['outlet_id']}\n";
    }
    echo "\n";
}

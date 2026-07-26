<?php
session_start();
$_GET['outlet_id'] = 8;
$_SESSION['lumero_selected_outlet_id'] = 8;
$_SESSION['user'] = ['id' => 1];

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

$rm = new RecipeModel();
$ref = new ReflectionClass($rm);
$prop = $ref->getParentClass()->getProperty('db');
$prop->setAccessible(true);
$prop->setValue($rm, $pdo);

$recipe = $pdo->query("SELECT id FROM recipes WHERE product_variant_id = 1001 LIMIT 1")->fetchColumn();
echo "Recipe ID: $recipe\n";

$bom = $rm->explodeBOM($recipe, 1.0);
echo "BOM for Dada Original + Nasi:\n";
print_r($bom);

$stmt = $pdo->prepare("
    SELECT rm.id, rm.name, COALESCE(orm.stock_qty, rm.stock_qty, 0) AS stock_qty, rm.outlet_id
    FROM raw_materials rm
    LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = 8
    WHERE rm.id IN (" . implode(',', array_keys($bom)) . ")
");
$stmt->execute();
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Stocks for BOM items:\n";
print_r($stocks);

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

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$count = $rm->recalculateAll(1);
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Recalculated $count recipes.\n";

<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$rm = new RecipeModel($pdo);

// Initialize Session correctly for Auth::id()
$_SESSION = [
    'outlet_id' => 8, 
    'user' => ['id' => 1] // This is probably what Auth::id() expects
];

try {
    $rm->recalculateAll(8);
    echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

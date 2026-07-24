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
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$dbObj = new Database(); // dummy wrapper
$mRecipe = new RecipeModel($dbObj);

$id = 394;
// Let's implement what getRecipe does but print step by step
echo "Calling getRecipe(394)...\n";
$recipe = $mRecipe->getRecipe($id);
if (!$recipe) {
    echo "getRecipe returned null!\n";
} else {
    echo "Recipe found! Items:\n";
    print_r($recipe['items']);
}

// Check database connection of Model vs PDO
$sql = "SELECT id FROM recipes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
echo "PDO found: " . print_r($stmt->fetch(), true) . "\n";

// Use mRecipe->db directly?
$rc = new ReflectionClass(Model::class);
$prop = $rc->getProperty('db');
$prop->setAccessible(true);
$modelDb = $prop->getValue($mRecipe);
$stmt2 = $modelDb->prepare($sql);
$stmt2->execute([$id]);
echo "Model DB found: " . print_r($stmt2->fetch(), true) . "\n";

<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$dbObj = new Database();
$mRecipe = new RecipeModel($dbObj);

$recipe = $mRecipe->getRecipe(394);
if (!$recipe) {
    echo "Recipe 394 not found via getRecipe()\n";
} else {
    echo "Recipe 394 found via getRecipe()\n";
    echo "Items Count: " . count($recipe['items']) . "\n";
    print_r($recipe['items']);
}

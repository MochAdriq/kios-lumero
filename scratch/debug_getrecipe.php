<?php
$_GET['outlet_id'] = 8;
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$rm = new RecipeModel();
$recipe = $rm->getRecipe(1052);
print_r($recipe['items']);

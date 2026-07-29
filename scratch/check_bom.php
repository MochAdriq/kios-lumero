<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$rm = new RecipeModel();
$bom = $rm->explodeBOM(1257, 1.0);
echo "BOM:\n";
print_r($bom);

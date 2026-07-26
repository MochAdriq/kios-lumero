<?php
session_start();
$_SESSION['outlet_id'] = 8;
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/recipes/RecipeModel.php';

$dbConn = Database::connection();
$rm = new RecipeModel();
$dbConn->exec('SET FOREIGN_KEY_CHECKS=0');

$res = $rm->recalculate(1052, 1);
echo "Recalculated total: $res\n";
$dbConn->exec('SET FOREIGN_KEY_CHECKS=1');

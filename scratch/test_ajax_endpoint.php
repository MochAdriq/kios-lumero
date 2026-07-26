<?php
$_GET['variant_id'] = 1108; // Valid variant
session_start();
$_SESSION['user'] = ['id' => 1];
$_SESSION['lumero_selected_outlet_id'] = 8;
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../modules/daily_stock/DailyStockController.php';

$c = new DailyStockController();
ob_start();
$c->ajaxRecipeStock();
$res = ob_get_clean();

echo "Response from endpoint:\n";
echo $res;

<?php
require_once __DIR__ . '/../helpers/functions.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdoNew = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

echo "Lumero Min Date: " . $pdoNew->query("SELECT MIN(business_date) FROM daily_closing_reports WHERE outlet_id = 8")->fetchColumn() . "\n";
echo "Lumero Max Date: " . $pdoNew->query("SELECT MAX(business_date) FROM daily_closing_reports WHERE outlet_id = 8")->fetchColumn() . "\n";

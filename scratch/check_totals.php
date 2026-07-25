<?php
require_once 'C:/xampp/htdocs/kios-lumero/helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$total = $pdo->query('SELECT SUM(total_revenue) FROM daily_closing_reports WHERE outlet_id = 8')->fetchColumn();
echo "Total Revenue in reports: " . $total . "\n";

$pdoOld = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');
$oldPaid = $pdoOld->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
echo "Total Paid in old DB: " . $oldPaid . "\n";

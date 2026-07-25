<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$q1 = "SELECT COALESCE(SUM(total_revenue),0) gross_sales FROM daily_closing_reports WHERE outlet_id=8 AND business_date BETWEEN '2026-05-01' AND '2026-05-31'";
echo "May: " . $pdo->query($q1)->fetchColumn() . "\n";

$q2 = "SELECT COALESCE(SUM(total_revenue),0) gross_sales FROM daily_closing_reports WHERE outlet_id=8 AND business_date BETWEEN '2026-06-01' AND '2026-06-30'";
echo "June: " . $pdo->query($q2)->fetchColumn() . "\n";

$q3 = "SELECT COALESCE(SUM(total),0) FROM orders WHERE outlet_id=8 AND DATE(created_at) BETWEEN '2026-05-01' AND '2026-05-31'";
echo "May Orders Table: " . $pdo->query($q3)->fetchColumn() . "\n";

$q4 = "SELECT COALESCE(SUM(total),0) FROM orders WHERE outlet_id=8 AND DATE(created_at) BETWEEN '2026-06-01' AND '2026-06-30'";
echo "June Orders Table: " . $pdo->query($q4)->fetchColumn() . "\n";

<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// Find sauce raw materials (exclude sambal geprek, sambal ijo)
echo "Finding sauces in Outlet 8:\n";
$stmt = $pdo->query("SELECT id, name FROM raw_materials WHERE outlet_id = 8 AND name LIKE '%saus%' OR name LIKE '%bbq%' OR name LIKE '%mentai%' OR name LIKE '%keju%' OR name LIKE '%garlic%' OR name LIKE '%lada%' OR name LIKE '%sadis%' OR name LIKE '%teriyaki%' OR name LIKE '%cheese%'");
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($materials as $m) {
    echo "- ID {$m['id']}: {$m['name']}\n";
}

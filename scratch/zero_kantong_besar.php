<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);

// Find "kantong besar" in raw materials
$stmt = $pdo->query("SELECT id, name FROM raw_materials WHERE name LIKE '%kantong besar%'");
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($materials)) {
    echo "No raw material found matching 'kantong besar'.\n";
} else {
    foreach ($materials as $m) {
        echo "Found: ID {$m['id']} - {$m['name']}\n";
        // Zero stock for this material
        $update = $pdo->prepare("UPDATE outlet_raw_materials SET stock_qty = 0 WHERE raw_material_id = ?");
        $update->execute([$m['id']]);
        echo " -> Stock zeroed.\n";
    }
}

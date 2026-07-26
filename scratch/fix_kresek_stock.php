<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');
$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass);
$outlet_id = 8;

// Get Kantong Kresek ID
$kresekId = $pdo->query("SELECT id FROM raw_materials WHERE name = 'Kantong Kresek' AND outlet_id = 8 LIMIT 1")->fetchColumn();
if ($kresekId) {
    // Insert or Update outlet_raw_materials
    $check = $pdo->query("SELECT id FROM outlet_raw_materials WHERE raw_material_id = $kresekId AND outlet_id = 8")->fetchColumn();
    if ($check) {
        $pdo->query("UPDATE outlet_raw_materials SET stock_qty = 10000, average_cost = 200 WHERE id = $check");
    } else {
        $pdo->query("INSERT INTO outlet_raw_materials (outlet_id, raw_material_id, stock_qty, min_stock_qty, average_cost, created_at, updated_at) VALUES (8, $kresekId, 10000, 0, 200, NOW(), NOW())");
    }
    // Update raw_materials
    $pdo->query("UPDATE raw_materials SET stock_qty = 10000, average_cost = 200 WHERE id = $kresekId");
    echo "Kantong Kresek stock updated to 10000.\n";
}

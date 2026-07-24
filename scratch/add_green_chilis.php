<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Cari unit id untuk gram/gr
$stmt = $pdo->prepare("SELECT id FROM units WHERE symbol = 'gr' OR symbol = 'gram' LIMIT 1");
$stmt->execute();
$unitId = $stmt->fetchColumn();

if (!$unitId) {
    echo "Unit gram tidak ditemukan!\n";
    exit;
}

$newMaterials = [
    'Cabe Keriting Hijau',
    'Cabe Rawit Hijau'
];

foreach ($newMaterials as $name) {
    // Cek apakah sudah ada
    $stmt = $pdo->prepare("SELECT id FROM raw_materials WHERE name = ?");
    $stmt->execute([$name]);
    $existing = $stmt->fetchColumn();
    
    if (!$existing) {
        $sku = 'RM-' . strtoupper(substr(md5($name . time()), 0, 6));
        $stmt = $pdo->prepare("INSERT INTO raw_materials (name, sku, unit_id, stock_qty, min_stock_qty, average_cost, created_at, updated_at) VALUES (?, ?, ?, 0, 1, 0, NOW(), NOW())");
        $stmt->execute([$name, $sku, $unitId]);
        echo "Berhasil menambahkan bahan baku: $name (Unit ID: $unitId)\n";
    } else {
        echo "Bahan baku $name sudah ada (ID: $existing)\n";
    }
}

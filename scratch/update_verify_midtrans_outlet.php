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

$TARGET_OUTLET = 7;

// 1. Update Latitude and Longitude for Outlet Midtrans
echo "=== UPDATING MIDTRANS OUTLET LOCATION ===\n";
$lat = -6.2445;
$lng = 106.7975;
$address = "Pasaraya Blok M, Building B, 4th Floor, Jl. Iskandarsyah II No. 2, Melawai, Kebayoran Baru, Jakarta Selatan, DKI Jakarta 12160";

$pdo->prepare("UPDATE outlets SET latitude = ?, longitude = ?, address = ? WHERE id = ?")
    ->execute([$lat, $lng, $address, $TARGET_OUTLET]);

echo "Updated Outlet ID {$TARGET_OUTLET}:\n";
echo "  - Latitude: {$lat}\n";
echo "  - Longitude: {$lng}\n";
echo "  - Address: {$address}\n\n";

// 2. Double check products and stock
echo "=== DOUBLE CHECKING PRODUCTS AND STOCK ===\n";

$catCount = $pdo->query("SELECT COUNT(*) FROM product_categories WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
$prodCount = $pdo->query("SELECT COUNT(*) FROM products WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();
$varCount = $pdo->query("SELECT COUNT(*) FROM product_variants WHERE outlet_id = {$TARGET_OUTLET}")->fetchColumn();

$today = date('Y-m-d');
$stockRows = $pdo->query("SELECT COUNT(*) FROM daily_product_stocks WHERE outlet_id = {$TARGET_OUTLET} AND business_date = '{$today}'")->fetchColumn();

// Cek apakah semua qty stock adalah 5
$stockCheck = $pdo->query("SELECT MIN(opening_qty) as min_qty, MAX(opening_qty) as max_qty, MIN(closing_qty) as min_close, MAX(closing_qty) as max_close FROM daily_product_stocks WHERE outlet_id = {$TARGET_OUTLET} AND business_date = '{$today}'")->fetch(PDO::FETCH_ASSOC);

echo "Outlet Midtrans (ID: {$TARGET_OUTLET}):\n";
echo "  - Categories: {$catCount}\n";
echo "  - Products:   {$prodCount}\n";
echo "  - Variants:   {$varCount}\n";
echo "  - Stock rows: {$stockRows}\n";
if ($stockCheck['min_qty'] == 5 && $stockCheck['max_qty'] == 5 && $stockCheck['min_close'] == 5 && $stockCheck['max_close'] == 5) {
    echo "  - All stock qty: EXACTLY 5 (Checked!)\n";
} else {
    echo "  - WARNING: Stock qty is NOT exactly 5! Min: {$stockCheck['min_qty']}, Max: {$stockCheck['max_qty']}\n";
}

echo "\nDone checking!\n";

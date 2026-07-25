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

$PSKL_ID = 8;

echo "=== VARIAN PRODUK DI PASEKON YANG BELUM ADA RESEPNYA ===\n\n";

$sql = "
    SELECT p.name AS product_name, pv.variant_name, pv.sku, pv.selling_price
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    LEFT JOIN recipes r ON r.product_variant_id = pv.id AND r.outlet_id = pv.outlet_id
    WHERE pv.outlet_id = $PSKL_ID
    AND r.id IS NULL
    ORDER BY p.name ASC, pv.variant_name ASC
";

$missingRecipes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if (count($missingRecipes) === 0) {
    echo "Luar biasa! Semua varian di Pasekon sudah memiliki resep.\n";
} else {
    echo "Total Varian Tanpa Resep: " . count($missingRecipes) . "\n\n";
    $currentProduct = '';
    foreach ($missingRecipes as $row) {
        if ($currentProduct !== $row['product_name']) {
            $currentProduct = $row['product_name'];
            echo "--- " . strtoupper($currentProduct) . " ---\n";
        }
        echo "  - {$row['variant_name']} (SKU: {$row['sku']}, Harga: Rp " . number_format($row['selling_price'], 0, ',', '.') . ")\n";
    }
}

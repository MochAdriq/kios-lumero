<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Compare orders.total vs SUM(order_items.subtotal)
$query = "
    SELECT o.id, o.order_number, o.grand_total as total, COALESCE(SUM(oi.subtotal), 0) as items_total, o.grand_total - COALESCE(SUM(oi.subtotal), 0) as diff
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.outlet_id = 8
    GROUP BY o.id
    HAVING diff > 0
";

$discrepancies = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

echo "Orders with missing item totals: " . count($discrepancies) . "\n";
$sumDiff = 0;
foreach ($discrepancies as $row) {
    $sumDiff += $row['diff'];
}
echo "Total value lost due to skipped items: Rp " . number_format($sumDiff, 0, ',', '.') . "\n";

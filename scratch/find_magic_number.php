<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');

// Try combinations to find 63.871.500 or 63.810.500
$target1 = 63871500;
$target2 = 63810500;

// Subtotals?
$sub_paid = $pdo->query("SELECT SUM(subtotal) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
echo "Subtotal Paid: $sub_paid\n";
$sub_all = $pdo->query("SELECT SUM(subtotal) FROM orders")->fetchColumn();
echo "Subtotal All: $sub_all\n";
$sub_paid_unpaid = $pdo->query("SELECT SUM(subtotal) FROM orders WHERE payment_status IN ('paid', 'unpaid')")->fetchColumn();
echo "Subtotal Paid+Unpaid: $sub_paid_unpaid\n";

// What if we exclude Grab/Gojek/ShopeeFood?
$channels = $pdo->query("SELECT order_source, SUM(total) FROM orders WHERE payment_status = 'paid' GROUP BY order_source")->fetchAll(PDO::FETCH_KEY_PAIR);
print_r($channels);

// What if the user is looking at TOTAL_REVENUE (Grand Total) in Kios Lumero BEFORE I fixed the cancelled orders?
// Wait, Kios Lumero before I fixed it was 68.390.500.

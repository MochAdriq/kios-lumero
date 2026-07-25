<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');
$res = $pdo->query("SELECT payment_status, SUM(total) as t, COUNT(*) as c FROM orders GROUP BY payment_status")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

$res2 = $pdo->query("SELECT * FROM orders WHERE total = 1260000")->fetchAll(PDO::FETCH_ASSOC);
if($res2) {
    echo "Found order with exactly 1.260.000!\n";
}

$res3 = $pdo->query("SELECT DATE(created_at) as d, SUM(total) as t FROM orders WHERE payment_status = 'cancelled' GROUP BY d")->fetchAll(PDO::FETCH_ASSOC);
print_r($res3);

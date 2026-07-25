<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=kios_lumero;charset=utf8mb4', 'root', '');
$emptyOrders = $pdo->query('SELECT COUNT(*), SUM(grand_total) FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE oi.id IS NULL AND o.outlet_id = 8')->fetchAll(PDO::FETCH_ASSOC);
print_r($emptyOrders);

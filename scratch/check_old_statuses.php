<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');
$res = $pdo->query('SELECT order_status, payment_status, COUNT(*), SUM(total) FROM orders GROUP BY order_status, payment_status')->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

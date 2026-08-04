<?php
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/core/Database.php';
$pdo = Database::connection();
$stmt = $pdo->query("SELECT id, order_number, created_at FROM orders ORDER BY id DESC LIMIT 5");
$orders = $stmt->fetchAll();
foreach ($orders as $o) {
    echo "ID: {$o['id']}, No: {$o['order_number']}, Created At: {$o['created_at']}\n";
}

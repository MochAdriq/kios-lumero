<?php
// No require
$pdo = new PDO('mysql:host=localhost;dbname=kios_lumero', 'root', '');
$stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number LIKE ?");
$stmt->execute(['%DCK2014%']);
$orderId = $stmt->fetchColumn();

if ($orderId) {
    $stmt2 = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt2->execute([$orderId]);
    $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    print_r($items);
} else {
    echo "Order not found\n";
}

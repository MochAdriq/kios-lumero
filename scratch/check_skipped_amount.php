<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_temp_migration;charset=utf8mb4', 'root', '');
// I saved the unmapped item IDs somewhere? Or just sum the total of the whole old DB and find the difference.
$paidTotal = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$allTotal = $pdo->query("SELECT SUM(total) FROM orders")->fetchColumn();
$unpaidTotal = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'unpaid'")->fetchColumn();
$cancelledTotal = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'cancelled'")->fetchColumn();

echo "Old DB Paid: $paidTotal\n";
echo "Old DB Unpaid: $unpaidTotal\n";
echo "Old DB Cancelled: $cancelledTotal\n";
echo "Old DB Total: $allTotal\n";

// Is there a 'completed' status?
$completedTotal = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'completed'")->fetchColumn();
echo "Old DB Completed: $completedTotal\n";

$otherTotal = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status NOT IN ('paid', 'unpaid', 'cancelled')")->fetchColumn();
echo "Old DB Other: $otherTotal\n";

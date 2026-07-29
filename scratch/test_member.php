<?php
require __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
$m = $pdo->query("SELECT * FROM members WHERE phone = '0895338977816'")->fetch(PDO::FETCH_ASSOC);
print_r($m);

echo "\n--- MEMBER ITEMS ---\n";
$items = $pdo->query("SELECT * FROM member_items WHERE member_id = " . ($m['id'] ?? 0))->fetchAll(PDO::FETCH_ASSOC);
print_r($items);

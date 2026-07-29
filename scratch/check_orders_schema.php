<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
$stmt = $pdo->query("SHOW COLUMNS FROM orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

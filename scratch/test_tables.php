<?php
require __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
$q = $pdo->query('SHOW TABLES');
print_r($q->fetchAll(PDO::FETCH_COLUMN));

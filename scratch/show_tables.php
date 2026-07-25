<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dcelup_test_dump;charset=utf8mb4', 'root', '');
print_r($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));

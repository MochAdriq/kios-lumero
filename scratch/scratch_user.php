<?php
require 'helpers/functions.php';
require 'core/Database.php';
$pdo = Database::connection();
print_r($pdo->query("SELECT id, username, role_id FROM users")->fetchAll(PDO::FETCH_ASSOC));
print_r($pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC));

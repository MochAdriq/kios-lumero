<?php
require 'core/Database.php';
require 'helpers/functions.php';
$pdo = Database::connection();
print_r($pdo->query("DESCRIBE payment_gateway_configs")->fetchAll(PDO::FETCH_ASSOC));

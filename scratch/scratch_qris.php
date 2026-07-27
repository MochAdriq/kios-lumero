<?php
require 'helpers/functions.php';
require 'core/Database.php';
$pdo = Database::connection();
print_r($pdo->query("SELECT outlet_id, setting_value FROM system_settings WHERE setting_key = 'payment_qris_image'")->fetchAll(PDO::FETCH_ASSOC));

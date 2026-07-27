<?php
require 'helpers/functions.php';
require 'core/Database.php';
$pdo = Database::connection();
$pdo->query("UPDATE system_settings SET setting_value = '' WHERE setting_key = 'payment_qris_image' AND setting_value LIKE '%qris-dana.jpeg%'");
echo "DB Updated\n";

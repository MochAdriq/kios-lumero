<?php
require 'helpers/functions.php';
require 'core/Database.php';
$pdo = Database::connection();
$pdo->query("UPDATE system_settings SET setting_value = 'public/assets/images/pos-products/payment/qris-dana.jpeg' WHERE setting_key = 'payment_qris_image' AND setting_value = 'assets/img/payment/qris-20260512-212418.jpg'");
echo "Fixed DB path.";

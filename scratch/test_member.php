<?php
require 'config/database.php';
require 'config/loyalty.php';
$pdo = Database::connection();
$m = loyalty_find_member_by_phone($pdo, '0895338977816');
print_r($m);

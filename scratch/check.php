<?php
require 'helpers/functions.php';
require 'core/Database.php';
require 'config/loyalty.php';
$pdo = Database::connection();
print_r(loyalty_check_claim_code($pdo, 'LMR-DEF63612'));

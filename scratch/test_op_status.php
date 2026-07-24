<?php
require __DIR__ . '/../helpers/functions.php';
require __DIR__ . '/../core/Database.php';
$st = check_outlet_operating_status(5);
print_r($st);

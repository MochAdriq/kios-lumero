<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Auth.php';
$_SESSION = [
    'outlet_id' => 8, 
    'user' => ['id' => 1]
];
echo "Auth ID: " . var_export(Auth::id(), true) . "\n";

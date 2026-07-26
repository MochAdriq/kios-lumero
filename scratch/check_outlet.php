<?php
session_start();
$_SESSION['user'] = ['id' => 1];
$_SESSION['outlet_id'] = 8;
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Auth.php';

if (function_exists('current_outlet_id')) {
    echo "current_outlet_id: " . current_outlet_id() . "\n";
} else {
    $user = Auth::user() ?? [];
    echo "user outlet: " . ((int)($user['outlet_id'] ?? 1) ?: 1) . "\n";
}

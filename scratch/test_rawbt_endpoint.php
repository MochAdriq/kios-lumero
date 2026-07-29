<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate $_GET
$_GET['id'] = 45; // Just some random order ID

try {
    ob_start();
    require __DIR__ . '/../api/print/rawbt.php';
    $output = ob_get_clean();
    echo "OUTPUT:\n" . $output;
} catch (Throwable $e) {
    echo "ERROR CAUGHT: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}

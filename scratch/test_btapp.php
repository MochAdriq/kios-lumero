<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
$stmt = $pdo->query('SELECT id FROM orders ORDER BY id DESC LIMIT 1');
$id = $stmt->fetchColumn();
echo "Latest order id: $id\n";
$_GET['id'] = $id;

// test btapp json output
error_reporting(0); // hide warnings
ob_start();
include __DIR__ . '/../api/print/btapp-json.php';
$output = ob_get_clean();

// strip warnings if any
$jsonStart = strpos($output, '{');
$json = substr($output, $jsonStart);

echo "JSON Output length: " . strlen($json) . "\n";
// echo $json; // don't echo full json, it's too big

$decoded = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "\nJSON Error: " . json_last_error_msg();
} else {
    echo "\nJSON is Valid!";
    foreach ($decoded as $idx => $item) {
        if ($item['type'] == 3) {
            echo "\nItem $idx (Image): size = " . strlen($item['content']);
        }
    }
}

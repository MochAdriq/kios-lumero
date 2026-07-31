<?php
/**
 * Cash Drawer Open — No Print
 * Sends the ESC/POS cash drawer pulse command via Bluetooth Print App (Thermer).
 * Called when "Tanpa Print Struk" is active and payment is cash.
 */
require_once __DIR__ . '/../../helpers/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
    $paymentMethod = trim(strtolower($_GET['payment_method'] ?? 'cash'));

    // Only open drawer for cash payment
    if ($paymentMethod !== 'cash') {
        echo json_encode(['success' => true, 'skipped' => true, 'message' => 'Bukan cash, laci tidak perlu dibuka.']);
        exit;
    }

    // Build a single cash drawer pulse command for Bluetooth Print App (Thermer)
    $drawerCmd = new stdClass();
    $drawerCmd->type = 0;
    $drawerCmd->content = "\x1B\x70\x00\x19\x64"; // ESC p 0 25ms 100ms
    $drawerCmd->bold = 0;
    $drawerCmd->align = 0;
    $drawerCmd->format = 0;

    $commands = [$drawerCmd];

    // Build Thermer scheme URL
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || (($_SERVER['SERVER_PORT'] ?? '') == 443) 
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $jsonUrl = $scheme . '://' . $host . '/api/print/open-drawer.php?order_id=' . $orderId . '&_json=1&t=' . time();

    if (isset($_GET['_json'])) {
        // Return the raw Thermer JSON commands array
        echo json_encode($commands);
        exit;
    }

    $btappUrl = 'my.bluetoothprint.scheme://' . $jsonUrl;

    echo json_encode([
        'success'   => true,
        'btapp_url' => $btappUrl,
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

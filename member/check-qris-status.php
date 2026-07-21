<?php
/**
 * check-qris-status.php
 * Endpoint AJAX polling status pembayaran QRIS Midtrans.
 * Dipanggil frontend setiap 3 detik setelah QR code ditampilkan.
 *
 * Query Param: order_id = Midtrans order ID (format: PRE_ORDER_NO-timestamp)
 * Response JSON: { paid: true } / { paid: false } / { error: "..." }
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if (empty($_SESSION['member_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/MidtransService.php';

$orderId = trim((string)($_GET['order_id'] ?? ''));
if ($orderId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'order_id diperlukan']);
    exit;
}

try {
    $serverKey = MidtransService::getServerKey();
    if ($serverKey === '') {
        throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
    }

    $statusUrl = MidtransService::isProduction()
        ? 'https://api.midtrans.com/v2/' . urlencode($orderId) . '/status'
        : 'https://api.sandbox.midtrans.com/v2/' . urlencode($orderId) . '/status';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $statusUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($serverKey . ':'),
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new RuntimeException('Koneksi Midtrans gagal: ' . $curlErr);
    }

    $data = json_decode((string)$response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Respons Midtrans tidak valid.');
    }

    $transactionStatus = (string)($data['transaction_status'] ?? '');
    $fraudStatus       = (string)($data['fraud_status']       ?? 'accept');

    $paid = MidtransService::mapStatus($transactionStatus, $fraudStatus) === 'paid';

    echo json_encode(['paid' => $paid], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[check-qris-status] ' . $e->getMessage());
    echo json_encode(['paid' => false, 'error' => 'Gagal cek status']);
}

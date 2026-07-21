<?php
/**
 * SIM Resto Sempurna - Midtrans Controller
 * Menangani pembuatan Snap Token dan Webhook Notification dari Midtrans
 */
class MidtransController extends Controller
{
    /**
     * Endpoint API Webhook Notification yang dipanggil oleh server Midtrans
     * Route: POST /api/midtrans/notification
     */
    public function notification(): void
    {
        header('Content-Type: application/json');

        $rawInput = file_get_contents('php://input');
        $payload = json_decode((string)$rawInput, true);

        if (!is_array($payload) || empty($payload['order_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
            return;
        }

        // Verifikasi Signature SHA512
        if (!MidtransService::verifySignature($payload)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
            return;
        }

        $orderIdMidtrans = (string)$payload['order_id'];
        $parts = explode('-', $orderIdMidtrans);
        if (count($parts) >= 3 && in_array($parts[0], ['FO', 'ORD'])) {
            $orderNumber = "{$parts[0]}-{$parts[1]}-{$parts[2]}";
        } else {
            $orderNumber = $parts[0] ?? '';
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? 'accept';
        $paymentType = $payload['payment_type'] ?? '';

        $newStatus = MidtransService::mapStatus($transactionStatus, $fraudStatus);

        $db = Database::connection();

        // Pisahkan logika antara Online Orders (FO-) dan POS Orders (ORD-)
        if (str_starts_with($orderNumber, 'FO-')) {
            $stmtOrder = $db->prepare("SELECT id FROM free_orders WHERE pre_order_no=? LIMIT 1");
            $stmtOrder->execute([$orderNumber]);
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Online Order not found']);
                return;
            }

            $paymentStatus = ($newStatus === 'paid') ? 'paid' : 'unpaid';
            $stmtUpdate = $db->prepare("UPDATE free_orders SET payment_status=?, payment_method='qris' WHERE pre_order_no=?");
            $stmtUpdate->execute([$paymentStatus, $orderNumber]);

        } else {
            // Cari pesanan berdasarkan order_number di tabel orders
            $stmtOrder = $db->prepare("SELECT id, grand_total FROM orders WHERE order_number=? LIMIT 1");
            $stmtOrder->execute([$orderNumber]);
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Order not found']);
                return;
            }

            $orderId = (int)$order['id'];

            // Update atau catat ke tabel payments
            $stmtUpdatePay = $db->prepare("UPDATE payments 
                SET status=?, provider='midtrans', gateway_reference=?, gateway_payload=?, updated_at=?
                WHERE order_id=?");
            $stmtUpdatePay->execute([
                $newStatus,
                $orderIdMidtrans,
                json_encode($payload),
                now(),
                $orderId
            ]);

            // Jika status paid, update status pesanan di orders menjadi paid
            if ($newStatus === 'paid') {
                $stmtUpdateOrder = $db->prepare("UPDATE orders SET payment_status='paid', updated_at=? WHERE id=?");
                $stmtUpdateOrder->execute([now(), $orderId]);
            } elseif ($newStatus === 'failed') {
                $stmtUpdateOrder = $db->prepare("UPDATE orders SET payment_status='unpaid', updated_at=? WHERE id=?");
                $stmtUpdateOrder->execute([now(), $orderId]);
            }
        }

        echo json_encode(['status' => 'ok']);
    }

    /**
     * Endpoint API untuk menghasilkan Snap Token dari kasir POS
     * Route: POST /api/midtrans/token
     */
    public function createToken(): void
    {
        header('Content-Type: application/json');
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        verify_csrf();

        try {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $db = Database::connection();
            $stmt = $db->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new RuntimeException('Order tidak ditemukan');
            }

            $snapResult = MidtransService::createSnapToken([
                'order_number' => $order['order_number'],
                'grand_total' => $order['grand_total'],
                'customer_name' => 'Customer ' . $order['order_number'],
            ]);

            echo json_encode([
                'success' => true,
                'token' => $snapResult['token'],
                'redirect_url' => $snapResult['redirect_url'],
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

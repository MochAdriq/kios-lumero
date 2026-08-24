<?php
class POSController extends Controller
{
    private POSModel $model;
    public function __construct() { $this->model = new POSModel(); }

    public function index(): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        $outletId = $this->model->outletId();
        $this->view('pos/index', [
            'pageTitle' => 'POS Kasir',
            'categories' => $this->model->categoriesWithProducts($outletId),
            'session' => $this->model->activeSession($outletId),
        ], 'pos');
    }

    public function checkout(): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        verify_csrf();
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');

        try {
            $cart = json_decode($_POST['cart'] ?? '[]', true);
            if (!is_array($cart)) $cart = [];
            $paymentMethod = $_POST['payment_method'] ?? 'cash';

            // Cek mode QRIS outlet: 'manual' atau 'midtrans'
            // Hanya gunakan Midtrans jika setting outlet memilih 'midtrans'
            $qrisMode  = function_exists('get_setting')
                ? get_setting('qris_payment_method', 'manual')
                : 'manual';
            $isMidtransEnabled = class_exists('MidtransService')
                && MidtransService::getServerKey() !== ''
                && $qrisMode === 'midtrans';

            $result = $this->model->createOrder([
                'items' => $cart,
                'payment_method' => $paymentMethod,
                'order_source' => $_POST['order_source'] ?? 'cashier',
                'order_type' => $_POST['order_type'] ?? 'takeaway',
                'paid_amount' => $_POST['paid_amount'] ?? 0,
                'discount_amount' => $_POST['discount_amount'] ?? 0,
                'notes' => $_POST['notes'] ?? null,
                'customer_name' => !empty($_POST['customer_name']) ? trim((string)$_POST['customer_name']) : null,
                'is_change_owed' => !empty($_POST['is_change_owed']) ? 1 : 0,
                'member_id' => !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null,
                'customer_phone' => !empty($_POST['customer_phone']) ? trim((string)$_POST['customer_phone']) : null,
                'skip_print_receipt' => !empty($_POST['skip_print_receipt']) ? 1 : 0,
                'is_midtrans' => $isMidtransEnabled,
            ]);

            $_SESSION['flash_success'] = 'Transaksi berhasil: '.$result['order_number'].' total '.rupiah($result['grand_total']);

            if ($isAjax) {
                $snapToken = null;
                $qrisUrl = null;
                $qrisString = null;
                if ($paymentMethod !== 'cash' && $isMidtransEnabled) {
                    try {
                        if (in_array($paymentMethod, ['qris', 'ewallet'], true)) {
                            $qrisRes = MidtransService::createQrisCharge([
                                'order_number' => $result['order_number'],
                                'grand_total' => $result['grand_total'],
                                'customer_name' => 'Customer ' . $result['order_number'],
                            ]);
                            $qrisUrl = $qrisRes['qr_url'] ?? null;
                            $qrisString = $qrisRes['qr_string'] ?? null;
                            if ($qrisString && isset($result['id'])) {
                                $db = Database::connection();
                                $up = $db->prepare("UPDATE payments SET gateway_reference = ?, gateway_payload = ? WHERE order_id = ?");
                                $up->execute([$qrisString, json_encode($qrisRes), $result['id']]);
                            }
                        } else {
                            $snapRes = MidtransService::createSnapToken([
                                'order_number' => $result['order_number'],
                                'grand_total' => $result['grand_total'],
                                'customer_name' => 'Customer ' . $result['order_number'],
                                'payment_method' => $paymentMethod,
                                'callbacks' => [
                                    'finish' => url('/pos')
                                ]
                            ]);
                            $snapToken = $snapRes['token'] ?? null;
                        }
                    } catch (Throwable $midtransErr) {
                        error_log("Midtrans Payment Error: " . $midtransErr->getMessage());
                    }
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'order_id' => $result['id'],
                    'order_number' => $result['order_number'],
                    'grand_total' => $result['grand_total'],
                    'snap_token' => $snapToken,
                    'qris_url' => $qrisUrl,
                    'qris_string' => $qrisString,
                    'receipt_url' => url('/pos/receipt/'.$result['id']),
                ]);
                return;
            }

            $this->redirect('/pos/receipt/'.$result['id']);
        } catch (Throwable $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                return;
            }

            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/pos');
        }
    }

    public function receipt($id): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        $outletId = Auth::role() === 'super_admin' ? null : (function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id')));
        $data = $this->model->receipt((int)$id, $outletId);
        $layout = (isset($_GET['embed']) || isset($_GET['print'])) ? null : 'app';
        $this->view('pos/receipt', ['pageTitle'=>'Struk '.$data['order']['order_number'], 'receipt'=>$data], $layout);
    }

    public function orders(): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        $orders = $this->model->allOrders($this->model->outletId());
        
        $totalRevenue = 0;
        $totalProfit = 0;
        $validOrders = 0;
        foreach ($orders as $o) {
            $isVoided = in_array($o['order_status'], ['cancelled', 'voided', 'draft'], true)
                     || ($o['payment_status'] ?? '') === 'refunded';
            if (!$isVoided) {
                $validOrders++;
                if (($o['payment_status'] ?? '') === 'paid') {
                    $totalRevenue += (float)$o['grand_total'];
                    $totalProfit += (float)$o['gross_profit'];
                }
            }
        }
        
        $this->view('orders/index', [
            'pageTitle'=>'Order & Transaksi',
            'orders' => $orders,
            'summary' => [
                'total_orders' => $validOrders,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalProfit
            ]
        ]);
    }

    public function updateOrderStatus(): void
    {
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        verify_csrf();
        $orderId = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if ($orderId > 0 && in_array($status, ['preparing', 'ready', 'completed'], true)) {
            $pdo = Database::connection();
            $st = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
            $st->execute([$status, $orderId]);
            
            $orderSt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
            $orderSt->execute([$orderId]);
            $orderNo = $orderSt->fetchColumn();
            
            if ($orderNo) {
                $st2 = $pdo->prepare("UPDATE free_orders SET order_status = ? WHERE pre_order_no = ?");
                $st2->execute([$status, $orderNo]);
            }
            
            $_SESSION['flash_success'] = 'Status pesanan berhasil diupdate menjadi ' . strtoupper($status) . '.';
        } else {
            $_SESSION['flash_error'] = 'Gagal mengupdate status pesanan.';
        }
        $this->redirect('/orders');
    }

    public function bulkUpdateOrderStatus(): void
    {
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        verify_csrf();
        
        $actionType = trim($_POST['action_type'] ?? '');
        $orderIdsRaw = trim($_POST['order_ids'] ?? '');
        $orderIds = array_filter(array_map('intval', explode(',', $orderIdsRaw)));
        
        if (empty($orderIds) || !in_array($actionType, ['preparing', 'ready', 'completed'], true)) {
            $_SESSION['flash_error'] = 'Aksi massal tidak valid atau tidak ada pesanan yang dipilih.';
            $this->redirect('/orders');
            return;
        }

        $pdo = Database::connection();
        $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
        
        try {
            // Update POS Orders — EXCLUDE voided, cancelled, or refunded orders
            $st = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() 
                WHERE id IN ($placeholders)
                  AND order_status NOT IN ('voided', 'cancelled')
                  AND payment_status != 'refunded'");
            $params = array_merge([$actionType], $orderIds);
            $st->execute($params);
            
            // Get order numbers to update free_orders
            $stNum = $pdo->prepare("SELECT order_number FROM orders WHERE id IN ($placeholders)");
            $stNum->execute($orderIds);
            $orderNumbers = $stNum->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($orderNumbers)) {
                $placeholdersFo = str_repeat('?,', count($orderNumbers) - 1) . '?';
                $stFo = $pdo->prepare("UPDATE free_orders SET order_status = ? WHERE pre_order_no IN ($placeholdersFo)");
                $paramsFo = array_merge([$actionType], $orderNumbers);
                $stFo->execute($paramsFo);
            }
            
            $_SESSION['flash_success'] = count($orderIds) . ' pesanan berhasil ditandai sebagai ' . strtoupper($actionType) . '.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal melakukan aksi massal: ' . $e->getMessage();
        }
        
        $this->redirect('/orders');
    }

    public function updatePaymentStatus(): void
    {
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        verify_csrf();
        $orderId  = (int)($_POST['id'] ?? 0);
        $status   = trim($_POST['status'] ?? '');
        $context  = trim($_POST['context'] ?? 'change'); // 'change' atau 'qris_manual'

        if ($orderId > 0 && in_array($status, ['paid'], true)) {
            $pdo = Database::connection();

            // Update tabel orders
            $pdo->prepare("UPDATE orders SET payment_status = 'paid', change_owed_amount = 0, updated_at = NOW() WHERE id = ?")
                ->execute([$orderId]);

            // Update tabel payments juga (untuk konsistensi laporan)
            $pdo->prepare("UPDATE payments SET status = 'paid', verified_by = ?, verified_at = NOW(), paid_at = COALESCE(paid_at, NOW()), updated_at = NOW() WHERE order_id = ? AND status != 'paid'")
                ->execute([Auth::id(), $orderId]);

            if ($context === 'qris_manual') {
                $_SESSION['flash_success'] = 'Pembayaran QRIS berhasil dikonfirmasi.';
            } else {
                $_SESSION['flash_success'] = 'Hutang kembalian pesanan berhasil dilunasi.';
            }
        } else {
            $_SESSION['flash_error'] = 'Gagal mengkonfirmasi pembayaran.';
        }
        $this->redirect('/orders');
    }

    public function bulkConfirmQris(): void
    {
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        verify_csrf();

        $orderIdsRaw = trim($_POST['order_ids'] ?? '');
        $orderIds = array_filter(array_map('intval', explode(',', $orderIdsRaw)));

        if (empty($orderIds)) {
            $_SESSION['flash_error'] = 'Tidak ada pesanan yang dipilih.';
            $this->redirect('/orders');
            return;
        }

        $pdo = Database::connection();
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        // Ambil hanya order yang benar-benar QRIS + unpaid — server-side safety check
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id IN ($placeholders) AND payment_status = 'unpaid'");
        $stmt->execute($orderIds);
        $validIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($validIds)) {
            $_SESSION['flash_error'] = 'Tidak ada order QRIS yang belum bayar pada pilihan ini.';
            $this->redirect('/orders');
            return;
        }

        $ph2 = implode(',', array_fill(0, count($validIds), '?'));

        // Update orders
        $pdo->prepare("UPDATE orders SET payment_status = 'paid', change_owed_amount = 0, updated_at = NOW() WHERE id IN ($ph2)")
            ->execute($validIds);

        // Update payments
        $verifiedAt = now();
        $verifiedBy = Auth::id();
        foreach ($validIds as $oid) {
            $pdo->prepare("UPDATE payments SET status = 'paid', verified_by = ?, verified_at = ?, paid_at = COALESCE(paid_at, ?), updated_at = ? WHERE order_id = ? AND status != 'paid'")
                ->execute([$verifiedBy, $verifiedAt, $verifiedAt, $verifiedAt, $oid]);
        }

        $_SESSION['flash_success'] = count($validIds) . ' pembayaran QRIS berhasil dikonfirmasi.';
        $this->redirect('/orders');
    }

    public function orderDetails(): void
    {
        header('Content-Type: application/json');
        try {
            Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
            $orderId = (int)($_GET['id'] ?? 0);
            if ($orderId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
                return;
            }
            $data = $this->model->receipt($orderId, $this->model->outletId());
            if (!$data || empty($data['order'])) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                return;
            }
            $json = json_encode(['success' => true, 'order' => $data['order'], 'items' => $data['items']]);
            if ($json === false) {
                echo json_encode(['success' => false, 'message' => 'JSON Error: ' . json_last_error_msg()]);
                return;
            }
            echo $json;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function updateItemFulfillment(): void
    {
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');
        
        $orderId = (int)($_POST['order_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $fulfilledQty = (float)($_POST['fulfilled_qty'] ?? 0);

        if ($orderId <= 0 || $itemId <= 0) {
            if ($isAjax) { echo json_encode(['success' => false, 'message' => 'Invalid IDs']); return; }
            $this->redirect('/orders');
            return;
        }

        $pdo = Database::connection();
        $st = $pdo->prepare("UPDATE order_items SET fulfilled_qty = ? WHERE id = ? AND order_id = ?");
        $st->execute([$fulfilledQty, $itemId, $orderId]);

        // Auto-status check: Are all items fulfilled?
        $stCheck = $pdo->prepare("SELECT SUM(qty) AS total_qty, SUM(fulfilled_qty) AS total_fulfilled FROM order_items WHERE order_id = ?");
        $stCheck->execute([$orderId]);
        $totals = $stCheck->fetch(PDO::FETCH_ASSOC);

        $autoReady = false;
        if ($totals && (float)$totals['total_fulfilled'] >= (float)$totals['total_qty']) {
            $pdo->prepare("UPDATE orders SET order_status = 'ready', updated_at = NOW() WHERE id = ? AND order_status IN ('pending', 'preparing')")->execute([$orderId]);
            $autoReady = true;
        } else {
            $pdo->prepare("UPDATE orders SET order_status = 'preparing', updated_at = NOW() WHERE id = ? AND order_status = 'pending'")->execute([$orderId]);
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'auto_ready' => $autoReady]);
        } else {
            $this->redirect('/orders');
        }
    }

    public function payments(): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        $this->view('payments/index', [
            'pageTitle'=>'Verifikasi Payment',
            'payments'=>$this->model->waitingPayments($this->model->outletId())
        ]);
    }

    public function verifyPayment(): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        verify_csrf();
        try {
            $outletId = Auth::role() === 'super_admin' ? null : (function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id')));
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $freeOrderId = (int)($_POST['free_order_id'] ?? 0);
            if ($freeOrderId > 0) {
                $this->model->verifyFreeOrderPayment($freeOrderId, $outletId);
            } else {
                $this->model->verifyPayment($paymentId, $outletId);
            }
            $_SESSION['flash_success'] = 'Payment berhasil diverifikasi.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/payments');
    }

    public function checkMember(): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        $phone = trim((string)($_GET['phone'] ?? ''));
        if ($phone === '') {
            $this->json(['success'=>false, 'message'=>'Nomor HP wajib diisi']);
        }
        $member = $this->model->findMemberByPhone($phone);
        
        if (!$member) {
            try {
                $pdo = Database::connection();
                $pinHash = password_hash('1234', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO members (name, phone, pin_hash, status, created_at) VALUES ('Member Baru', ?, ?, 'active', NOW())");
                if ($stmt->execute([$phone, $pinHash])) {
                    $newId = (int)$pdo->lastInsertId();
                    $member = [
                        'id' => $newId,
                        'name' => 'Member Baru',
                        'phone' => $phone,
                        'total_points' => 0
                    ];
                }
            } catch (Throwable $e) {
                // Biarkan $member tetap null jika terjadi error
            }
        }

        if ($member) {
            $this->json(['success'=>true, 'found'=>true, 'member'=>[
                'id' => (int)$member['id'],
                'name' => (string)($member['name'] ?? 'Member Baru'),
                'phone' => (string)($member['phone'] ?? $phone),
                'points' => (int)($member['total_points'] ?? ($member['points'] ?? ($member['loyalty_points'] ?? 0)))
            ]]);
        } else {
            $this->json(['success'=>true, 'found'=>false, 'message'=>'Gagal otomatis membuat akun member']);
        }
    }
}

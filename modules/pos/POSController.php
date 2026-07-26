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

            $result = $this->model->createOrder([
                'items' => $cart,
                'payment_method' => $paymentMethod,
                'order_source' => $_POST['order_source'] ?? 'cashier',
                'order_type' => $_POST['order_type'] ?? 'takeaway',
                'paid_amount' => $_POST['paid_amount'] ?? 0,
                'discount_amount' => $_POST['discount_amount'] ?? 0,
                'notes' => $_POST['notes'] ?? null,
                'member_id' => !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null,
                'customer_phone' => !empty($_POST['customer_phone']) ? trim((string)$_POST['customer_phone']) : null,
            ]);

            $_SESSION['flash_success'] = 'Transaksi berhasil: '.$result['order_number'].' total '.rupiah($result['grand_total']);

            if ($isAjax) {
                $snapToken = null;
                $qrisUrl = null;
                $qrisString = null;
                $isMidtransEnabled = function_exists('get_setting') && get_setting('qris_payment_method', 'manual') === 'midtrans';
                if ($paymentMethod !== 'cash' && $isMidtransEnabled && class_exists('MidtransService') && MidtransService::getServerKey() !== '') {
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
            if (!in_array($o['order_status'], ['cancelled', 'void', 'draft'], true)) {
                $totalRevenue += (float)$o['grand_total'];
                $totalProfit += (float)$o['gross_profit'];
                $validOrders++;
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
        if ($orderId > 0 && in_array($status, ['completed'], true)) {
            $pdo = Database::connection();
            $st = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
            $st->execute([$status, $orderId]);
            
            $st2 = $pdo->prepare("
                UPDATE free_orders fo
                JOIN orders o ON fo.pre_order_no = o.order_number
                SET fo.order_status = ?
                WHERE o.id = ?
            ");
            $st2->execute([$status, $orderId]);
            
            $_SESSION['flash_success'] = 'Status pesanan berhasil diupdate menjadi ' . strtoupper($status) . '.';
        } else {
            $_SESSION['flash_error'] = 'Gagal mengupdate status pesanan.';
        }
        $this->redirect('/orders');
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
        if ($member) {
            $this->json(['success'=>true, 'found'=>true, 'member'=>[
                'id' => (int)$member['id'],
                'name' => (string)($member['name'] ?? 'Member'),
                'phone' => (string)($member['phone'] ?? $phone),
                'points' => (int)($member['total_points'] ?? ($member['points'] ?? ($member['loyalty_points'] ?? 0)))
            ]]);
        } else {
            $this->json(['success'=>true, 'found'=>false, 'message'=>'Member tidak ditemukan']);
        }
    }
}

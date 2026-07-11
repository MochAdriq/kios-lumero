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
            ]);

            $_SESSION['flash_success'] = 'Transaksi berhasil: '.$result['order_number'].' total '.rupiah($result['grand_total']);

            if ($isAjax) {
                $snapToken = null;
                $qrisUrl = null;
                $qrisString = null;
                if ($paymentMethod !== 'cash' && class_exists('MidtransService') && MidtransService::getServerKey() !== '') {
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
        if (!$data) { http_response_code(404); include __DIR__ . '/../../views/errors/404.php'; return; }
        $this->view('pos/receipt', ['pageTitle'=>'Struk '.$data['order']['order_number'], 'receipt'=>$data]);
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
            $this->model->verifyPayment((int)($_POST['payment_id'] ?? 0), $outletId);
            $_SESSION['flash_success'] = 'Payment berhasil diverifikasi.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/payments');
    }
}

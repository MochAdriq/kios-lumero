<?php
require_once __DIR__ . '/../../helpers/delivery_helper.php';
require_once __DIR__ . '/../../helpers/free_order_helper.php';

class DeliveryController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        $pdo = Database::connection();
        fo_ensure_tables($pdo);
        delivery_ensure_columns($pdo);

        // Filter by status or search if provided
        $statusFilter = trim((string)($_GET['status'] ?? ''));
        $searchQuery = trim((string)($_GET['q'] ?? ''));

        $sql = "SELECT * FROM free_orders WHERE pickup_type = 'delivery'";
        $params = [];

        if ($statusFilter !== '') {
            $sql .= " AND delivery_status = ?";
            $params[] = $statusFilter;
        }
        if ($searchQuery !== '') {
            $sql .= " AND (pre_order_no LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR delivery_address LIKE ?)";
            $like = '%' . $searchQuery . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY id DESC LIMIT 150";
        $orders = fo_all($pdo, $sql, $params);

        // Fetch items for each order to show details
        $itemsMap = [];
        if (!empty($orders)) {
            $ids = array_map(fn($o) => (int)$o['id'], $orders);
            $inClause = implode(',', $ids);
            $allItems = fo_all($pdo, "SELECT * FROM free_order_items WHERE free_order_id IN ($inClause) ORDER BY id ASC");
            foreach ($allItems as $it) {
                $itemsMap[$it['free_order_id']][] = $it;
            }
        }

        $settings = delivery_settings($pdo);
        $outletCoords = delivery_outlet_coords($pdo);

        $this->view('delivery/index', [
            'pageTitle' => 'Monitoring & Management Delivery',
            'orders' => $orders,
            'itemsMap' => $itemsMap,
            'settings' => $settings,
            'outletCoords' => $outletCoords,
            'statusFilter' => $statusFilter,
            'searchQuery' => $searchQuery
        ]);
    }

    public function settings(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $pdo = Database::connection();
        delivery_ensure_columns($pdo);

        $settings = delivery_settings($pdo);
        $outletCoords = delivery_outlet_coords($pdo);

        $this->view('delivery/settings', [
            'pageTitle' => 'Konfigurasi & Peta Outlet Delivery',
            'settings' => $settings,
            'outletCoords' => $outletCoords
        ]);
    }

    public function saveSettings(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();
        $pdo = Database::connection();
        delivery_save_settings($pdo, $_POST);
        $_SESSION['flash_success'] = 'Konfigurasi dan titik koordinat Delivery berhasil disimpan.';
        $this->redirect('/delivery/settings');
    }

    public function updateStatus(): void
    {
        Auth::requireRoles(['super_admin', 'administrator', 'cashier']);
        verify_csrf();
        $pdo = Database::connection();

        $orderId = (int)($_POST['order_id'] ?? 0);
        $newDelStatus = trim((string)($_POST['delivery_status'] ?? ''));
        $courierName = trim((string)($_POST['delivery_courier_name'] ?? 'Kurir Internal'));

        if ($orderId <= 0 || !in_array($newDelStatus, ['preparing', 'on_the_way', 'delivered', 'cancelled'], true)) {
            $_SESSION['flash_error'] = 'Status pengantaran tidak valid.';
            $this->redirect('/delivery');
            return;
        }

        // Map delivery_status to order_status where appropriate
        $orderStatus = 'ready';
        if ($newDelStatus === 'on_the_way') {
            $orderStatus = 'on_the_way';
        } elseif ($newDelStatus === 'delivered') {
            $orderStatus = 'completed';
        } elseif ($newDelStatus === 'cancelled') {
            $orderStatus = 'cancelled';
        }

        $st = $pdo->prepare("UPDATE free_orders SET delivery_status = ?, delivery_courier_name = ?, order_status = ? WHERE id = ? AND pickup_type = 'delivery'");
        $st->execute([$newDelStatus, $courierName, $orderStatus, $orderId]);

        $_SESSION['flash_success'] = 'Status pengantaran pesanan berhasil diperbarui menjadi ' . strtoupper(str_replace('_', ' ', $newDelStatus)) . '.';
        $this->redirect('/delivery');
    }
}

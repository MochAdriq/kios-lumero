<?php
class ProductSalesController extends Controller
{
    private ProductSalesModel $model;

    public function __construct()
    {
        $this->model = new ProductSalesModel();
    }

    public function index(): void
    {
        // Default requirement: Only Admin and Super Admin can access this report
        Auth::requireRoles(['super_admin', 'administrator']);

        $startDate = $_GET['start_date'] ?? date('Y-m-d');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        $role = Auth::role();
        $outletId = null;

        if ($role === 'super_admin') {
            if (!empty($_GET['outlet_id'])) {
                $outletId = (int)$_GET['outlet_id'];
            }
        } else {
            // Admin only sees their own outlet
            $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        }

        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;

        $stats = $this->model->getSalesStats($startDate, $endDate, $outletId, $categoryId);

        // Prepare data for Chart.js
        $chartLabels = [];
        $chartData = [];
        $top10 = array_slice($stats, 0, 10);
        foreach ($top10 as $row) {
            $name = $row['product_name'] . ($row['variant_name'] ? ' - ' . $row['variant_name'] : '');
            $chartLabels[] = $name;
            $chartData[] = (int)$row['total_qty'];
        }

        $outlets = [];
        $db = Database::connection();
        if ($role === 'super_admin') {
            $outlets = $db->query("SELECT id, name FROM outlets ORDER BY name ASC")->fetchAll();
        }
        
        // Fetch categories for filter dropdown
        $categories = $db->query("SELECT id, name FROM categories WHERE type = 'product' ORDER BY name ASC")->fetchAll();

        $this->view('product_sales/index', [
            'pageTitle' => 'Penjualan Produk',
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'outlets' => $outlets,
            'selectedOutlet' => $outletId,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
        ]);
    }
}

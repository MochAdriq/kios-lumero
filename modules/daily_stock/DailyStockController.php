<?php
class DailyStockController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $model = new DailyStockModel();
        $bizDate = function_exists('business_date') ? business_date() : today();
        $date = $_GET['date'] ?? $bizDate;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = $bizDate;
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $search = trim($_GET['q'] ?? '');

        $this->view('daily-stock/index', [
            'pageTitle' => 'Stock Harian',
            'businessDate' => $date,
            'categoryId' => $categoryId,
            'search' => $search,
            'categories' => $model->categories(),
            'items' => $model->products($date, $search, $categoryId),
            'summary' => $model->summary($date),
            'movements' => $model->recentMovements($date),
        ]);
    }

    public function save(): void
    {
        Auth::requireRoles(['super_admin','administrator','cashier']);
        verify_csrf();
        $bizDate = function_exists('business_date') ? business_date() : today();
        $date = $_POST['business_date'] ?? $bizDate;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = $bizDate;
        $result = (new DailyStockModel())->saveBulk($date, $_POST);
        Audit::log('update_daily_product_stock','daily_product_stocks',null,null,['date'=>$date,'saved'=>$result['saved']]);
        $_SESSION['flash_success'] = 'Stock harian berhasil disimpan: ' . $result['saved'] . ' item.';
        $qs = http_build_query(['date'=>$date,'category_id'=>$_POST['category_id'] ?? 0,'q'=>$_POST['q'] ?? '']);
        $this->redirect('/daily-stock?' . $qs);
    }
    
    public function ajaxRecipeStock(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        
        $variantId = (int)($_GET['variant_id'] ?? 0);
        $outletId = current_outlet_id();
        
        if (!$variantId) {
            echo json_encode(['error' => 'Variant ID required']);
            return;
        }

        require_once __DIR__ . '/../recipes/RecipeModel.php';
        $rm = new RecipeModel();
        
        $db = Database::connection();
        $recipe = $db->query("SELECT id, name FROM recipes WHERE recipe_type = 'final' AND product_variant_id = $variantId ORDER BY (outlet_id = $outletId) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if (!$recipe) {
            echo json_encode(['error' => 'Resep final tidak ditemukan untuk produk ini.']);
            return;
        }
        
        $bom = $rm->explodeBOM($recipe['id'], 1.0);
        if (!$bom) {
            echo json_encode(['error' => 'BOM kosong atau tidak valid.']);
            return;
        }
        
        $rmIds = array_keys($bom);
        $placeholders = implode(',', array_fill(0, count($rmIds), '?'));
        
        $stmt = $db->prepare("
            SELECT rm.id, rm.name, u.symbol as unit, COALESCE(orm.stock_qty, rm.stock_qty, 0) as available_stock
            FROM raw_materials rm
            LEFT JOIN units u ON rm.unit_id = u.id
            LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ?
            WHERE rm.id IN ($placeholders)
        ");
        $params = array_merge([$outletId], $rmIds);
        $stmt->execute($params);
        $rawMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($rawMaterials as $mat) {
            $required = $bom[$mat['id']] ?? 0;
            $results[] = [
                'name' => $mat['name'],
                'unit' => $mat['unit'] ?: '',
                'required' => (float)$required,
                'available' => (float)$mat['available_stock'],
                'is_bottleneck' => ((float)$mat['available_stock'] < (float)$required)
            ];
        }
        
        echo json_encode([
            'recipe_name' => $recipe['name'],
            'items' => $results
        ]);
    }
}

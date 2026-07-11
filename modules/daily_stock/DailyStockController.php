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
}

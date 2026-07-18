<?php
class CapitalController extends Controller
{
    private function requireAuth(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        if (Auth::role() !== 'super_admin') {
            $_GET['outlet_id'] = (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id', 1));
            $_POST['outlet_id'] = (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id', 1));
        }
    }

    private function post(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
    }

    private function validDate(string $d): bool
    {
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
    }

    public function index(): void
    {
        $this->requireAuth();
        // Capital uses ExecutiveModel as its base since the tables are shared
        require_once __DIR__ . '/../executive/ExecutiveModel.php';
        $m = new ExecutiveModel();

        $today = today();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? $today;
        if (!$this->validDate($from)) $from = date('Y-m-01');
        if (!$this->validDate($to)) $to = $today;
        if (strtotime($from) > strtotime($to)) { [$from, $to] = [$to, $from]; }

        $capitals = $m->capitals();
        $capitalByCat = $m->capitalByCategory();

        $editId = (int)($_GET['edit'] ?? 0);
        $edit = null;
        if ($editId > 0) {
            foreach ($capitals as $c) { if ((int)$c['id'] === $editId) { $edit = $c; break; } }
        }

        $this->view('capital/index', [
            'pageTitle' => 'Manajemen Modal',
            'from' => $from, 'to' => $to, 'today' => $today,
            'capitals' => $capitals,
            'capitalByCat' => $capitalByCat,
            'edit' => $edit,
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        verify_csrf();
        require_once __DIR__ . '/../executive/ExecutiveModel.php';
        $m = new ExecutiveModel();
        $m->saveCapital([
            'id' => $this->post('id'),
            'capital_date' => $this->validDate($this->post('capital_date', today())) ? $this->post('capital_date') : today(),
            'category' => $this->post('category', 'Modal Awal'),
            'component_name' => $this->post('component_name'),
            'description' => $this->post('description'),
            'amount' => (int)preg_replace('/[^0-9]/', '', $this->post('amount', '0')),
            'payment_method' => $this->post('payment_method') ?: null,
            'supplier' => $this->post('supplier') ?: null,
            'invoice_no' => $this->post('invoice_no') ?: null,
            'is_active' => (int)($this->post('is_active', '1')),
        ]);
        $_SESSION['flash_success'] = 'Komponen modal berhasil disimpan.';
        $this->redirect('/capital');
    }

    public function delete(): void
    {
        $this->requireAuth();
        verify_csrf();
        require_once __DIR__ . '/../executive/ExecutiveModel.php';
        $id = (int)($this->post('id', '0'));
        if ($id > 0) { (new ExecutiveModel())->deactivateCapital($id); $_SESSION['flash_success'] = 'Komponen modal dinonaktifkan.'; }
        $this->redirect('/capital');
    }
}

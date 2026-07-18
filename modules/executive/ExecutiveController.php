<?php
class ExecutiveController extends Controller
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
        $m = new ExecutiveModel();

        $today = today();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? $today;
        if (!$this->validDate($from)) $from = date('Y-m-01');
        if (!$this->validDate($to)) $to = $today;
        if (strtotime($from) > strtotime($to)) { [$from, $to] = [$to, $from]; }

        $kpi = $m->computeAll($from, $to);
        $capitals = $m->capitals();
        $capitalByCat = $m->capitalByCategory();
        $topProducts = $m->topProducts($from, $to);
        $menuMatrix = $m->menuProfitMatrix($topProducts);
        $inventoryDecision = $m->inventoryDecision();
        $experiments = $m->experiments();
        $targets = $m->targets();

        // Update expansion checklist with inventory data
        $kpi['expansionChecklist'][5]['ok'] = count($inventoryDecision['urgent']) <= 1;

        // Chart data
        $chartRows = array_slice($kpi['daily'], -21);
        $chartMax = max(1, max(array_map(function($r) {
            return max(0, (int)($r['sales'] ?? 0), (int)($r['hpp'] ?? 0), (int)($r['expenses'] ?? 0), (int)($r['net_profit'] ?? 0));
        }, $chartRows ?: [['sales' => 0, 'net_profit' => 0, 'hpp' => 0, 'expenses' => 0]])));

        // Edit capital
        $editId = (int)($_GET['edit'] ?? 0);
        $edit = null;
        if ($editId > 0) {
            foreach ($capitals as $c) { if ((int)$c['id'] === $editId) { $edit = $c; break; } }
        }

        $this->view('executive/index', [
            'pageTitle' => 'Executive Suite',
            'from' => $from, 'to' => $to, 'today' => $today,
            'kpi' => $kpi,
            'capitals' => $capitals, 'capitalByCat' => $capitalByCat,
            'topProducts' => $topProducts, 'menuMatrix' => $menuMatrix,
            'inventoryDecision' => $inventoryDecision,
            'experiments' => $experiments, 'targets' => $targets,
            'chartRows' => $chartRows, 'chartMax' => $chartMax,
            'edit' => $edit,
            'trendRows' => $kpi['trendRows'],
        ]);
    }



    public function storeTarget(): void
    {
        $this->requireAuth();
        verify_csrf();
        (new ExecutiveModel())->storeTarget($_POST);
        $_SESSION['flash_success'] = 'Target bisnis berhasil disimpan.';
        $this->redirect('/executive');
    }

    public function saveSettings(): void
    {
        $this->requireAuth();
        verify_csrf();
        $m = new ExecutiveModel();
        $start = $this->post('business_start_date', '2026-05-17');
        if (!$this->validDate($start)) $start = '2026-05-17';
        $m->setSetting('business_start_date', $start);
        $m->setSetting('projection_working_days_month', (string)max(1, (int)$this->post('projection_working_days_month', '30')));
        $m->setSetting('daily_sales_target', (string)max(0, (int)preg_replace('/[^0-9]/', '', $this->post('daily_sales_target', '1000000'))));
        $m->setSetting('owner_reserve_percent', (string)max(0, (float)$this->post('owner_reserve_percent', '5')));
        $m->setSetting('roi_payback_percent', (string)max(0, (float)$this->post('roi_payback_percent', '15')));
        $m->setSetting('growth_conservative_pct', (string)(float)$this->post('growth_conservative_pct', '0'));
        $m->setSetting('growth_base_pct', (string)(float)$this->post('growth_base_pct', '8'));
        $m->setSetting('growth_aggressive_pct', (string)(float)$this->post('growth_aggressive_pct', '18'));
        $_SESSION['flash_success'] = 'Pengaturan business navigator berhasil disimpan.';
        $this->redirect('/executive');
    }



    public function printReport(): void
    {
        $this->requireAuth();
        $m = new ExecutiveModel();
        $today = today();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? $today;
        if (!$this->validDate($from)) $from = date('Y-m-01');
        if (!$this->validDate($to)) $to = $today;
        if (strtotime($from) > strtotime($to)) { [$from, $to] = [$to, $from]; }

        $kpi = $m->computeAll($from, $to);
        $capitals = $m->capitals();
        $this->view('executive/print', [
            'pageTitle' => 'Print ROI Report',
            'from' => $from, 'to' => $to, 'today' => $today,
            'kpi' => $kpi, 'capitals' => $capitals,
        ], null);
    }
}

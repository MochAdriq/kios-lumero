<?php
class InnovationController extends Controller
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

    public function index(): void
    {
        $this->requireAuth();
        require_once __DIR__ . '/../executive/ExecutiveModel.php';
        $m = new ExecutiveModel();

        $trendRows = $m->trendRecommendations();
        $experiments = $m->experiments();
        $store = current_outlet_name();

        $this->view('innovation/index', [
            'pageTitle' => 'Inovasi & Eksperimen Menu',
            'trendRows' => $trendRows,
            'experiments' => $experiments,
            'store' => $store,
        ]);
    }

    public function saveTrend(): void
    {
        $this->requireAuth();
        verify_csrf();
        require_once __DIR__ . '/../executive/ExecutiveModel.php';
        (new ExecutiveModel())->saveTrendKeyword($_POST);
        $_SESSION['flash_success'] = 'Keyword trend berhasil disimpan.';
        $this->redirect('/innovation');
    }

    public function saveExperiment(): void
    {
        $this->requireAuth();
        verify_csrf();
        require_once __DIR__ . '/../executive/ExecutiveModel.php';
        $id = (new ExecutiveModel())->saveExperiment($_POST);
        
        $name = urlencode($_POST['experiment_name'] ?? '');
        $hpp = urlencode($_POST['estimated_hpp'] ?? '');
        $price = urlencode($_POST['suggested_price'] ?? '');
        
        $this->redirect("/products/builder?exp_id={$id}&exp_name={$name}&exp_hpp={$hpp}&exp_price={$price}");
    }

    public function updateExperiment(): void
    {
        $this->requireAuth();
        verify_csrf();
        require_once __DIR__ . '/../executive/ExecutiveModel.php';
        $id = (int)($this->post('id', '0'));
        if ($id > 0) {
            (new ExecutiveModel())->updateExperimentStatus($id, $this->post('status', 'running'), $this->post('decision', 'pending'));
            $_SESSION['flash_success'] = 'Status eksperimen berhasil diperbarui.';
        }
        $this->redirect('/innovation');
    }
}

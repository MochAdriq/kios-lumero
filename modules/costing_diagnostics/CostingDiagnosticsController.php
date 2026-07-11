<?php
class CostingDiagnosticsController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $model = new CostingDiagnosticsModel();
        $diagnostics = $model->runDiagnostics();
        $healthScore = $model->healthScore($diagnostics);
        $stats = $model->stats($diagnostics);

        $this->view('costing-diagnostics/index', [
            'pageTitle'    => 'Diagnostik HPP & Resep',
            'diagnostics'  => $diagnostics,
            'healthScore'  => $healthScore,
            'stats'        => $stats,
        ]);
    }
}

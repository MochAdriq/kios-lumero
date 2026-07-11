<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        $m=new DashboardModel(); $this->view('dashboard/index', ['summary'=>$m->summary($outletId),'topItems'=>$m->topItems($outletId),'weekly'=>$m->weeklySales($outletId), 'pageTitle'=>'Dashboard']);
    }
    public function apiSummary(): void
    {
        Auth::requireLogin();
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        $this->json((new DashboardModel())->summary($outletId));
    }
}

<?php
class StoreController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        $m=new StoreModel();
        $this->view('store/index', ['session'=>$m->todaySession($outletId),'staff'=>$m->activeStaff($outletId),'pageTitle'=>'Buka/Tutup Toko','error'=>null]);
    }
    public function open(): void
    {
        Auth::requireRoles(['super_admin','administrator']); verify_csrf();
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        try { (new StoreModel())->open($outletId, Auth::id(), (float)($_POST['opening_cash'] ?? 0), array_map('intval', $_POST['staff_ids'] ?? [])); Audit::log('open_store'); $this->redirect('/store'); }
        catch(Throwable $e){ $m=new StoreModel(); $this->view('store/index',['session'=>$m->todaySession($outletId),'staff'=>$m->activeStaff($outletId),'pageTitle'=>'Buka/Tutup Toko','error'=>$e->getMessage()]); }
    }
    public function close(): void
    {
        Auth::requireRoles(['super_admin','administrator']); verify_csrf();
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        try { (new StoreModel())->close($outletId, Auth::id(), (float)($_POST['physical_cash'] ?? 0), trim($_POST['notes'] ?? '')); $this->redirect('/store'); }
        catch(Throwable $e){ $m=new StoreModel(); $this->view('store/index',['session'=>$m->todaySession($outletId),'staff'=>$m->activeStaff($outletId),'pageTitle'=>'Buka/Tutup Toko','error'=>$e->getMessage()]); }
    }
}

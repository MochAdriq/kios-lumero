<?php
class PurchaseController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        $m=new PurchaseModel();
        $from=$_GET['from'] ?? date('Y-m-01'); $to=$_GET['to'] ?? today();
        $this->view('purchases/index',['pageTitle'=>'Input Belanja','from'=>$from,'to'=>$to,'items'=>$m->list($from,$to),'materials'=>$m->materials(),'vendors'=>$m->vendors()]);
    }
    public function store(): void
    {
        Auth::requireRoles(['super_admin','administrator']); verify_csrf();
        try { $id=(new PurchaseModel())->store($_POST); Audit::log('create_purchase','purchase_orders',$id,null,$_POST); $_SESSION['flash_success']='Belanja berhasil disimpan dan stok bahan diperbarui.'; }
        catch(Throwable $e){ $_SESSION['flash_error']=$e->getMessage(); }
        $this->redirect('/purchases');
    }
}

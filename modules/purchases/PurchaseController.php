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

    public function edit(string $id): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        $m = new PurchaseModel();
        $po = $m->getWithItems((int)$id);
        if (!$po) {
            $_SESSION['flash_error'] = 'Data belanja tidak ditemukan.';
            $this->redirect('/purchases');
            return;
        }
        $this->view('purchases/edit', [
            'pageTitle' => 'Edit Belanja',
            'po' => $po,
            'materials' => $m->materials(),
            'vendors' => $m->vendors()
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRoles(['super_admin','administrator']); verify_csrf();
        try { 
            (new PurchaseModel())->update((int)$id, $_POST); 
            Audit::log('update_purchase','purchase_orders',(int)$id,null,$_POST); 
            $_SESSION['flash_success']='Belanja berhasil diperbarui dan stok bahan dikalkulasi ulang.'; 
        } catch(Throwable $e) { 
            $_SESSION['flash_error']=$e->getMessage(); 
            $this->redirect('/purchases/edit/' . $id);
            return;
        }
        $this->redirect('/purchases');
    }
}

<?php
class VendorController extends Controller
{
 public function index(): void { Auth::requireRoles(['super_admin','administrator']); $this->view('vendors/index',['pageTitle'=>'Vendor / Supplier','items'=>(new VendorModel())->list()]); }
 public function store(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); try{$id=(new VendorModel())->store($_POST); Audit::log('save_vendor','vendors',$id,null,$_POST); $_SESSION['flash_success']='Vendor berhasil disimpan.';}catch(Throwable $e){$_SESSION['flash_error']=$e->getMessage();} $this->redirect('/vendors'); }
}

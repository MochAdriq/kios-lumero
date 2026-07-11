<?php
class ExpenseController extends Controller
{
 public function index(): void { Auth::requireRoles(['super_admin','administrator']); $m=new ExpenseModel(); $from=$_GET['from']??date('Y-m-01'); $to=$_GET['to']??today(); $this->view('expenses/index',['pageTitle'=>'Pengeluaran Operasional','from'=>$from,'to'=>$to,'items'=>$m->list($from,$to),'categories'=>$m->categories()]); }
 public function store(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); try{$id=(new ExpenseModel())->store($_POST); Audit::log('create_expense','operational_expenses',$id,null,$_POST); $_SESSION['flash_success']='Pengeluaran berhasil dicatat.';}catch(Throwable $e){$_SESSION['flash_error']=$e->getMessage();} $this->redirect('/expenses'); }
}

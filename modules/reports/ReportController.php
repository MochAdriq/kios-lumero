<?php
class ReportController extends Controller
{
 public function daily(): void { Auth::requireRoles(['super_admin','administrator']); $m=new ReportModel(); $bizDate=function_exists('business_date')?business_date():today(); $date=$_GET['date']??$bizDate; $this->view('reports/daily',['pageTitle'=>'Laporan Harian','date'=>$date,'data'=>$m->dailyData($date),'closings'=>$m->closings($date,$date)]); }
 public function generateDaily(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); $bizDate=function_exists('business_date')?business_date():today(); $date=$_POST['business_date']??$bizDate; try{(new ReportModel())->saveDaily($date,(float)($_POST['cash_physical']??0)); $_SESSION['flash_success']='Laporan harian berhasil digenerate.';}catch(Throwable $e){$_SESSION['flash_error']=$e->getMessage();} $this->redirect('/reports/daily?date='.$date); }
 public function financial(): void { Auth::requireRoles(['super_admin','administrator']); $m=new ReportModel(); $from=$_GET['from']??date('Y-m-01'); $to=$_GET['to']??today(); $this->view('reports/financial',['pageTitle'=>'Laporan Keuangan','from'=>$from,'to'=>$to,'pl'=>$m->profitLoss($from,$to),'closings'=>$m->closings($from,$to)]); }
}

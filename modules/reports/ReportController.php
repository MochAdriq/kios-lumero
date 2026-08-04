<?php
class ReportController extends Controller
{
 public function daily(): void { Auth::requireRoles(['super_admin','administrator']); $m=new ReportModel(); $bizDate=function_exists('business_date')?business_date():today(); $date=$_GET['date']??$bizDate; $this->view('reports/daily',['pageTitle'=>'Laporan Harian','date'=>$date,'data'=>$m->dailyData($date),'closings'=>$m->closings($date,$date)]); }
 public function generateDaily(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); $bizDate=function_exists('business_date')?business_date():today(); $date=$_POST['business_date']??$bizDate; try{(new ReportModel())->saveDaily($date,(float)($_POST['cash_physical']??0)); $_SESSION['flash_success']='Laporan harian berhasil digenerate.';}catch(Throwable $e){$_SESSION['flash_error']=$e->getMessage();} $this->redirect('/reports/daily?date='.$date); }
 public function financial(): void { Auth::requireRoles(['super_admin','administrator']); $m=new ReportModel(); $from=$_GET['from']??date('Y-m-01'); $to=$_GET['to']??today(); $this->view('reports/financial',['pageTitle'=>'Laporan Keuangan','from'=>$from,'to'=>$to,'pl'=>$m->profitLoss($from,$to),'closings'=>$m->closings($from,$to)]); }
 public function exportFinancialCSV(): void {
  Auth::requireRoles(['super_admin','administrator']);
  $m = new ReportModel();
  $from = $_GET['from'] ?? date('Y-m-01');
  $to = $_GET['to'] ?? today();
  $closings = $m->closings($from, $to);
  
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=Laporan_Keuangan_' . $from . '_sd_' . $to . '.csv');
  
  $output = fopen('php://output', 'w');
  
  fputcsv($output, [
   'Tanggal (Business Date)', 'Total Transaksi', 'Item Terjual', 'Total Omzet Kotor', 'Total Diskon', 'Total Pendapatan (Omzet Bersih)',
   'Total HPP', 'Total Profit Kasar', 'Masuk via QRIS', 'Masuk via Debit/E-Wallet', 'Masuk via Cash (Sistem)',
   'Pengeluaran Outlet', 'Sisa Cash Seharusnya', 'Sisa Cash Aktual di Laci', 'Selisih Uang (Minus/Lebih)', 'Laba Bersih Akhir'
  ]);
  
  foreach ($closings as $c) {
   $cashSeharusnya = (float)($c['cash_sales'] ?? 0) - (float)($c['total_expense'] ?? 0);
   $selisihUang = (float)($c['cash_physical'] ?? 0) - $cashSeharusnya;
   
   fputcsv($output, [
    $c['business_date'] ?? '-',
    $c['total_transactions'] ?? 0,
    $c['total_items_sold'] ?? 0,
    (float)($c['gross_sales'] ?? 0),
    (float)($c['discount_total'] ?? 0),
    (float)($c['total_revenue'] ?? 0),
    (float)($c['total_hpp'] ?? 0),
    (float)($c['gross_profit'] ?? 0),
    (float)($c['qris_sales'] ?? 0),
    (float)($c['debit_credit_sales'] ?? 0) + (float)($c['ewallet_sales'] ?? 0),
    (float)($c['cash_sales'] ?? 0),
    (float)($c['total_expense'] ?? 0),
    $cashSeharusnya,
    (float)($c['cash_physical'] ?? 0),
    $selisihUang,
    (float)($c['net_profit'] ?? 0)
   ]);
  }
  
  fclose($output);
  exit;
 }
}

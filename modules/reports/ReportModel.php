<?php
class ReportModel extends Model
{
 private function outletId(): int { return function_exists('current_outlet_id') ? current_outlet_id() : ((int)(Auth::user()['outlet_id']??1) ?: 1); }
 public function dailyData(string $date): array
 {
  $outlet=$this->outletId();
  $sales=$this->one("SELECT COUNT(*) trx, COALESCE(SUM(subtotal),0) gross_sales, COALESCE(SUM(discount_amount),0) discount_total, COALESCE(SUM(subtotal-discount_amount),0) net_sales, COALESCE(SUM(tax_amount),0) tax_total, COALESCE(SUM(service_amount),0) service_total, COALESCE(SUM(grand_total),0) total_revenue, COALESCE(SUM(total_hpp),0) total_hpp, COALESCE(SUM(gross_profit),0) gross_profit FROM orders WHERE outlet_id=? AND business_date=? AND payment_status='paid'",[$outlet,$date]) ?: [];
  $pay=$this->all("SELECT p.payment_method, COALESCE(SUM(p.amount),0) total FROM payments p JOIN orders o ON o.id=p.order_id WHERE o.outlet_id=? AND o.business_date=? AND p.status='paid' GROUP BY p.payment_method",[$outlet,$date]);
  $exp=$this->one("SELECT COALESCE(SUM(amount),0) total FROM operational_expenses WHERE outlet_id=? AND business_date=?",[$outlet,$date]);
  $payroll=$this->one("SELECT COALESCE(SUM(amount),0) total FROM payroll_expenses WHERE outlet_id=? AND business_date=?",[$outlet,$date]);
  $waste=$this->one("SELECT COALESCE(SUM(total_hpp),0) total FROM daily_product_stock_movements WHERE outlet_id=? AND business_date=? AND movement_type='wastage'",[$outlet,$date]);
  $items=$this->one("SELECT COALESCE(SUM(qty),0) qty FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.outlet_id=? AND o.business_date=? AND o.payment_status='paid'",[$outlet,$date]);
  $pays=['cash'=>0,'qris'=>0,'debit_credit'=>0,'ewallet'=>0]; foreach($pay as $p){$m=$p['payment_method']; if(in_array($m,['debit','credit'])) $pays['debit_credit']+=(float)$p['total']; elseif(isset($pays[$m])) $pays[$m]+=(float)$p['total'];}
  $totalExpense=(float)($exp['total']??0)+(float)($payroll['total']??0)+(float)($waste['total']??0);
  return array_merge($sales,['operational_expense'=>$exp['total']??0,'payroll_expense'=>$payroll['total']??0,'wastage_loss'=>$waste['total']??0,'total_expense'=>$totalExpense,'net_profit'=>(float)($sales['gross_profit']??0)-$totalExpense,'total_items_sold'=>$items['qty']??0],$pays);
 }
 public function saveDaily(string $date, float $cashPhysical=0): void
 { $d=$this->dailyData($date); $outlet=$this->outletId(); $session=$this->one("SELECT id FROM daily_store_sessions WHERE outlet_id=? AND business_date=? ORDER BY id DESC LIMIT 1",[$outlet,$date]); if(!$session) throw new RuntimeException('Sesi toko tanggal ini belum ada.'); $cashDiff=$cashPhysical-(float)$d['cash']; $this->execSql("INSERT INTO daily_closing_reports (outlet_id,daily_store_session_id,business_date,gross_sales,discount_total,net_sales,tax_total,service_total,total_revenue,total_hpp,gross_profit,payroll_expense,operational_expense,wastage_loss,total_expense,net_profit,cash_sales,qris_sales,debit_credit_sales,ewallet_sales,total_transactions,total_items_sold,cash_system,cash_physical,cash_difference,analysis_summary,closed_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE gross_sales=VALUES(gross_sales),discount_total=VALUES(discount_total),net_sales=VALUES(net_sales),tax_total=VALUES(tax_total),service_total=VALUES(service_total),total_revenue=VALUES(total_revenue),total_hpp=VALUES(total_hpp),gross_profit=VALUES(gross_profit),payroll_expense=VALUES(payroll_expense),operational_expense=VALUES(operational_expense),wastage_loss=VALUES(wastage_loss),total_expense=VALUES(total_expense),net_profit=VALUES(net_profit),cash_sales=VALUES(cash_sales),qris_sales=VALUES(qris_sales),debit_credit_sales=VALUES(debit_credit_sales),ewallet_sales=VALUES(ewallet_sales),total_transactions=VALUES(total_transactions),total_items_sold=VALUES(total_items_sold),cash_system=VALUES(cash_system),cash_physical=VALUES(cash_physical),cash_difference=VALUES(cash_difference),analysis_summary=VALUES(analysis_summary),closed_by=VALUES(closed_by),updated_at=NOW()",[$outlet,$session['id'],$date,$d['gross_sales'],$d['discount_total'],$d['net_sales'],$d['tax_total'],$d['service_total'],$d['total_revenue'],$d['total_hpp'],$d['gross_profit'],$d['payroll_expense'],$d['operational_expense'],$d['wastage_loss'],$d['total_expense'],$d['net_profit'],$d['cash'],$d['qris'],$d['debit_credit'],$d['ewallet'],$d['trx'],$d['total_items_sold'],$d['cash'],$cashPhysical,$cashDiff,$this->analysis($d),Auth::id()]); }
 private function analysis(array $d): string { $margin=((float)$d['total_revenue']>0)?((float)$d['net_profit']/(float)$d['total_revenue']*100):0; return 'Margin bersih '.number_format($margin,1,',','.').'%. '.(((float)$d['net_profit']>=0)?'Operasional profit.':'Perlu evaluasi biaya/HPP.'); }
 public function closings(string $from,string $to): array { 
  $rows = $this->all("SELECT * FROM daily_closing_reports WHERE outlet_id=? AND business_date BETWEEN ? AND ? ORDER BY business_date ASC",[$this->outletId(),$from,$to]); 
  $dataByDate = [];
  foreach ($rows as $row) { $dataByDate[$row['business_date']] = $row; }
  $result = [];
  $current = strtotime($from);
  $end = strtotime($to);
  while ($current <= $end) {
      $dateStr = date('Y-m-d', $current);
      if (isset($dataByDate[$dateStr])) {
          $result[] = $dataByDate[$dateStr];
      } else {
          $result[] = ['business_date' => $dateStr, 'total_revenue' => 0, 'total_hpp' => 0, 'gross_profit' => 0, 'total_expense' => 0, 'net_profit' => 0, 'total_transactions' => 0];
      }
      $current = strtotime('+1 day', $current);
  }
  return $result;
 }
 public function profitLoss(string $from,string $to): array { return $this->one("SELECT COALESCE(SUM(total_revenue),0) revenue, COALESCE(SUM(total_hpp),0) hpp, COALESCE(SUM(gross_profit),0) gross_profit, COALESCE(SUM(total_expense),0) expense, COALESCE(SUM(net_profit),0) net_profit FROM daily_closing_reports WHERE outlet_id=? AND business_date BETWEEN ? AND ?",[$this->outletId(),$from,$to]) ?: []; }
}

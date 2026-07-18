<?php
class DashboardModel extends Model
{
    public function summary(int $outletId): array
    {
        $date = business_date($outletId);
        $sales = $this->one("SELECT COUNT(*) trx, COALESCE(SUM(grand_total),0) omzet, COALESCE(SUM(total_hpp),0) hpp, COALESCE(SUM(gross_profit),0) laba FROM orders WHERE outlet_id=? AND business_date=? AND payment_status='paid'", [$outletId,$date]);
        $low = $this->one("SELECT COUNT(*) total FROM raw_materials rm LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ? WHERE rm.is_active=1 AND rm.outlet_id = ? AND COALESCE(orm.stock_qty, rm.stock_qty, 0) <= COALESCE(orm.min_stock_qty, rm.min_stock_qty, 0)", [$outletId, $outletId]);
        $store = $this->one("SELECT * FROM daily_store_sessions WHERE outlet_id=? AND business_date=? ORDER BY id DESC LIMIT 1", [$outletId,$date]);
        return ['sales'=>$sales,'low_stock'=>$low['total'] ?? 0,'store'=>$store];
    }
    public function topItems(int $outletId): array
    {
        return $this->all("SELECT oi.product_name_snapshot, oi.variant_name_snapshot, SUM(oi.qty) qty, SUM(oi.subtotal) total FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.outlet_id=? AND o.business_date=? AND o.payment_status='paid' GROUP BY oi.product_name_snapshot, oi.variant_name_snapshot ORDER BY qty DESC LIMIT 8", [$outletId, business_date($outletId)]);
    }
    public function weeklySales(int $outletId): array
    {
        return $this->all("SELECT business_date, COALESCE(SUM(grand_total),0) omzet, COALESCE(SUM(total_hpp),0) hpp, COALESCE(SUM(gross_profit),0) laba FROM orders WHERE outlet_id=? AND business_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND payment_status='paid' GROUP BY business_date ORDER BY business_date", [$outletId]);
    }
}

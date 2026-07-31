<?php
class ProductSalesModel extends Model
{
    public function getSalesStats(string $startDate, string $endDate, ?int $outletId = null, ?int $categoryId = null): array
    {
        $params = [$startDate, $endDate];
        $outletCondition = "";
        $categoryCondition = "";
        
        if ($outletId !== null) {
            $outletCondition = " AND o.outlet_id = ? ";
            $params[] = $outletId;
        }
        
        if ($categoryId !== null) {
            $categoryCondition = " AND p.category_id = ? ";
            $params[] = $categoryId;
        }

        // Query excludes voided/cancelled/refunded orders to match valid OMZET
        $sql = "
            SELECT 
                oi.product_name_snapshot AS product_name,
                oi.variant_name_snapshot AS variant_name,
                SUM(oi.qty) AS total_qty,
                SUM(oi.subtotal - oi.discount_amount) AS total_revenue,
                SUM(oi.gross_profit) AS total_profit
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            LEFT JOIN product_variants pv ON oi.product_variant_id = pv.id
            LEFT JOIN products p ON pv.product_id = p.id
            WHERE o.order_status NOT IN ('voided', 'cancelled')
              AND COALESCE(o.payment_status, '') != 'refunded'
              AND DATE(o.created_at) BETWEEN ? AND ?
              {$outletCondition}
              {$categoryCondition}
            GROUP BY oi.product_name_snapshot, oi.variant_name_snapshot
            ORDER BY total_qty DESC
        ";

        return $this->all($sql, $params);
    }
}

<?php
class HQModel extends Model
{
    /**
     * Summary of all active branches for HQ dashboard.
     */
    public function allBranchSummary(): array
    {
        $bizDate = function_exists('business_date') ? business_date() : today();
        return $this->all("
            SELECT
                o.id AS outlet_id,
                o.name AS outlet_name,
                o.slug,
                o.outlet_code AS code,
                o.is_hq,
                o.closing_hour,
                COALESCE((SELECT ds.status FROM daily_store_sessions ds WHERE ds.outlet_id = o.id AND ds.business_date = ? ORDER BY ds.id DESC LIMIT 1), 'not_opened') AS store_status,
                COALESCE(ord_sum.trx, 0) AS today_trx,
                COALESCE(ord_sum.revenue, 0) AS today_revenue,
                COALESCE(ord_sum.hpp, 0) AS today_hpp,
                COALESCE(ord_sum.profit, 0) AS today_profit,
                ord_sum.last_order_at,
                COALESCE(u_count.total, 0) AS user_count
            FROM outlets o
            LEFT JOIN (
                SELECT outlet_id, COUNT(*) AS trx, SUM(grand_total) AS revenue, SUM(total_hpp) AS hpp, SUM(gross_profit) AS profit, MAX(updated_at) AS last_order_at
                FROM orders WHERE business_date = ? AND payment_status = 'paid'
                GROUP BY outlet_id
            ) ord_sum ON ord_sum.outlet_id = o.id
            LEFT JOIN (
                SELECT outlet_id, COUNT(*) AS total FROM users WHERE is_active = 1 GROUP BY outlet_id
            ) u_count ON u_count.outlet_id = o.id
            WHERE o.is_active = 1
            ORDER BY o.is_hq DESC, o.name ASC
        ", [$bizDate, $bizDate]);
    }

    /**
     * Combined totals across all branches.
     */
    public function totalsSummary(): array
    {
        $bizDate = function_exists('business_date') ? business_date() : today();
        $row = $this->one("
            SELECT
                COUNT(*) AS total_trx,
                COALESCE(SUM(grand_total), 0) AS total_revenue,
                COALESCE(SUM(total_hpp), 0) AS total_hpp,
                COALESCE(SUM(gross_profit), 0) AS total_profit
            FROM orders
            WHERE business_date = ? AND payment_status = 'paid'
        ", [$bizDate]);
        return $row ?: ['total_trx' => 0, 'total_revenue' => 0, 'total_hpp' => 0, 'total_profit' => 0];
    }

    /**
     * Weekly revenue per branch for chart.
     */
    public function weeklyPerBranch(): array
    {
        return $this->all("
            SELECT o.id AS outlet_id, o.name AS outlet_name, o.slug,
                ord.business_date,
                COALESCE(SUM(ord.grand_total), 0) AS revenue
            FROM outlets o
            LEFT JOIN orders ord ON ord.outlet_id = o.id
                AND ord.business_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                AND ord.payment_status = 'paid'
            WHERE o.is_active = 1
            GROUP BY o.id, o.name, o.slug, ord.business_date
            ORDER BY o.name, ord.business_date
        ");
    }

    /**
     * Weekly chart-ready data with guaranteed complete date buckets.
     */
    public function weeklyChartData(int $days = 7, ?string $endDate = null): array
    {
        $days = max(1, min($days, 31));
        $endDate = $endDate ?: (function_exists('business_date') ? business_date() : today());

        $end = DateTimeImmutable::createFromFormat('Y-m-d', $endDate) ?: new DateTimeImmutable(today());
        $start = $end->sub(new DateInterval('P' . ($days - 1) . 'D'));
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');

        $outlets = $this->all("
            SELECT id, name, is_hq
            FROM outlets
            WHERE is_active = 1
            ORDER BY is_hq DESC, name ASC
        ");

        $rows = $this->all("
            SELECT outlet_id, business_date, COALESCE(SUM(grand_total), 0) AS revenue
            FROM orders
            WHERE business_date BETWEEN ? AND ?
              AND payment_status = 'paid'
            GROUP BY outlet_id, business_date
        ", [$startDate, $endDate]);

        $dates = [];
        $categories = [];
        $dateIndex = [];
        $cursor = $start;
        $i = 0;
        while ($cursor <= $end) {
            $dateKey = $cursor->format('Y-m-d');
            $dates[] = $dateKey;
            $categories[] = $cursor->format('d M');
            $dateIndex[$dateKey] = $i;
            $cursor = $cursor->modify('+1 day');
            $i++;
        }

        $seriesMap = [];
        foreach ($outlets as $outlet) {
            $outletId = (int)($outlet['id'] ?? 0);
            if ($outletId <= 0) {
                continue;
            }
            $seriesMap[$outletId] = [
                'name' => (string)($outlet['name'] ?? ('Outlet #' . $outletId)),
                'data' => array_fill(0, $days, 0),
            ];
        }

        foreach ($rows as $row) {
            $outletId = (int)($row['outlet_id'] ?? 0);
            $dateKey = (string)($row['business_date'] ?? '');
            if (!isset($seriesMap[$outletId], $dateIndex[$dateKey])) {
                continue;
            }
            $seriesMap[$outletId]['data'][$dateIndex[$dateKey]] = (float)($row['revenue'] ?? 0);
        }

        return [
            'from' => $startDate,
            'to' => $endDate,
            'dates' => $dates,
            'categories' => $categories,
            'series' => array_values($seriesMap),
        ];
    }

    /**
     * Cross-branch financial report for a date range.
     */
    public function crossBranchReport(string $from, string $to): array
    {
        return $this->all("
            SELECT
                o.id AS outlet_id, o.name AS outlet_name, o.slug, o.is_hq,
                COALESCE(SUM(cr.total_revenue), 0) AS revenue,
                COALESCE(SUM(cr.total_hpp), 0) AS hpp,
                COALESCE(SUM(cr.gross_profit), 0) AS gross_profit,
                COALESCE(SUM(cr.total_expense), 0) AS expense,
                COALESCE(SUM(cr.net_profit), 0) AS net_profit,
                COUNT(cr.id) AS days_reported
            FROM outlets o
            LEFT JOIN daily_closing_reports cr ON cr.outlet_id = o.id AND cr.business_date BETWEEN ? AND ?
            WHERE o.is_active = 1
            GROUP BY o.id, o.name, o.slug
            ORDER BY o.is_hq DESC, o.name
        ", [$from, $to]);
    }
}

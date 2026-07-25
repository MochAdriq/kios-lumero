<?php
class DailyStockModel extends Model
{
    private function outletId(): int
    {
        if (function_exists('current_outlet_id')) {
            return current_outlet_id();
        }

        $user = Auth::user() ?? [];
        return (int)($user['outlet_id'] ?? 1) ?: 1;
    }

    public function categories(): array
    {
        $scope = ['sql' => 'outlet_id = ?', 'params' => [$this->outletId()]];
        return $this->all("SELECT id, name FROM product_categories WHERE is_active=1 AND {$scope['sql']} ORDER BY sort_order, name", $scope['params']);
    }

    public function products(string $date, string $search = '', int $categoryId = 0): array
    {
        $outletId = $this->outletId();
        $pScope = ['sql' => 'p.outlet_id = ?', 'params' => [$outletId]];
        $pvScope = ['sql' => 'pv.outlet_id = ?', 'params' => [$outletId]];
        $pcScope = ['sql' => 'pc.outlet_id = ?', 'params' => [$outletId]];
        $where = "WHERE p.is_active=1 AND pv.is_active=1 AND pc.is_active=1
            AND {$pScope['sql']}
            AND {$pvScope['sql']}
            AND {$pcScope['sql']}";
        $params = array_merge($pScope['params'], $pvScope['params'], $pcScope['params']);

        if ($categoryId > 0) {
            $where .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        if ($search !== '') {
            $where .= " AND (p.name LIKE ? OR pv.variant_name LIKE ? OR pv.sku LIKE ?)";
            $q = '%' . $search . '%';
            $params[] = $q; $params[] = $q; $params[] = $q;
        }

        $items = $this->all("SELECT
                pv.id AS product_variant_id,
                pv.sku,
                pv.variant_name,
                pv.hpp,
                pv.selling_price,
                p.name AS product_name,
                p.image,
                pc.name AS category_name
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            JOIN product_categories pc ON pc.id = p.category_id
            $where
            ORDER BY pc.sort_order, pc.name, p.name, pv.variant_name
            LIMIT 500", $params);

        $salesRaw = $this->all("SELECT oi.product_variant_id, SUM(oi.qty) as sold_qty
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.outlet_id = ? AND o.business_date = ? AND o.order_status = 'completed'
            GROUP BY oi.product_variant_id", [$outletId, $date]);
        $salesMap = [];
        foreach ($salesRaw as $r) {
            $salesMap[$r['product_variant_id']] = (float)$r['sold_qty'];
        }

        require_once __DIR__ . '/../recipes/RecipeModel.php';
        $mRecipe = new RecipeModel();

        $vids = array_map(function($it) { return (int)$it['product_variant_id']; }, $items);
        $bulkYields = $mRecipe->calculateBulkMaxYield($vids, $outletId);

        foreach ($items as &$it) {
            $vid = (int)$it['product_variant_id'];
            $maxYield = $bulkYields[$vid] ?? 0.0;
            $it['closing_qty'] = $maxYield;
            $it['stock_status'] = $maxYield > 0 ? ($maxYield <= 3 ? 'low' : 'available') : 'sold_out';
            $it['opening_qty'] = 0; // Read-only projection
            $it['produced_qty'] = 0; // Read-only projection
            $it['sold_qty'] = $salesMap[$vid] ?? 0;
            $it['wasted_qty'] = 0; // Read-only projection
        }
        return $items;
    }

    public function summary(string $date): array
    {
        $items = $this->products($date);
        $summary = [
            'total_items' => count($items),
            'closing_qty' => 0,
            'sold_out_count' => 0,
            'low_count' => 0,
            'opening_qty' => 0,
            'produced_qty' => 0,
            'sold_qty' => 0,
            'wasted_qty' => 0,
        ];
        foreach ($items as $it) {
            $summary['closing_qty'] += $it['closing_qty'];
            $summary['sold_qty'] += $it['sold_qty'];
            if ($it['stock_status'] === 'sold_out') $summary['sold_out_count']++;
            if ($it['stock_status'] === 'low') $summary['low_count']++;
        }
        return $summary;
    }

    public function saveBulk(string $date, array $payload): array
    {
        throw new RuntimeException('Stok harian sekarang berjalan otomatis (dinamis) berdasarkan bahan mentah. Input manual telah dinonaktifkan.');
    }

    public function recentMovements(string $date, int $limit = 30): array
    {
        return $this->all("SELECT m.*, p.name AS product_name, pv.variant_name
            FROM daily_product_stock_movements m
            JOIN product_variants pv ON pv.id=m.product_variant_id
            JOIN products p ON p.id=pv.product_id
            WHERE m.outlet_id=? AND m.business_date=?
            ORDER BY m.id DESC
            LIMIT $limit", [$this->outletId(), $date]);
    }
}

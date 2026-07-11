<?php
class ExecutiveModel extends Model
{
    private function outletId(): int
    {
        return function_exists('current_outlet_id') ? current_outlet_id() : ((int)(Auth::user()['outlet_id'] ?? 1) ?: 1);
    }

    // ── Table Existence & Helpers ──────────────────────────────
    private function tableExists(string $table): bool
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
            $st->execute([$table]);
            return (int)$st->fetchColumn() > 0;
        } catch (Throwable $e) { return false; }
    }

    private function colExists(string $table, string $col): bool
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
            $st->execute([$table, $col]);
            return (int)$st->fetchColumn() > 0;
        } catch (Throwable $e) { return false; }
    }

    private function scalar(string $sql, array $params = [])
    {
        try { $st = $this->db->prepare($sql); $st->execute($params); return $st->fetchColumn(); } catch (Throwable $e) { return null; }
    }

    private function safeAll(string $sql, array $params = []): array
    {
        try { $st = $this->db->prepare($sql); $st->execute($params); return $st->fetchAll(); } catch (Throwable $e) { return []; }
    }

    // ── Settings (per outlet) ──────────────────────────────────
    public function ensureTables(): void
    {
        $out = $this->outletId();

        // Auto-upgrade business_capitals: add V1 columns if missing
        if ($this->tableExists('business_capitals')) {
            $cols = [
                ['category', "VARCHAR(100) NOT NULL DEFAULT 'Modal Awal'"],
                ['component_name', 'VARCHAR(180) DEFAULT NULL'],
                ['payment_method', 'VARCHAR(50) DEFAULT NULL'],
                ['supplier', 'VARCHAR(160) DEFAULT NULL'],
                ['invoice_no', 'VARCHAR(100) DEFAULT NULL'],
                ['is_active', 'TINYINT(1) NOT NULL DEFAULT 1'],
            ];
            foreach ($cols as [$col, $def]) {
                if (!$this->colExists('business_capitals', $col)) {
                    $this->db->exec("ALTER TABLE business_capitals ADD COLUMN `$col` $def");
                }
            }
            // Backfill component_name from description
            $this->db->exec("UPDATE business_capitals SET component_name = COALESCE(NULLIF(component_name,''), description, capital_type) WHERE component_name IS NULL OR component_name = ''");
        }

        $this->db->exec("CREATE TABLE IF NOT EXISTS business_roi_settings (
            id INT AUTO_INCREMENT PRIMARY KEY, outlet_id INT NOT NULL DEFAULT 1,
            setting_key VARCHAR(80) NOT NULL, setting_value TEXT DEFAULT NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_roi_setting (outlet_id, setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS owner_cash_allocation_rules (
            id INT AUTO_INCREMENT PRIMARY KEY, outlet_id INT NOT NULL DEFAULT 1,
            rule_name VARCHAR(120) NOT NULL, allocation_type VARCHAR(40) NOT NULL,
            percent_of_sales DECIMAL(8,2) NOT NULL DEFAULT 0, fixed_amount INT NOT NULL DEFAULT 0,
            priority_order INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cash_alloc_outlet (outlet_id, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS market_trend_keywords (
            id INT AUTO_INCREMENT PRIMARY KEY, keyword VARCHAR(160) NOT NULL,
            product_idea VARCHAR(180) DEFAULT NULL, category VARCHAR(100) DEFAULT 'Ayam Crispy',
            source_note VARCHAR(255) DEFAULT NULL, base_hpp_estimate INT NOT NULL DEFAULT 0,
            suggested_price INT NOT NULL DEFAULT 0, complexity_score TINYINT NOT NULL DEFAULT 3,
            stock_fit_score TINYINT NOT NULL DEFAULT 3, is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_market_keyword (keyword)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS menu_experiment_plans (
            id INT AUTO_INCREMENT PRIMARY KEY, outlet_id INT NOT NULL DEFAULT 1,
            experiment_name VARCHAR(180) NOT NULL, source_keyword VARCHAR(180) DEFAULT NULL,
            start_date DATE NOT NULL, end_date DATE NOT NULL,
            target_orders_per_day INT NOT NULL DEFAULT 0, target_margin_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
            estimated_hpp INT NOT NULL DEFAULT 0, suggested_price INT NOT NULL DEFAULT 0,
            status ENUM('planned','running','completed','stopped') NOT NULL DEFAULT 'planned',
            decision ENUM('pending','make_permanent','continue_test','stop') NOT NULL DEFAULT 'pending',
            notes TEXT DEFAULT NULL, created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_experiment_outlet (outlet_id), INDEX idx_experiment_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Seed defaults
        $defaults = [
            'business_start_date' => '2026-05-17', 'projection_working_days_month' => '30',
            'daily_sales_target' => '1000000', 'owner_reserve_percent' => '5',
            'roi_payback_percent' => '15', 'growth_conservative_pct' => '0',
            'growth_base_pct' => '8', 'growth_aggressive_pct' => '18',
        ];
        $st = $this->db->prepare("INSERT INTO business_roi_settings (outlet_id,setting_key,setting_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=setting_value");
        foreach ($defaults as $k => $v) { $st->execute([$out, $k, $v]); }

        // Seed cash allocation rules
        $rules = [
            ['Simpan HPP untuk Restock', 'hpp_restock', 0, 0, 10],
            ['Dana Operasional Harian', 'operational', 0, 0, 20],
            ['Cadangan Darurat Outlet', 'emergency_reserve', 5, 0, 30],
            ['Setoran Balik Modal / ROI', 'roi_payback', 15, 0, 40],
            ['Uang Aman Ditarik Owner', 'owner_draw', 0, 0, 99],
        ];
        $st2 = $this->db->prepare("INSERT INTO owner_cash_allocation_rules (outlet_id,rule_name,allocation_type,percent_of_sales,fixed_amount,priority_order,is_active) VALUES (?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE rule_name=VALUES(rule_name)");
        foreach ($rules as $r) { $st2->execute([$out, ...$r]); }
    }

    public function setting(string $key, string $default = ''): string
    {
        $v = $this->scalar("SELECT setting_value FROM business_roi_settings WHERE outlet_id=? AND setting_key=? LIMIT 1", [$this->outletId(), $key]);
        return $v !== null && $v !== false ? (string)$v : $default;
    }

    public function setSetting(string $key, string $val): void
    {
        $this->execSql("INSERT INTO business_roi_settings (outlet_id,setting_key,setting_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)", [$this->outletId(), $key, $val]);
    }

    // ── Finance Data ───────────────────────────────────────────
    public function financeSummary(string $from, string $to): array
    {
        $out = $this->outletId();
        // Prefer daily_closing_reports (DCC native)
        if ($this->tableExists('daily_closing_reports')) {
            $r = $this->one("SELECT
                COALESCE(SUM(total_revenue),0) gross_sales,
                COALESCE(SUM(total_hpp),0) hpp,
                COALESCE(SUM(gross_profit),0) gross_profit,
                COALESCE(SUM(total_expense),0) expenses,
                COALESCE(SUM(net_profit),0) net_profit,
                COALESCE(SUM(total_transactions),0) paid_orders
                FROM daily_closing_reports WHERE outlet_id=? AND business_date BETWEEN ? AND ?", [$out, $from, $to]);
            if ($r) {
                foreach (['gross_sales','hpp','gross_profit','expenses','net_profit','paid_orders'] as $k) $r[$k] = (float)($r[$k] ?? 0);
                return $r;
            }
        }
        // Fallback to orders table
        $gross = (float)($this->scalar("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status='paid' AND status<>'cancelled'", [$from, $to]) ?? 0);
        $hpp = (float)($this->scalar("SELECT COALESCE(SUM(total_hpp),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status='paid' AND status<>'cancelled'", [$from, $to]) ?? 0);
        $orders = (float)($this->scalar("SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status='paid' AND status<>'cancelled'", [$from, $to]) ?? 0);
        $expenses = 0;
        if ($this->tableExists('expenses')) {
            $col = $this->colExists('expenses', 'expense_date') ? 'expense_date' : ($this->colExists('expenses', 'created_at') ? 'DATE(created_at)' : null);
            if ($col) {
                $scope = outlet_scope_sql('outlet_id', $out);
                $expenses = (float)($this->scalar("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE {$col} BETWEEN ? AND ? AND {$scope['sql']}", array_merge([$from, $to], $scope['params'])) ?? 0);
            }
        }
        return ['gross_sales' => $gross, 'hpp' => $hpp, 'gross_profit' => $gross - $hpp, 'expenses' => $expenses, 'net_profit' => $gross - $hpp - $expenses, 'paid_orders' => $orders];
    }

    public function dailyRows(string $from, string $to): array
    {
        $out = $this->outletId();
        if ($this->tableExists('daily_closing_reports')) {
            $rows = $this->safeAll("SELECT business_date `date`, COALESCE(total_transactions,0) orders, COALESCE(total_revenue,0) sales, COALESCE(total_hpp,0) hpp, COALESCE(total_expense,0) expenses, COALESCE(net_profit,0) net_profit FROM daily_closing_reports WHERE outlet_id=? AND business_date BETWEEN ? AND ? ORDER BY business_date", [$out, $from, $to]);
            if ($rows) return array_map(function($r) { foreach ($r as $k => $v) $r[$k] = $k === 'date' ? $v : (float)$v; return $r; }, $rows);
        }
        // Fallback: iterate dates
        $rows = [];
        $a = new DateTime($from); $b = new DateTime($to);
        while ($a <= $b) {
            $d = $a->format('Y-m-d');
            $f = $this->financeSummary($d, $d);
            $rows[] = ['date' => $d, 'orders' => $f['paid_orders'], 'sales' => $f['gross_sales'], 'hpp' => $f['hpp'], 'expenses' => $f['expenses'], 'net_profit' => $f['net_profit']];
            $a->modify('+1 day');
        }
        return $rows;
    }

    public function activeDays(string $from, string $to): int
    {
        $out = $this->outletId();
        if ($this->tableExists('daily_closing_reports')) {
            $n = (int)($this->scalar("SELECT COUNT(*) FROM daily_closing_reports WHERE outlet_id=? AND business_date BETWEEN ? AND ? AND total_transactions > 0", [$out, $from, $to]) ?? 0);
            if ($n > 0) return $n;
        }
        if ($this->tableExists('orders')) {
            $n = (int)($this->scalar("SELECT COUNT(*) FROM (SELECT DATE(created_at) d FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status='paid' GROUP BY DATE(created_at)) x", [$from, $to]) ?? 0);
            if ($n > 0) return $n;
        }
        return 1;
    }

    public function calendarDays(string $from, string $to): int
    {
        $a = new DateTime($from); $b = new DateTime($to);
        return max(1, $a->diff($b)->days + 1);
    }

    // ── Capital ────────────────────────────────────────────────
    public function activeCapital(): float
    {
        $out = $this->outletId();
        if ($this->colExists('business_capitals', 'is_active')) {
            return (float)($this->scalar("SELECT COALESCE(SUM(amount),0) FROM business_capitals WHERE outlet_id=? AND is_active=1", [$out]) ?? 0);
        }
        return (float)($this->scalar("SELECT COALESCE(SUM(amount),0) FROM business_capitals WHERE outlet_id=?", [$out]) ?? 0);
    }

    public function capitals(): array
    {
        $out = $this->outletId();
        if ($this->colExists('business_capitals', 'is_active')) {
            return $this->safeAll("SELECT * FROM business_capitals WHERE outlet_id=? ORDER BY is_active DESC, capital_date ASC, id ASC", [$out]);
        }
        return $this->safeAll("SELECT *, 1 as is_active FROM business_capitals WHERE outlet_id=? ORDER BY capital_date ASC, id ASC", [$out]);
    }

    public function capitalByCategory(): array
    {
        $out = $this->outletId();
        $active = $this->colExists('business_capitals', 'is_active') ? 'AND is_active=1' : '';
        return $this->safeAll("SELECT COALESCE(category, capital_type, 'Modal') category, COALESCE(SUM(amount),0) amount, COUNT(*) items FROM business_capitals WHERE outlet_id=? $active GROUP BY category ORDER BY amount DESC", [$out]);
    }

    public function saveCapital(array $d): void
    {
        $out = $this->outletId();
        $id = (int)($d['id'] ?? 0);
        $hasCols = $this->colExists('business_capitals', 'component_name');
        if ($id > 0 && $hasCols) {
            $this->execSql("UPDATE business_capitals SET capital_date=?,category=?,component_name=?,description=?,amount=?,payment_method=?,supplier=?,invoice_no=?,is_active=? WHERE id=? AND outlet_id=?", [
                $d['capital_date'], $d['category'], $d['component_name'], $d['description'] ?? '', (int)$d['amount'],
                $d['payment_method'] ?? null, $d['supplier'] ?? null, $d['invoice_no'] ?? null, (int)($d['is_active'] ?? 1), $id, $out
            ]);
        } elseif ($hasCols) {
            $this->execSql("INSERT INTO business_capitals (outlet_id,capital_date,category,component_name,description,amount,payment_method,supplier,invoice_no,is_active,capital_type,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())", [
                $out, $d['capital_date'], $d['category'], $d['component_name'], $d['description'] ?? '', (int)$d['amount'],
                $d['payment_method'] ?? null, $d['supplier'] ?? null, $d['invoice_no'] ?? null, 1, $d['category']
            ]);
        } else {
            $this->execSql("INSERT INTO business_capitals (outlet_id,capital_type,amount,description,capital_date,created_at) VALUES (?,?,?,?,?,NOW())", [
                $out, $d['category'] ?? 'initial_capital', (float)$d['amount'], trim($d['description'] ?? ''), $d['capital_date'] ?: today()
            ]);
        }
    }

    public function deactivateCapital(int $id): void
    {
        $this->execSql("UPDATE business_capitals SET is_active=0 WHERE id=? AND outlet_id=?", [$id, $this->outletId()]);
    }

    // ── Products & Menu Matrix ─────────────────────────────────
    public function topProducts(string $from, string $to): array
    {
        if (!$this->tableExists('order_items') || !$this->tableExists('orders')) return [];
        return $this->safeAll("SELECT oi.item_name, SUM(oi.qty) qty, SUM(oi.line_total) revenue,
            SUM(COALESCE(oi.line_hpp,0)) hpp, SUM(oi.line_total)-SUM(COALESCE(oi.line_hpp,0)) gross_profit
            FROM order_items oi JOIN orders o ON o.id=oi.order_id
            WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status='paid' AND o.status<>'cancelled'
            GROUP BY oi.item_name ORDER BY revenue DESC, qty DESC LIMIT 8", [$from, $to]);
    }

    public function menuProfitMatrix(array $topProducts): array
    {
        if (!$topProducts) return ['avg_qty' => 0, 'avg_margin' => 0, 'groups' => ['star' => [], 'traffic' => [], 'hidden' => [], 'danger' => []]];
        $qtys = array_map(fn($p) => (float)($p['qty'] ?? 0), $topProducts);
        $margins = [];
        foreach ($topProducts as $p) { $rev = (float)($p['revenue'] ?? 0); $gp = (float)($p['gross_profit'] ?? 0); $margins[] = $rev > 0 ? $gp / $rev * 100 : 0; }
        $qtyAvg = array_sum($qtys) / count($qtys);
        $marginAvg = count($margins) ? array_sum($margins) / count($margins) : 0;
        $matrix = ['star' => [], 'traffic' => [], 'hidden' => [], 'danger' => []];
        foreach ($topProducts as $p) {
            $qty = (float)($p['qty'] ?? 0); $rev = (float)($p['revenue'] ?? 0); $gp = (float)($p['gross_profit'] ?? 0);
            $m = $rev > 0 ? $gp / $rev * 100 : 0;
            $item = $p; $item['margin_pct'] = $m;
            if ($qty >= $qtyAvg && $m >= $marginAvg) $matrix['star'][] = $item;
            elseif ($qty >= $qtyAvg && $m < $marginAvg) $matrix['traffic'][] = $item;
            elseif ($qty < $qtyAvg && $m >= $marginAvg) $matrix['hidden'][] = $item;
            else $matrix['danger'][] = $item;
        }
        return ['avg_qty' => $qtyAvg, 'avg_margin' => $marginAvg, 'groups' => $matrix];
    }

    // ── Inventory Decision ─────────────────────────────────────
    public function inventoryDecision(): array
    {
        $out = ['urgent' => [], 'watch' => [], 'safe' => [], 'notes' => []];
        if (!$this->tableExists('inventory_items')) return $out;
        $rows = $this->safeAll("SELECT id,name,unit,stock_qty,unit_cost,COALESCE(min_stock,0) min_stock FROM inventory_items WHERE is_active=1 ORDER BY name");
        foreach ($rows as $r) {
            $stock = (float)($r['stock_qty'] ?? 0); $min = (float)($r['min_stock'] ?? 0); $days = null;
            if ($this->tableExists('inventory_movements') && $this->colExists('inventory_movements', 'inventory_item_id')) {
                $used = (float)($this->scalar("SELECT COALESCE(SUM(ABS(qty)),0) FROM inventory_movements WHERE inventory_item_id=? AND movement_type IN ('sale','out','usage','adjust_out') AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()", [(int)$r['id']]) ?? 0);
                if ($used > 0) $days = $stock / ($used / 7);
            }
            $r['days_left'] = $days;
            if ($stock <= 0 || ($min > 0 && $stock <= $min) || ($days !== null && $days <= 2)) $out['urgent'][] = $r;
            elseif (($min > 0 && $stock <= $min * 1.5) || ($days !== null && $days <= 5)) $out['watch'][] = $r;
            else $out['safe'][] = $r;
        }
        if (count($out['urgent']) > 0) $out['notes'][] = 'Prioritaskan pembelian bahan kritis. Jangan belanja semua item sekaligus jika cashflow belum kuat.';
        if (count($out['watch']) > 0) $out['notes'][] = 'Item watch-list perlu dipantau 2-5 hari ke depan dan cocok masuk rencana belanja bertahap.';
        if (!count($out['urgent']) && !count($out['watch'])) $out['notes'][] = 'Stok relatif aman. Tahan belanja besar dan fokus jualan agar cashflow tidak terkunci.';
        return $out;
    }

    // ── Trends & Experiments ───────────────────────────────────
    public function trendRecommendations(): array
    {
        if (!$this->tableExists('market_trend_keywords')) return [];
        $rows = $this->safeAll("SELECT * FROM market_trend_keywords WHERE is_active=1 ORDER BY stock_fit_score DESC, complexity_score ASC, suggested_price DESC LIMIT 12");
        foreach ($rows as &$r) {
            $price = (int)($r['suggested_price'] ?? 0); $hpp = (int)($r['base_hpp_estimate'] ?? 0);
            $r['margin_pct'] = ($price > 0 && $hpp > 0) ? (($price - $hpp) / $price * 100) : 0;
            $risk = max(0, (int)($r['complexity_score'] ?? 3) - 3) * 5;
            $r['gross_profit_per_unit'] = max(0, $price - $hpp);
            $r['final_score'] = min(100, max(0, ($r['stock_fit_score'] * 14) + ((6 - $r['complexity_score']) * 9) + min(32, max(0, $r['margin_pct'] / 1.5)) - $risk));
            $r['decision'] = $r['final_score'] >= 72 ? 'UJI 7 HARI' : ($r['final_score'] >= 55 ? 'UJI TERBATAS' : ($r['final_score'] >= 42 ? 'PANTAU DULU' : 'TUNDA'));
        }
        unset($r);
        usort($rows, fn($a, $b) => $b['final_score'] <=> $a['final_score']);
        return $rows;
    }

    public function saveTrendKeyword(array $d): void
    {
        $this->execSql("INSERT INTO market_trend_keywords (keyword,product_idea,category,source_note,base_hpp_estimate,suggested_price,complexity_score,stock_fit_score,is_active) VALUES (?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE product_idea=VALUES(product_idea),category=VALUES(category),source_note=VALUES(source_note),base_hpp_estimate=VALUES(base_hpp_estimate),suggested_price=VALUES(suggested_price),complexity_score=VALUES(complexity_score),stock_fit_score=VALUES(stock_fit_score)", [
            trim($d['keyword']), trim($d['product_idea'] ?? ''), trim($d['category'] ?? 'Ayam Crispy'),
            trim($d['source_note'] ?? ''), (int)($d['base_hpp_estimate'] ?? 0), (int)($d['suggested_price'] ?? 0),
            max(1, min(5, (int)($d['complexity_score'] ?? 3))), max(1, min(5, (int)($d['stock_fit_score'] ?? 3))),
        ]);
    }

    public function experiments(): array
    {
        if (!$this->tableExists('menu_experiment_plans')) return [];
        return $this->safeAll("SELECT * FROM menu_experiment_plans WHERE outlet_id=? ORDER BY FIELD(status,'running','planned','completed','stopped'), start_date DESC, id DESC LIMIT 10", [$this->outletId()]);
    }

    public function saveExperiment(array $d): void
    {
        $start = $d['start_date'] ?: today();
        $end = $d['end_date'] ?: (new DateTime($start))->modify('+6 days')->format('Y-m-d');
        $this->execSql("INSERT INTO menu_experiment_plans (outlet_id,experiment_name,source_keyword,start_date,end_date,target_orders_per_day,target_margin_pct,estimated_hpp,suggested_price,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [
            $this->outletId(), trim($d['experiment_name']), trim($d['source_keyword'] ?? ''),
            $start, $end, max(0, (int)($d['target_orders_per_day'] ?? 15)),
            max(0, (float)($d['target_margin_pct'] ?? 35)), max(0, (int)($d['estimated_hpp'] ?? 0)),
            max(0, (int)($d['suggested_price'] ?? 0)), 'planned', trim($d['notes'] ?? ''),
        ]);
    }

    public function updateExperimentStatus(int $id, string $status, string $decision): void
    {
        if (!in_array($status, ['planned','running','completed','stopped'], true)) $status = 'running';
        if (!in_array($decision, ['pending','make_permanent','continue_test','stop'], true)) $decision = 'pending';
        $this->execSql("UPDATE menu_experiment_plans SET status=?, decision=? WHERE id=? AND outlet_id=?", [$status, $decision, $id, $this->outletId()]);
    }

    // ── Targets ────────────────────────────────────────────────
    public function targets(): array
    {
        return $this->safeAll("SELECT * FROM business_targets WHERE outlet_id=? ORDER BY period_start DESC LIMIT 20", [$this->outletId()]);
    }

    public function storeTarget(array $d): void
    {
        $this->execSql("INSERT INTO business_targets (outlet_id,target_type,period_start,period_end,target_revenue,target_net_profit,target_transactions,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())", [
            $this->outletId(), $d['target_type'] ?? 'monthly', $d['period_start'], $d['period_end'],
            (float)$d['target_revenue'], (float)($d['target_net_profit'] ?? 0), (int)($d['target_transactions'] ?? 0), trim($d['notes'] ?? '')
        ]);
    }

    // ── Computed KPIs ──────────────────────────────────────────
    public function computeAll(string $from, string $to): array
    {
        $this->ensureTables();
        $out = $this->outletId();
        $today = today();

        // Settings
        $startDate = $this->setting('business_start_date', '2026-05-17');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !strtotime($startDate)) $startDate = $today;
        $workingDays = max(1, (int)$this->setting('projection_working_days_month', '30'));
        $dailyTarget = max(0, (int)$this->setting('daily_sales_target', '1000000'));
        $reservePct = max(0, (float)$this->setting('owner_reserve_percent', '5'));
        $roiPaybackPct = max(0, (float)$this->setting('roi_payback_percent', '15'));
        $growthConservative = (float)$this->setting('growth_conservative_pct', '0');
        $growthBase = (float)$this->setting('growth_base_pct', '8');
        $growthAggressive = (float)$this->setting('growth_aggressive_pct', '18');

        // Capital & finance
        $activeCapital = $this->activeCapital();
        $period = $this->financeSummary($from, $to);
        $cumulative = $this->financeSummary($startDate, $today);
        $todayFin = $this->financeSummary($today, $today);
        $daily = $this->dailyRows($from, $to);
        $activeDays = $this->activeDays($from, $to);
        $cumActiveDays = $this->activeDays($startDate, $today);

        // Averages per active day
        $avgSalesActive = $period['gross_sales'] / $activeDays;
        $avgHppActive = $period['hpp'] / $activeDays;
        $avgGrossActive = $period['gross_profit'] / $activeDays;
        $avgExpActive = $period['expenses'] / $activeDays;
        $avgNetActive = $period['net_profit'] / $activeDays;
        $avgOrdersActive = $period['paid_orders'] / $activeDays;
        $avgTicket = $period['paid_orders'] > 0 ? $period['gross_sales'] / $period['paid_orders'] : 0;

        // Margins & ratios
        $grossMargin = $period['gross_sales'] > 0 ? $period['gross_profit'] / $period['gross_sales'] * 100 : 0;
        $netMargin = $period['gross_sales'] > 0 ? $period['net_profit'] / $period['gross_sales'] * 100 : 0;
        $hppRatio = $period['gross_sales'] > 0 ? $period['hpp'] / $period['gross_sales'] * 100 : 0;
        $expenseRatio = $period['gross_sales'] > 0 ? $period['expenses'] / $period['gross_sales'] * 100 : 0;

        // ROI & BEP
        $cumNet = (float)$cumulative['net_profit'];
        $remaining = max(0, $activeCapital - $cumNet);
        $roiPct = $activeCapital > 0 ? ($cumNet / $activeCapital * 100) : 0;
        $profitAfterBep = max(0, $cumNet - $activeCapital);
        $daysToBep = ($avgNetActive > 0 && $remaining > 0) ? (int)ceil($remaining / $avgNetActive) : 0;
        $bepDate = $daysToBep > 0 ? (new DateTime($today))->modify('+' . $daysToBep . ' days')->format('Y-m-d') : ($activeCapital > 0 && $cumNet >= $activeCapital ? $today : null);
        $dailyOperationalBep = ($period['gross_sales'] > 0 && $period['gross_profit'] > 0) ? (int)ceil($avgExpActive / ($period['gross_profit'] / $period['gross_sales'])) : 0;
        $todayOperationalProfit = $todayFin['gross_sales'] - $todayFin['hpp'] - $todayFin['expenses'];

        // Target 6-month ROI
        $targetProfitDaily6m = $remaining > 0 ? $remaining / 180 : 0;
        $grossMarginDecimal = $period['gross_sales'] > 0 ? $period['gross_profit'] / $period['gross_sales'] : 0;
        $targetSalesDaily6m = ($targetProfitDaily6m > 0 && $grossMarginDecimal > 0) ? (int)ceil(($targetProfitDaily6m + $avgExpActive) / $grossMarginDecimal) : 0;
        $targetOrdersDaily6m = ($targetSalesDaily6m > 0 && $avgTicket > 0) ? $targetSalesDaily6m / $avgTicket : 0;

        // Momentum & growth
        $last7 = $this->periodFinanceByDays($today, 7);
        $prev7 = $this->prevPeriodFinance($today, 7);
        $last3 = $this->periodFinanceByDays($today, 3);
        $prev3 = $this->prevPeriodFinance($today, 3);
        $growth7 = $this->growthPct($last7['gross_sales'], $prev7['gross_sales']);
        $growth3 = $this->growthPct($last3['gross_sales'], $prev3['gross_sales']);
        $momentumScore = max(0, min(100, 50 + ($growth7 * 0.7) + ($growth3 * 0.3)));

        // Trends
        $trendRows = $this->trendRecommendations();
        $trendScore = count($trendRows) > 0 ? array_sum(array_map(fn($r) => (float)$r['final_score'], $trendRows)) / count($trendRows) : 0;
        $trendBoost = max(-0.05, min(0.18, ($trendScore - 50) / 250));

        // Forecast
        $baseGrowthRate = ($growthBase / 100) + $trendBoost;
        $conservativeRate = ($growthConservative / 100) + max(0, $trendBoost / 2);
        $aggressiveRate = ($growthAggressive / 100) + max(0, $trendBoost);
        $forecast = [];
        foreach (['conservative' => $conservativeRate, 'base' => $baseGrowthRate, 'aggressive' => $aggressiveRate] as $label => $rate) {
            $s = $avgSalesActive * $workingDays * (1 + $rate);
            $n = $avgNetActive * $workingDays * (1 + $rate);
            $forecast[$label] = ['sales30' => $s, 'net30' => $n, 'bepDays' => ($n > 0 && $remaining > 0) ? ceil($remaining / ($avgNetActive * (1 + $rate))) : 0];
        }

        // Cash allocation
        $cashHpp = (int)$todayFin['hpp'];
        $cashOp = (int)$todayFin['expenses'];
        $cashReserve = (int)round($todayFin['gross_sales'] * $reservePct / 100);
        $cashRoi = (int)round($todayFin['gross_sales'] * $roiPaybackPct / 100);
        $safeDraw = max(0, (int)$todayFin['gross_sales'] - $cashHpp - $cashOp - $cashReserve - $cashRoi);

        // Health score
        $healthScore = 0;
        $healthScore += min(25, max(0, $roiPct / 4));
        $healthScore += ($avgSalesActive > 0 && $dailyTarget > 0) ? min(20, ($avgSalesActive / $dailyTarget) * 20) : 0;
        $healthScore += max(0, min(20, $netMargin));
        $healthScore += max(0, min(15, 100 - $hppRatio)) / 100 * 15;
        $healthScore += max(0, min(10, 100 - $expenseRatio)) / 100 * 10;
        $healthScore += min(10, $trendScore / 10);
        $healthScore = round(max(0, min(100, $healthScore)));

        // Decision engine
        $decisionClass = 'success'; $decisionTitle = 'Bisnis dalam jalur sehat'; $decisionText = 'Pertahankan kontrol HPP, kualitas produk, dan dorong average ticket lewat paket/combo.';
        if ($period['gross_sales'] <= 0) { $decisionClass = 'danger'; $decisionTitle = 'Belum ada omzet pada periode ini'; $decisionText = 'Pastikan toko beroperasi, kasir input semua transaksi, dan jalankan promo pembuka penjualan.'; }
        elseif ($avgSalesActive < $dailyTarget) { $decisionClass = 'warning'; $decisionTitle = 'Omzet aktif masih di bawah target operasional'; $decisionText = 'Rata-rata omzet aktif ' . rupiah($avgSalesActive) . ' masih di bawah target ' . rupiah($dailyTarget) . '. Fokus ke paket kombo, upsell minuman, dan jam sore.'; }
        elseif ($netMargin < 10) { $decisionClass = 'warning'; $decisionTitle = 'Margin bersih masih tipis'; $decisionText = 'Omzet cukup, tetapi margin bersih ' . number_format($netMargin, 2, ',', '.') . '% perlu diperbaiki. Kontrol porsi, saus, packaging, dan biaya operasional.'; }
        if ($avgNetActive <= 0 && $period['gross_sales'] > 0) { $decisionClass = 'danger'; $decisionTitle = 'Laba bersih aktif belum positif'; $decisionText = 'Penjualan berjalan, tetapi laba bersih masih nol/minus. Cek HPP, gaji harian, dan pengeluaran rutin.'; }

        // Expansion readiness
        $expansionStatus = 'Belum layak ekspansi'; $expansionClass = 'warning';
        if ($roiPct >= 80 && $avgNetActive > 0 && $netMargin >= 15 && $avgSalesActive >= $dailyTarget && $healthScore >= 75) { $expansionStatus = 'Siap studi ekspansi'; $expansionClass = 'success'; }
        elseif ($roiPct >= 50 && $avgNetActive > 0) { $expansionStatus = 'Pra-ekspansi: kuatkan SOP'; }
        elseif ($healthScore < 45) { $expansionStatus = 'Fokus stabilisasi outlet'; $expansionClass = 'danger'; }

        // Impact simulations
        $impactSims = [
            ['title' => 'Omzet naik 10%', 'sales30' => $avgSalesActive * 1.10 * $workingDays, 'net30' => $avgNetActive * 1.10 * $workingDays, 'bepDays' => ($avgNetActive * 1.10 > 0 && $remaining > 0) ? ceil($remaining / ($avgNetActive * 1.10)) : 0, 'note' => 'Efek dari promo terarah, kombo sore, dan upsell minuman.'],
            ['title' => 'HPP turun 5%', 'sales30' => $avgSalesActive * $workingDays, 'net30' => ($avgNetActive + ($avgSalesActive * 0.05)) * $workingDays, 'bepDays' => (($avgNetActive + ($avgSalesActive * 0.05)) > 0 && $remaining > 0) ? ceil($remaining / ($avgNetActive + ($avgSalesActive * 0.05))) : 0, 'note' => 'Efek dari kontrol gramasi, saus, tepung, dan packaging.'],
            ['title' => 'Avg ticket naik 8%', 'sales30' => ($avgSalesActive * 1.08) * $workingDays, 'net30' => ($avgNetActive + ($avgSalesActive * 0.08 * $netMargin / 100)) * $workingDays, 'bepDays' => (($avgNetActive + ($avgSalesActive * 0.08 * $netMargin / 100)) > 0 && $remaining > 0) ? ceil($remaining / ($avgNetActive + ($avgSalesActive * 0.08 * $netMargin / 100))) : 0, 'note' => 'Efek dari bundling ayam+nasi+minuman dan add-on saus.'],
        ];

        // Expansion checklist
        $expansionChecklist = [
            ['label' => 'ROI minimal 70%', 'ok' => $roiPct >= 70],
            ['label' => 'Laba bersih aktif positif', 'ok' => $avgNetActive > 0],
            ['label' => 'Margin bersih minimal 15%', 'ok' => $netMargin >= 15],
            ['label' => 'Omzet aktif mencapai target', 'ok' => $avgSalesActive >= $dailyTarget],
            ['label' => 'HPP di bawah 58%', 'ok' => $hppRatio <= 58],
            ['label' => 'Stok kritis terkendali', 'ok' => true], // Updated below with inventory
            ['label' => 'Trend score menu mendukung', 'ok' => $trendScore >= 55],
        ];

        // Commands today
        $commands = [];
        if ($todayFin['gross_sales'] < $dailyTarget) {
            $gap = max(0, $dailyTarget - $todayFin['gross_sales']);
            $commands[] = 'Kejar omzet hari ini minimal ' . rupiah($gap) . ' lagi agar melewati target dasar.';
        } else {
            $commands[] = 'Target omzet dasar sudah tercapai. Fokus pertahankan margin dan upsell menu bernilai tinggi.';
        }
        if ($hppRatio > 58) $commands[] = 'Tahan diskon besar dan audit pemakaian bahan karena rasio HPP sedang tinggi.';
        elseif ($netMargin < 12) $commands[] = 'Naikkan average ticket melalui paket kombo, nasi, saus tambahan, dan minuman.';
        else $commands[] = 'Margin relatif aman. Push paket dengan gross profit tertinggi untuk mempercepat ROI.';
        if ($trendScore >= 65) $commands[] = 'Pilih 1 ide menu tren untuk eksperimen 7 hari, jangan langsung menambah banyak menu permanen.';
        else $commands[] = 'Trend score belum kuat. Lanjutkan pantau tren dan utamakan menu internal paling profitable.';

        // Owner actions
        $ownerActions = [];
        if ($avgSalesActive < $dailyTarget) $ownerActions[] = ['prio' => 'HIGH', 'title' => 'Kejar omzet aktif', 'text' => 'Rata-rata omzet aktif masih di bawah target. Fokus ke jam ramai, paket kombo, upsell minuman, dan push produk margin tinggi.'];
        if ($hppRatio > 58) $ownerActions[] = ['prio' => 'HIGH', 'title' => 'Audit HPP & gramasi', 'text' => 'Rasio HPP melewati 58%. Cek pemakaian tepung, saus, packaging, porsi ayam, dan update resep agar margin tidak terkikis.'];
        if ($netMargin < 12 && $period['gross_sales'] > 0) $ownerActions[] = ['prio' => 'MED', 'title' => 'Perbaiki margin bersih', 'text' => 'Margin bersih masih tipis. Naikkan average ticket lewat paket ayam+nasi+minuman, kurangi diskon tanpa minimum pembelian.'];
        if ($trendScore >= 65) $ownerActions[] = ['prio' => 'MED', 'title' => 'Jalankan eksperimen menu 7 hari', 'text' => 'Sinyal tren menu cukup kuat. Pilih satu varian dengan stock-fit tinggi, tetapkan HPP, harga, target order, dan evaluasi setelah 7 hari.'];
        if ($roiPct >= 70 && $avgNetActive > 0 && $netMargin >= 15) $ownerActions[] = ['prio' => 'LOW', 'title' => 'Siapkan pra-ekspansi', 'text' => 'Kondisi sudah mulai sehat. Dokumentasikan SOP, resep baku, standar stok, dan simulasi outlet kedua sebelum ekspansi.'];
        if (!$ownerActions) $ownerActions[] = ['prio' => 'INFO', 'title' => 'Kumpulkan data lebih panjang', 'text' => 'Data belum cukup kuat untuk keputusan besar. Gunakan minimal 14-30 hari aktif agar proyeksi dan tren lebih presisi.'];

        return compact(
            'startDate', 'workingDays', 'dailyTarget', 'reservePct', 'roiPaybackPct',
            'growthConservative', 'growthBase', 'growthAggressive',
            'activeCapital', 'period', 'cumulative', 'todayFin', 'daily',
            'activeDays', 'avgSalesActive', 'avgHppActive', 'avgGrossActive', 'avgExpActive', 'avgNetActive',
            'avgOrdersActive', 'avgTicket', 'grossMargin', 'netMargin', 'hppRatio', 'expenseRatio',
            'cumNet', 'remaining', 'roiPct', 'profitAfterBep', 'daysToBep', 'bepDate',
            'dailyOperationalBep', 'todayOperationalProfit',
            'targetSalesDaily6m', 'targetOrdersDaily6m',
            'growth7', 'growth3', 'momentumScore', 'trendScore', 'trendBoost',
            'trendRows', 'forecast', 'impactSims',
            'cashHpp', 'cashOp', 'cashReserve', 'cashRoi', 'safeDraw',
            'healthScore', 'decisionClass', 'decisionTitle', 'decisionText',
            'expansionStatus', 'expansionClass', 'expansionChecklist',
            'commands', 'ownerActions'
        );
    }

    // ── Private helpers ────────────────────────────────────────
    private function periodFinanceByDays(string $to, int $days): array
    {
        $end = new DateTime($to);
        $start = (clone $end)->modify('-' . ($days - 1) . ' days');
        return $this->financeSummary($start->format('Y-m-d'), $end->format('Y-m-d'));
    }

    private function prevPeriodFinance(string $to, int $days): array
    {
        $end = (new DateTime($to))->modify('-' . $days . ' days');
        $start = (clone $end)->modify('-' . ($days - 1) . ' days');
        return $this->financeSummary($start->format('Y-m-d'), $end->format('Y-m-d'));
    }

    private function growthPct(float $current, float $prev): float
    {
        if ($prev <= 0 && $current > 0) return 100;
        if ($prev <= 0) return 0;
        return (($current - $prev) / $prev) * 100;
    }
}

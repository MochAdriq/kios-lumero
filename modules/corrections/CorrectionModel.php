<?php
class CorrectionModel extends Model
{
    private function outletId(): int
    {
        if (function_exists('current_outlet_id')) {
            return current_outlet_id();
        }
        $user = Auth::user() ?? [];
        return (int)($user['outlet_id'] ?? 1) ?: 1;
    }

    // ── Table Safety ──────────────────────────────────────

    public function ensureTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS stock_corrections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            outlet_id INT NOT NULL,
            correction_type ENUM('order_void','order_adjust','stock_addition','stock_reduction') NOT NULL,
            reference_type VARCHAR(50) NULL,
            reference_id INT NULL,
            raw_material_id INT NULL,
            qty DECIMAL(12,4) NOT NULL DEFAULT 0,
            old_value DECIMAL(12,4) NULL,
            new_value DECIMAL(12,4) NULL,
            reason TEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_correction_outlet (outlet_id),
            KEY idx_correction_type (correction_type),
            KEY idx_correction_ref (reference_type, reference_id),
            KEY idx_correction_date (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // ── Order Void ────────────────────────────────────────

    /**
     * Get orders that can be voided (paid, not already voided).
     */
    public function voidableOrders(int $outletId, int $limit = 50): array
    {
        return $this->all("
            SELECT o.*, 
                   COALESCE(p.payment_method, '-') AS payment_method,
                   u.name AS cashier_name
            FROM orders o
            LEFT JOIN payments p ON p.order_id = o.id
            LEFT JOIN users u ON u.id = o.cashier_id
            WHERE o.outlet_id = ?
              AND o.order_status IN ('completed', 'paid')
              AND o.order_status != 'voided'
            ORDER BY o.created_at DESC
            LIMIT {$limit}
        ", [$outletId]);
    }

    /**
     * Get full order details including items.
     */
    public function getOrder(int $orderId): ?array
    {
        $order = $this->one("
            SELECT o.*, 
                   COALESCE(p.payment_method, '-') AS payment_method,
                   u.name AS cashier_name
            FROM orders o
            LEFT JOIN payments p ON p.order_id = o.id
            LEFT JOIN users u ON u.id = o.cashier_id
            WHERE o.id = ?
            LIMIT 1
        ", [$orderId]);

        if (!$order) return null;

        $order['items'] = $this->all("
            SELECT oi.*, pv.sku
            FROM order_items oi
            LEFT JOIN product_variants pv ON pv.id = oi.product_variant_id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ", [$orderId]);

        return $order;
    }

    /**
     * Void an entire order and reverse backflush.
     */
    public function voidOrder(int $orderId, string $reason, int $userId): void
    {
        $this->ensureTable();
        $outletId = $this->outletId();

        $order = $this->getOrder($orderId);
        if (!$order) {
            throw new RuntimeException('Order tidak ditemukan.');
        }
        if ($order['order_status'] === 'voided') {
            throw new RuntimeException('Order sudah di-void sebelumnya.');
        }
        if ((int)$order['outlet_id'] !== $outletId) {
            throw new RuntimeException('Order bukan milik outlet ini.');
        }

        $this->db->beginTransaction();
        try {
            // 1. Update order status
            $this->execSql(
                "UPDATE orders SET order_status = 'voided', payment_status = 'refunded', updated_at = ? WHERE id = ?",
                [now(), $orderId]
            );

            // 2. Update payment status
            $this->execSql(
                "UPDATE payments SET status = 'refunded', updated_at = ? WHERE order_id = ?",
                [now(), $orderId]
            );

            // 3. Reverse backflush — return raw materials to stock
            $this->reverseBackflush($order, $userId, $outletId);

            // 4. Log correction
            $this->execSql(
                "INSERT INTO stock_corrections (outlet_id, correction_type, reference_type, reference_id, qty, old_value, new_value, reason, created_by, created_at)
                 VALUES (?, 'order_void', 'order', ?, ?, ?, ?, ?, ?, ?)",
                [
                    $outletId, $orderId,
                    (float)$order['grand_total'],
                    null, null,
                    $reason, $userId, now()
                ]
            );

            // 5. Reverse product lifetime qty sold
            foreach ($order['items'] as $item) {
                if ((int)($item['product_variant_id'] ?? 0) > 0) {
                    $pv = $this->one("SELECT product_id FROM product_variants WHERE id = ?", [(int)$item['product_variant_id']]);
                    if ($pv) {
                        $this->execSql(
                            "UPDATE products SET lifetime_qty_sold = GREATEST(0, lifetime_qty_sold - ?), updated_at = ? WHERE id = ?",
                            [(float)$item['qty'], now(), (int)$pv['product_id']]
                        );
                    }
                }
            }

            // 6. Audit log
            Audit::log('void_order', 'orders', $orderId, [
                'order_number' => $order['order_number'],
                'grand_total'  => $order['grand_total'],
            ], [
                'status'  => 'voided',
                'reason'  => $reason,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Reverse backflush: return raw materials deducted during the original sale.
     */
    private function reverseBackflush(array $order, int $userId, int $outletId): void
    {
        require_once __DIR__ . '/../recipes/RecipeModel.php';
        $recipeModel = new RecipeModel();

        foreach ($order['items'] as $item) {
            $variantId = (int)($item['product_variant_id'] ?? 0);
            $qty = (float)($item['qty'] ?? 0);
            if ($variantId <= 0 || $qty <= 0) continue;

            // Get BOM for this variant
            $recipe = $this->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$variantId]);
            if (!$recipe) continue;

            $bom = $recipeModel->explodeBOM((int)$recipe['id'], $qty);
            if (empty($bom)) continue;

            foreach ($bom as $rmId => $qtyToReturn) {
                if ($qtyToReturn <= 0) continue;

                // Get current stock per outlet
                $rm = function_exists('inventory_get_material_stock') ? inventory_get_material_stock($this->db, $rmId, $outletId) : $this->one("SELECT stock_qty, average_cost FROM raw_materials WHERE id = ?", [$rmId]);
                if (!$rm) continue;

                $oldStock = (float)$rm['stock_qty'];
                $newStock = $oldStock + $qtyToReturn;

                // Return stock
                if (function_exists('inventory_set_material_stock')) {
                    inventory_set_material_stock($this->db, $rmId, $newStock, (float)($rm['average_cost'] ?? 0), $outletId);
                } else {
                    $this->execSql(
                        "UPDATE raw_materials SET stock_qty = stock_qty + ?, updated_at = ? WHERE id = ?",
                        [$qtyToReturn, now(), $rmId]
                    );
                }

                // Log inventory movement as correction
                $this->execSql(
                    "INSERT INTO inventory_movements (outlet_id, raw_material_id, movement_type, qty, unit_cost, total_cost, reference_type, reference_id, notes, created_by, created_at)
                     VALUES (?, ?, 'correction_in', ?, ?, ?, 'order_void', ?, ?, ?, ?)",
                    [
                        $outletId, $rmId, $qtyToReturn,
                        (float)$rm['average_cost'],
                        $qtyToReturn * (float)$rm['average_cost'],
                        (int)$order['id'],
                        'Pengembalian stok dari void order #' . ($order['order_number'] ?? $order['id']),
                        $userId, now()
                    ]
                );

                // Log per-material correction
                $this->execSql(
                    "INSERT INTO stock_corrections (outlet_id, correction_type, reference_type, reference_id, raw_material_id, qty, old_value, new_value, reason, created_by, created_at)
                     VALUES (?, 'order_void', 'raw_material', ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $outletId, $rmId, $rmId, $qtyToReturn,
                        $oldStock, $newStock,
                        'Reverse backflush: void order #' . ($order['order_number'] ?? $order['id']),
                        $userId, now()
                    ]
                );
            }
        }
    }

    // ── Stock Correction ──────────────────────────────────

    /**
     * List raw materials for the stock correction form.
     */
    public function rawMaterials(): array
    {
        if (function_exists('inventory_ensure_outlet_stocks')) inventory_ensure_outlet_stocks($this->db);
        $outletId = $this->outletId();
        return $this->all("
            SELECT rm.id, rm.name, rm.sku,
                   COALESCE(orm.stock_qty, rm.stock_qty, 0) AS stock_qty,
                   COALESCE(orm.average_cost, rm.average_cost, 0) AS average_cost,
                   rmc.name AS category_name, u.symbol AS unit_symbol
            FROM raw_materials rm
            LEFT JOIN raw_material_categories rmc ON rmc.id = rm.category_id
            LEFT JOIN units u ON u.id = rm.unit_id
            LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ?
            WHERE rm.is_active = 1 AND rm.outlet_id = ?
            ORDER BY rmc.sort_order, rm.name
        ", [$outletId, $outletId]);
    }
    public function materials(): array { return $this->rawMaterials(); }

    /**
     * Apply a manual stock correction.
     */
    public function correctStock(int $rawMaterialId, string $type, float $qty, string $reason, int $userId): void
    {
        $this->ensureTable();
        $outletId = $this->outletId();

        if (!in_array($type, ['stock_addition', 'stock_reduction'], true)) {
            throw new RuntimeException('Tipe koreksi tidak valid.');
        }
        if ($qty <= 0) {
            throw new RuntimeException('Jumlah koreksi harus lebih dari 0.');
        }
        if (trim($reason) === '') {
            throw new RuntimeException('Alasan koreksi wajib diisi.');
        }

        $rm = function_exists('inventory_get_material_stock') ? inventory_get_material_stock($this->db, $rawMaterialId, $outletId) : $this->one("SELECT id, name, stock_qty, average_cost FROM raw_materials WHERE id = ? AND is_active = 1", [$rawMaterialId]);
        if (!$rm) {
            throw new RuntimeException('Bahan baku tidak ditemukan.');
        }

        $oldStock = (float)$rm['stock_qty'];
        $movementType = $type === 'stock_addition' ? 'correction_in' : 'correction_out';
        $sign = $type === 'stock_addition' ? 1 : -1;
        $newStock = $oldStock + ($qty * $sign);

        if ($newStock < 0) {
            throw new RuntimeException('Stok tidak cukup untuk pengurangan. Stok saat ini: ' . number_format($oldStock, 2));
        }

        $this->db->beginTransaction();
        try {
            // 1. Update stock
            if (function_exists('inventory_set_material_stock')) {
                inventory_set_material_stock($this->db, $rawMaterialId, $newStock, (float)($rm['average_cost'] ?? 0), $outletId);
            } else {
                $this->execSql(
                    "UPDATE raw_materials SET stock_qty = ?, updated_at = ? WHERE id = ?",
                    [$newStock, now(), $rawMaterialId]
                );
            }

            // 2. Insert inventory movement
            $this->execSql(
                "INSERT INTO inventory_movements (outlet_id, raw_material_id, movement_type, qty, unit_cost, total_cost, reference_type, reference_id, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'stock_correction', NULL, ?, ?, ?)",
                [
                    $outletId, $rawMaterialId, $movementType, $qty,
                    (float)$rm['average_cost'],
                    $qty * (float)$rm['average_cost'],
                    $reason, $userId, now()
                ]
            );

            // 3. Insert stock correction log
            $this->execSql(
                "INSERT INTO stock_corrections (outlet_id, correction_type, reference_type, reference_id, raw_material_id, qty, old_value, new_value, reason, created_by, created_at)
                 VALUES (?, ?, 'raw_material', ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $outletId, $type, $rawMaterialId, $rawMaterialId,
                    $qty, $oldStock, $newStock,
                    $reason, $userId, now()
                ]
            );

            // 4. Audit log
            Audit::log(
                $type === 'stock_addition' ? 'stock_correction_add' : 'stock_correction_reduce',
                'raw_materials', $rawMaterialId,
                ['stock_qty' => $oldStock],
                ['stock_qty' => $newStock, 'reason' => $reason]
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── History ───────────────────────────────────────────

    /**
     * Get recent correction history.
     */
    public function recentCorrections(int $limit = 100): array
    {
        $this->ensureTable();
        $outletId = $this->outletId();

        return $this->all("
            SELECT sc.*,
                   u.name AS user_name,
                   rm.name AS material_name,
                   rm.sku AS material_sku,
                   o.order_number
            FROM stock_corrections sc
            LEFT JOIN users u ON u.id = sc.created_by
            LEFT JOIN raw_materials rm ON rm.id = sc.raw_material_id
            LEFT JOIN orders o ON o.id = sc.reference_id AND sc.reference_type = 'order'
            WHERE sc.outlet_id = ?
            ORDER BY sc.created_at DESC
            LIMIT {$limit}
        ", [$outletId]);
    }
}

<?php
class POSModel extends Model
{
    public function outletId(): int
    {
        if (function_exists('current_outlet_id')) {
            return current_outlet_id();
        }

        $u = Auth::user() ?? [];
        return (int)($u['outlet_id'] ?? 1) ?: 1;
    }

    public function activeSession(int $outletId): ?array
    {
        return $this->one("SELECT * FROM daily_store_sessions WHERE outlet_id=? AND business_date=? AND status='open' ORDER BY id DESC LIMIT 1", [$outletId, business_date($outletId)]);
    }

    public function categoriesWithProducts(int $outletId): array
    {
        $pcScope = ['sql' => 'pc.outlet_id = ?', 'params' => [$outletId]];
        $categories = $this->all("SELECT pc.id, pc.name, pc.slug, pc.sort_order
            FROM product_categories pc
            WHERE pc.is_active=1 AND {$pcScope['sql']}
            ORDER BY CASE WHEN pc.sort_order = 0 THEN 999 ELSE pc.sort_order END ASC, pc.name ASC", $pcScope['params']);
        $out = [];
        if (!$categories) return [];
        
        require_once __DIR__ . '/../recipes/RecipeModel.php';
        $mRecipe = new RecipeModel();

        foreach ($categories as $cat) {
            $pScope = ['sql' => 'p.outlet_id = ?', 'params' => [$outletId]];
            $pvScope = ['sql' => 'pv.outlet_id = ?', 'params' => [$outletId]];
            $pcScope = ['sql' => 'pc.outlet_id = ?', 'params' => [$outletId]];
            $items = $this->all("SELECT
                    pv.id AS variant_id,
                    pv.sku,
                    p.name AS product_name,
                    pv.variant_name,
                    COALESCE(NULLIF(pv.selling_price,0), p.base_price, 0) AS price,
                    COALESCE(pv.hpp, p.base_hpp, 0) AS hpp,
                    COALESCE(pv.image, p.image) AS image
                FROM product_variants pv
                JOIN products p ON p.id=pv.product_id
                JOIN product_categories pc ON pc.id=p.category_id
                WHERE p.category_id=? AND p.is_active=1
                    AND pv.is_active = 1
                    AND pc.is_active=1
                    AND {$pScope['sql']}
                    AND {$pvScope['sql']}
                    AND {$pcScope['sql']}
                ORDER BY p.name ASC, pv.is_default DESC, pv.variant_name ASC", array_merge([$cat['id']], $pScope['params'], $pvScope['params'], $pcScope['params']));
            
            if ($items) {
                foreach ($items as &$it) {
                    $it['ready_stock'] = $mRecipe->calculateMaxYield((int)$it['variant_id']);
                }
                $cat['items'] = $items;
                $out[] = $cat;
            }
        }
        return $out;
    }

    public function nextOrderNumber(): string
    {
        $max = (int)$this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(order_number,4) AS UNSIGNED)),1999) FROM orders WHERE order_number REGEXP '^DCK[0-9]+$'")->fetchColumn();
        return 'DCK' . max(2000, $max + 1);
    }

    public function createOrder(array $payload): array
    {
        $outletId = $this->outletId();
        $session = $this->activeSession($outletId);
        if (!$session) {
            throw new RuntimeException('Toko belum dibuka. Silakan buka toko terlebih dahulu sebelum transaksi POS.');
        }

        $items = $payload['items'] ?? [];
        if (!$items || !is_array($items)) {
            throw new RuntimeException('Keranjang masih kosong.');
        }

        $paymentMethod = $payload['payment_method'] ?? 'cash';
        $allowedPayments = ['cash','debit','credit','qris','ewallet','bank_transfer','other'];
        if (!in_array($paymentMethod, $allowedPayments, true)) $paymentMethod = 'cash';

        $orderSource = $payload['order_source'] ?? 'cashier';
        $allowedSources = ['cashier','self_order','gofood','grabfood','shopeefood','manual'];
        if (!in_array($orderSource, $allowedSources, true)) $orderSource = 'cashier';

        $orderType = $payload['order_type'] ?? 'takeaway';
        if (!in_array($orderType, ['dine_in','takeaway','delivery'], true)) $orderType = 'takeaway';

        $discount = max(0, (float)($payload['discount_amount'] ?? 0));
        $cashReceived = max(0, (float)($payload['paid_amount'] ?? 0));
        $taxPercent = 0;
        $servicePercent = (float)$this->setting($outletId, 'service_charge_percent', 0);

        $normalized = [];
        $subtotal = 0;
        $totalHpp = 0;

        foreach ($items as $it) {
            $variantId = (int)($it['variant_id'] ?? 0);
            $qty = max(0.001, (float)($it['qty'] ?? 1));
            $pScope = ['sql' => 'p.outlet_id = ?', 'params' => [$outletId]];
            $pvScope = ['sql' => 'pv.outlet_id = ?', 'params' => [$outletId]];
            $pcScope = ['sql' => 'pc.outlet_id = ?', 'params' => [$outletId]];
            $variant = $this->one("SELECT pv.*, p.name AS product_name, p.id AS product_id
                FROM product_variants pv
                JOIN products p ON p.id=pv.product_id
                JOIN product_categories pc ON pc.id=p.category_id
                WHERE pv.id=? AND pv.is_active=1 AND p.is_active=1 AND pc.is_active=1
                    AND {$pScope['sql']}
                    AND {$pvScope['sql']}
                    AND {$pcScope['sql']}
                LIMIT 1", array_merge([$variantId], $pScope['params'], $pvScope['params'], $pcScope['params']));
            if (!$variant) throw new RuntimeException('Produk tidak valid atau nonaktif.');

            $price = (float)$variant['selling_price'];
            $hpp = (float)$variant['hpp'];
            $lineTotal = $price * $qty;
            $lineHpp = $hpp * $qty;
            $normalized[] = [
                'variant_id' => $variantId,
                'product_id' => (int)$variant['product_id'],
                'product_name' => $variant['product_name'],
                'variant_name' => $variant['variant_name'],
                'qty' => $qty,
                'price' => $price,
                'hpp' => $hpp,
                'line_total' => $lineTotal,
                'line_hpp' => $lineHpp,
            ];
            $subtotal += $lineTotal;
            $totalHpp += $lineHpp;
        }

        $discount = min($discount, $subtotal);
        $taxable = max(0, $subtotal - $discount);
        $tax = 0;
        $service = round($taxable * $servicePercent / 100, 0);
        $grandTotal = max(0, $taxable + $tax + $service);
        if ($paymentMethod === 'cash' && $cashReceived < $grandTotal) {
            throw new RuntimeException('Uang diterima kurang dari total bayar.');
        }
        $paidAmount = $paymentMethod === 'cash' ? $cashReceived : $grandTotal;
        $change = $paymentMethod === 'cash' ? max(0, $paidAmount - $grandTotal) : 0;
        $grossProfit = $grandTotal - $tax - $service - $totalHpp;

        $this->db->beginTransaction();
        try {
            $orderNo = $this->nextOrderNumber();
            $stmt = $this->db->prepare("INSERT INTO orders
                (outlet_id,daily_store_session_id,customer_id,order_number,order_source,order_type,business_date,subtotal,discount_amount,tax_amount,service_amount,grand_total,total_hpp,gross_profit,payment_status,order_status,cashier_id,notes,created_at,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $orderBizDate = business_date($outletId);
            $stmt->execute([
                $outletId,(int)$session['id'],null,$orderNo,$orderSource,$orderType,$orderBizDate,$subtotal,$discount,$tax,$service,$grandTotal,$totalHpp,$grossProfit,
                'paid','completed',Auth::id(),($payload['notes'] ?? null),now(),now()
            ]);
            $orderId = (int)$this->db->lastInsertId();
            $bizDate = $orderBizDate;

            $itemStmt = $this->db->prepare("INSERT INTO order_items
                (order_id,product_variant_id,product_name_snapshot,variant_name_snapshot,qty,selling_price,discount_amount,tax_amount,subtotal,hpp_per_unit,total_hpp,gross_profit,notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

            foreach ($normalized as $ni) {
                $lineDiscount = $subtotal > 0 ? round($discount * ($ni['line_total'] / $subtotal), 2) : 0;
                $lineTax = 0;
                $lineProfit = ($ni['line_total'] - $lineDiscount) - $ni['line_hpp'];
                $itemStmt->execute([$orderId,$ni['variant_id'],$ni['product_name'],$ni['variant_name'],$ni['qty'],$ni['price'],$lineDiscount,$lineTax,$ni['line_total'],$ni['hpp'],$ni['line_hpp'],$lineProfit,null]);
                $orderItemId = (int)$this->db->lastInsertId();
                $this->deductDailyReadyStock($outletId, $ni['variant_id'], $ni['qty'], $ni['hpp'], $orderItemId);
                $this->execSql("UPDATE products SET lifetime_qty_sold = lifetime_qty_sold + ?, updated_at=? WHERE id=?", [$ni['qty'], now(), $ni['product_id']]);
            }

            $payStmt = $this->db->prepare("INSERT INTO payments
                (order_id,payment_method,provider,amount,paid_at,status,gateway_reference,gateway_payload,verified_by,verified_at,created_at,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $payStmt->execute([$orderId,$paymentMethod,null,$grandTotal,now(),'paid',null,null,Auth::id(),now(),now(),now()]);

            Audit::log('create_pos_order', 'orders', $orderId, null, ['order_number'=>$orderNo,'grand_total'=>$grandTotal,'payment_method'=>$paymentMethod]);

            try {
                require_once __DIR__ . '/../../config/loyalty.php';
                $memberId = !empty($payload['member_id']) ? (int)$payload['member_id'] : 0;
                $memberPhone = !empty($payload['customer_phone']) ? trim((string)$payload['customer_phone']) : null;
                $memberRow = null;
                if ($memberId > 0 && function_exists('loyalty_member_by_id')) {
                    $memberRow = loyalty_member_by_id($this->db, $memberId);
                    if ($memberRow) {
                        $this->db->prepare("UPDATE orders SET member_id=?, customer_phone=? WHERE id=?")->execute([$memberId, ($memberPhone ?: ($memberRow['phone'] ?? null)), $orderId]);
                    }
                }
                if (function_exists('loyalty_apply_order_after_insert')) {
                    loyalty_apply_order_after_insert($this->db, (int)$orderId, $memberRow, (int)$grandTotal, 0, 0, Auth::id());
                }
            } catch (Throwable $lx) {}

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
            return ['id'=>$orderId,'order_number'=>$orderNo,'grand_total'=>$grandTotal,'change'=>$change];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function deductDailyReadyStock(int $outletId, int $variantId, float $qty, float $hpp, int $orderItemId): void
    {
        $bizDate = business_date($outletId);
        require_once __DIR__ . '/../recipes/RecipeModel.php';
        $mRecipe = new RecipeModel();
        $mRecipe->backflushRawMaterials($variantId, $qty, $orderItemId, Auth::id(), $bizDate, $outletId);
    }

    public function recentOrders(int $outletId, int $limit = 50): array
    {
        return $this->all("SELECT o.*, COALESCE(p.payment_method,'-') AS payment_method
            FROM orders o
            LEFT JOIN payments p ON p.order_id=o.id
            WHERE o.outlet_id=?
            ORDER BY o.created_at DESC, o.id DESC
            LIMIT {$limit}", [$outletId]);
    }

    public function allOrders(int $outletId): array
    {
        return $this->all("SELECT o.*, COALESCE(p.payment_method,'-') AS payment_method
            FROM orders o
            LEFT JOIN payments p ON p.order_id=o.id
            WHERE o.outlet_id=?
            ORDER BY o.created_at DESC, o.id DESC", [$outletId]);
    }

    public function receipt(int $orderId, ?int $outletId = null): ?array
    {
        $params = [$orderId];
        $outletSql = '';
        if ($outletId !== null) {
            $outletSql = ' AND o.outlet_id=?';
            $params[] = $outletId;
        }
        $order = $this->one("SELECT o.*, p.payment_method, p.amount AS paid_amount, ou.name AS outlet_name, ou.address AS outlet_address
            FROM orders o
            LEFT JOIN payments p ON p.order_id=o.id
            LEFT JOIN outlets ou ON ou.id=o.outlet_id
            WHERE o.id=?{$outletSql} LIMIT 1", $params);
        if (!$order) return null;
        require_once __DIR__ . '/../../config/loyalty.php';
        if (empty($order['member_id']) && empty($order['loyalty_claim_code']) && ($order['grand_total'] ?? 0) >= 1000 && function_exists('loyalty_create_receipt_claim')) {
            $pts = max(1, (int)floor(($order['grand_total'] ?? 0) / 1000));
            loyalty_create_receipt_claim($this->db, (int)$order['id'], $pts);
            $order = $this->one("SELECT o.*, p.payment_method, p.amount AS paid_amount, ou.name AS outlet_name, ou.address AS outlet_address
                FROM orders o
                LEFT JOIN payments p ON p.order_id=o.id
                LEFT JOIN outlets ou ON ou.id=o.outlet_id
                WHERE o.id=?{$outletSql} LIMIT 1", $params);
        }
        $items = $this->all("SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC", [$orderId]);
        return ['order'=>$order,'items'=>$items];
    }

    public function waitingPayments(int $outletId): array
    {
        $posPayments = $this->all("SELECT o.*, p.id AS payment_id, p.payment_method, p.amount, p.status AS payment_real_status, 0 AS free_order_id, 0 AS is_free_order
            FROM payments p JOIN orders o ON o.id=p.order_id
            WHERE o.outlet_id=? AND p.status IN ('pending','waiting_verification')
            ORDER BY p.created_at ASC", [$outletId]);

        $freeOrders = $this->all("SELECT id AS free_order_id, 1 AS is_free_order, 0 AS payment_id, pre_order_no AS order_number, payment_method, total AS amount, payment_status AS payment_real_status, created_at, customer_name
            FROM free_orders
            WHERE payment_status IN ('unpaid','pending','waiting_verification') AND order_status <> 'cancelled'
            ORDER BY created_at ASC");

        $merged = array_merge($posPayments, $freeOrders);
        usort($merged, function($a, $b) {
            return strtotime($a['created_at'] ?? 'now') <=> strtotime($b['created_at'] ?? 'now');
        });
        return $merged;
    }

    public function verifyPayment(int $paymentId, ?int $outletId = null): void
    {
        $params = [$paymentId];
        $outletSql = '';
        if ($outletId !== null) {
            $outletSql = ' AND o.outlet_id=?';
            $params[] = $outletId;
        }
        $payment = $this->one("SELECT p.*, o.id AS oid FROM payments p JOIN orders o ON o.id=p.order_id WHERE p.id=?{$outletSql} LIMIT 1", $params);
        if (!$payment) throw new RuntimeException('Payment tidak ditemukan.');
        $this->execSql("UPDATE payments SET status='paid', verified_by=?, verified_at=?, paid_at=COALESCE(paid_at,?), updated_at=? WHERE id=?", [Auth::id(),now(),now(),now(),$paymentId]);
        $this->execSql("UPDATE orders SET payment_status='paid', order_status='completed', updated_at=? WHERE id=?", [now(),$payment['oid']]);
        Audit::log('verify_payment', 'payments', $paymentId, $payment, ['status'=>'paid']);
    }

    public function verifyFreeOrderPayment(int $freeOrderId, ?int $outletId = null): void
    {
        $fo = $this->one("SELECT * FROM free_orders WHERE id=? LIMIT 1", [$freeOrderId]);
        if (!$fo) throw new RuntimeException('Order Online tidak ditemukan.');

        $existOrder = $this->one("SELECT id FROM orders WHERE order_number=? LIMIT 1", [$fo['pre_order_no']]);
        if ($existOrder) {
            if ($fo['payment_status'] === 'paid') {
                throw new RuntimeException('Order Online sudah diverifikasi dan masuk ke POS.');
            }
        }

        if ($fo['payment_status'] !== 'paid') {
            $this->execSql("UPDATE free_orders SET payment_status='paid', order_status='processing', updated_at=? WHERE id=?", [now(), $freeOrderId]);
            $fo['payment_status'] = 'paid';
        }

        $outletId = $outletId ?: (int)app_config('default_outlet_id', 1);
        $session = $this->one("SELECT id FROM daily_store_sessions WHERE outlet_id=? AND status='open' ORDER BY id DESC LIMIT 1", [$outletId]) ?: ['id' => 0];

        if (!$existOrder) {
            $stmt = $this->db->prepare("INSERT INTO orders
                (outlet_id,daily_store_session_id,customer_id,order_number,order_source,order_type,business_date,subtotal,discount_amount,tax_amount,service_amount,grand_total,total_hpp,gross_profit,payment_status,order_status,cashier_id,notes,created_at,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $orderBizDate = business_date($outletId);
            $subtotal = (int)$fo['subtotal'];
            $discount = (int)($fo['discount'] ?? 0);
            $total = (int)$fo['total'];
            $totalHpp = (int)($fo['total_hpp'] ?? 0);
            $grossProfit = $total - $totalHpp;
            $stmt->execute([
                $outletId,(int)$session['id'],null,$fo['pre_order_no'],'online_order',($fo['pickup_type'] ?: 'dine_in'),$orderBizDate,$subtotal,$discount,0,0,$total,$totalHpp,$grossProfit,
                'paid','processing',Auth::id(),($fo['customer_note'] ?? null),now(),now()
            ]);
            $orderId = (int)$this->db->lastInsertId();

            if (!empty($fo['member_id']) || !empty($fo['customer_phone'])) {
                try {
                    $this->execSql("UPDATE orders SET member_id=?, customer_phone=? WHERE id=?", [
                        ($fo['member_id'] ?: null), ($fo['customer_phone'] ?: null), $orderId
                    ]);
                } catch (Throwable $e) {}
            }

            $foItems = $this->all("SELECT * FROM free_order_items WHERE free_order_id=?", [$freeOrderId]);
            if ($foItems) {
                $itemStmt = $this->db->prepare("INSERT INTO order_items
                    (order_id,product_variant_id,product_name_snapshot,variant_name_snapshot,qty,selling_price,discount_amount,tax_amount,subtotal,hpp_per_unit,total_hpp,gross_profit,notes)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                foreach ($foItems as $ni) {
                    $lineDiscount = $subtotal > 0 ? round($discount * (($ni['line_total'] ?? 0) / $subtotal), 2) : 0;
                    $lineProfit = (($ni['line_total'] ?? 0) - $lineDiscount) - ($ni['line_hpp'] ?? 0);
                    $itemStmt->execute([
                        $orderId, (int)($ni['menu_item_id'] ?? 0), ($ni['item_name'] ?? 'Item Menu'), '', (int)($ni['qty'] ?? 1), (int)($ni['price'] ?? 0), $lineDiscount, 0, (int)($ni['line_total'] ?? 0), (int)($ni['hpp'] ?? 0), (int)($ni['line_hpp'] ?? 0), $lineProfit, null
                    ]);
                    $orderItemId = (int)$this->db->lastInsertId();
                    $this->deductDailyReadyStock($outletId, (int)($ni['menu_item_id'] ?? 0), (float)($ni['qty'] ?? 1), (float)($ni['hpp'] ?? 0), $orderItemId);
                    $this->execSql("UPDATE products SET lifetime_qty_sold = lifetime_qty_sold + ?, updated_at=? WHERE id= (SELECT product_id FROM product_variants WHERE id=? LIMIT 1)", [(int)($ni['qty'] ?? 1), now(), (int)($ni['menu_item_id'] ?? 0)]);
                }
            }

            $payStmt = $this->db->prepare("INSERT INTO payments
                (order_id,payment_method,provider,amount,paid_at,status,gateway_reference,gateway_payload,verified_by,verified_at,created_at,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $payStmt->execute([$orderId, ($fo['payment_method'] ?: 'qris'), null, $total, now(), 'paid', null, null, Auth::id(), now(), now(), now()]);

            try {
                require_once __DIR__ . '/../../config/loyalty.php';
                $memberRow = (!empty($fo['member_id']) && function_exists('loyalty_member_by_id')) ? loyalty_member_by_id($this->db, (int)$fo['member_id']) : null;
                if (function_exists('loyalty_apply_order_after_insert')) {
                    loyalty_apply_order_after_insert($this->db, (int)$orderId, $memberRow, (int)$total, (int)($fo['loyalty_points_redeemed'] ?? 0), (int)($fo['loyalty_redeem_amount'] ?? 0), Auth::id());
                }
            } catch (Throwable $lx) {}
        }

        Audit::log('verify_free_order_payment', 'free_orders', $freeOrderId, $fo, ['status'=>'paid']);
    }

    public function findMemberByPhone(string $phone)
    {
        require_once __DIR__ . '/../../config/loyalty.php';
        return loyalty_find_member_by_phone($this->db, $phone);
    }

    private function setting(int $outletId, string $key, $default = null)
    {
        $scope = outlet_scope_sql('outlet_id', $outletId);
        $row = $this->one("SELECT setting_value FROM system_settings WHERE setting_key=? AND {$scope['sql']} ORDER BY outlet_id DESC LIMIT 1", array_merge([$key], $scope['params']));
        return $row['setting_value'] ?? $default;
    }
}

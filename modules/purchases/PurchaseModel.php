<?php
class PurchaseModel extends Model
{
    private function outletId(): int { return function_exists('current_outlet_id') ? current_outlet_id() : ((int)(Auth::user()['outlet_id']??1) ?: 1); }
    public function list(string $from, string $to): array
    {
        return $this->all("SELECT po.*, v.name vendor_name,
               (SELECT GROUP_CONCAT(CONCAT(rm.name, ' (', CAST(poi.qty AS CHAR), ' ', COALESCE(u.symbol,''), ')') SEPARATOR ', ')
                FROM purchase_order_items poi
                JOIN raw_materials rm ON rm.id=poi.raw_material_id
                LEFT JOIN units u ON u.id=rm.unit_id
                WHERE poi.purchase_order_id=po.id) AS item_details
            FROM purchase_orders po LEFT JOIN vendors v ON v.id=po.vendor_id WHERE po.outlet_id=? AND po.purchase_date BETWEEN ? AND ? ORDER BY po.purchase_date DESC, po.id DESC LIMIT 200", [$this->outletId(), $from, $to]);
    }
    public function materials(): array { return $this->all("SELECT rm.*, u.symbol unit_symbol, c.name category_name FROM raw_materials rm JOIN units u ON u.id=rm.unit_id LEFT JOIN raw_material_categories c ON c.id=rm.category_id WHERE rm.is_active=1 ORDER BY c.sort_order, c.name, rm.name"); }
    public function vendors(): array { return $this->all("SELECT id,name FROM vendors WHERE is_active=1 ORDER BY name"); }
    public function store(array $d): int
    {
        $db = Database::connection(); $db->beginTransaction();
        try {
            $outletId=$this->outletId(); $date=$d['purchase_date'] ?: today();
            $vendorId = !empty($d['vendor_id']) ? (int)$d['vendor_id'] : null;
            
            $materialIds = $d['raw_material_id'] ?? [];
            if (!is_array($materialIds)) {
                $materialIds = [$materialIds];
                $qtys = [$d['qty'] ?? 0];
                $unitCosts = [$d['unit_cost'] ?? 0];
            } else {
                $qtys = $d['qty'] ?? [];
                $unitCosts = $d['unit_cost'] ?? [];
            }

            $total = 0.0;
            $items = [];
            foreach ($materialIds as $idx => $rmIdRaw) {
                $materialId = (int)$rmIdRaw;
                $qty = max(0, (float)($qtys[$idx] ?? 0));
                $unitCost = max(0, (float)($unitCosts[$idx] ?? 0));
                if ($materialId > 0 && $qty > 0) {
                    $items[] = ['id' => $materialId, 'qty' => $qty, 'cost' => $unitCost, 'sub' => $qty * $unitCost];
                    $total += $qty * $unitCost;
                }
            }

            if (empty($items)) throw new RuntimeException('Bahan belanjaan kosong atau qty tidak valid.');

            $poNo='PO-'.date('ymdHis').'-'.rand(10,99);
            $paid=(float)($d['paid_amount'] ?? $total); $debt=max(0,$total-$paid);
            $status=$debt>0 ? ($paid>0?'partial':'unpaid') : 'paid';
            $stmt=$db->prepare("INSERT INTO purchase_orders (outlet_id,vendor_id,po_number,purchase_date,due_date,payment_status,subtotal,discount,tax,grand_total,paid_amount,debt_amount,notes,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
            $stmt->execute([$outletId,$vendorId,$poNo,$date,($d['due_date']?:null),$status,$total,0,0,$total,$paid,$debt,trim($d['notes']??''),Auth::id()]);
            $poId=(int)$db->lastInsertId();

            require_once __DIR__ . '/../recipes/RecipeModel.php';
            $recipeModel = new RecipeModel();

            foreach ($items as $it) {
                $materialId = $it['id'];
                $qty = $it['qty'];
                $unitCost = $it['cost'];
                $itemTotal = $it['sub'];

                $mat=$this->one("SELECT * FROM raw_materials WHERE id=? FOR UPDATE", [$materialId]);
                if (!$mat) continue;

                $stmt=$db->prepare("INSERT INTO purchase_order_items (purchase_order_id,raw_material_id,qty,unit_id,unit_cost,total_cost) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$poId,$materialId,$qty,$mat['unit_id'],$unitCost,$itemTotal]);

                $outletStock = function_exists('inventory_get_material_stock') ? inventory_get_material_stock($db, $materialId, $outletId) : $mat;
                $oldStock=(float)($outletStock['stock_qty'] ?? $mat['stock_qty']);
                $oldAvg=(float)($outletStock['average_cost'] ?? $mat['average_cost']);
                $newStock=$oldStock+$qty; $newAvg=$newStock>0 ? (($oldStock*$oldAvg)+$itemTotal)/$newStock : $unitCost;

                if (function_exists('inventory_set_material_stock')) {
                    inventory_set_material_stock($db, $materialId, $newStock, $newAvg, $outletId);
                } else {
                    $db->prepare("UPDATE raw_materials SET stock_qty=?, average_cost=?, updated_at=NOW() WHERE id=?")->execute([$newStock,$newAvg,$materialId]);
                }

                $db->prepare("INSERT INTO inventory_movements (outlet_id,raw_material_id,movement_date,business_date,movement_type,reference_type,reference_id,qty_in,qty_out,unit_cost,total_cost,stock_after,notes,created_by,created_at) VALUES (?,?,NOW(),?,'purchase','purchase_orders',?,?,?,?,?,?,?,?,NOW())")
                    ->execute([$outletId,$materialId,$date,$poId,$qty,0,$unitCost,$itemTotal,$newStock,'Input belanja '.$poNo,Auth::id()]);
                
                $recipeModel->cascadeRecalculateFromRawMaterial($materialId, Auth::id() ?: 1);
            }

            if ($debt>0 && $vendorId) {
                $db->prepare("INSERT INTO vendor_payables (outlet_id,vendor_id,purchase_order_id,amount,paid_amount,remaining_amount,due_date,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())")
                    ->execute([$outletId,$vendorId,$poId,$total,$paid,$debt,($d['due_date']?:null),$status]);
            }

            $db->commit(); return $poId;
        } catch (Throwable $e) { $db->rollBack(); throw $e; }
    }

    public function getWithItems(int $id): ?array
    {
        $po = $this->one("SELECT * FROM purchase_orders WHERE id=? AND outlet_id=?", [$id, $this->outletId()]);
        if (!$po) return null;
        $po['items'] = $this->all("SELECT poi.*, rm.name as material_name, u.symbol as unit_symbol 
            FROM purchase_order_items poi
            JOIN raw_materials rm ON rm.id = poi.raw_material_id
            LEFT JOIN units u ON u.id = poi.unit_id
            WHERE poi.purchase_order_id=?", [$id]);
        return $po;
    }

    public function update(int $poId, array $d): void
    {
        $db = Database::connection();
        $outletId = $this->outletId();
        
        $po = $this->one("SELECT * FROM purchase_orders WHERE id=? AND outlet_id=?", [$poId, $outletId]);
        if (!$po) throw new RuntimeException('Data belanja tidak ditemukan.');
        
        $db->beginTransaction();
        try {
            $date = $d['purchase_date'] ?: $po['purchase_date'];
            $vendorId = !empty($d['vendor_id']) ? (int)$d['vendor_id'] : null;
            
            $materialIds = $d['raw_material_id'] ?? [];
            if (!is_array($materialIds)) {
                $materialIds = [$materialIds];
                $qtys = [$d['qty'] ?? 0];
                $unitCosts = [$d['unit_cost'] ?? 0];
            } else {
                $qtys = $d['qty'] ?? [];
                $unitCosts = $d['unit_cost'] ?? [];
            }

            $total = 0.0;
            $items = [];
            foreach ($materialIds as $idx => $rmIdRaw) {
                $materialId = (int)$rmIdRaw;
                $qty = max(0, (float)($qtys[$idx] ?? 0));
                $unitCost = max(0, (float)($unitCosts[$idx] ?? 0));
                if ($materialId > 0 && $qty > 0) {
                    $items[] = ['id' => $materialId, 'qty' => $qty, 'cost' => $unitCost, 'sub' => $qty * $unitCost];
                    $total += $qty * $unitCost;
                }
            }

            if (empty($items)) throw new RuntimeException('Bahan belanjaan kosong atau qty tidak valid.');

            require_once __DIR__ . '/../recipes/RecipeModel.php';
            $recipeModel = new RecipeModel();

            // 1. REVERSE OLD ITEMS
            $oldItems = $this->all("SELECT * FROM purchase_order_items WHERE purchase_order_id=?", [$poId]);
            $affectedMaterials = [];
            foreach ($oldItems as $oldIt) {
                $mId = (int)$oldIt['raw_material_id'];
                $oQty = (float)$oldIt['qty'];
                $oTotal = (float)$oldIt['total_cost'];
                $affectedMaterials[$mId] = true;
                
                $mat = $this->one("SELECT * FROM raw_materials WHERE id=? FOR UPDATE", [$mId]);
                if (!$mat) continue;
                
                $outletStock = function_exists('inventory_get_material_stock') ? inventory_get_material_stock($db, $mId, $outletId) : $mat;
                $currStock = (float)($outletStock['stock_qty'] ?? $mat['stock_qty']);
                $currAvg = (float)($outletStock['average_cost'] ?? $mat['average_cost']);
                
                $revStock = $currStock - $oQty;
                $revAvg = $revStock > 0 ? max(0, (($currStock * $currAvg) - $oTotal) / $revStock) : $currAvg;
                
                if (function_exists('inventory_set_material_stock')) {
                    inventory_set_material_stock($db, $mId, $revStock, $revAvg, $outletId);
                } else {
                    $db->prepare("UPDATE raw_materials SET stock_qty=?, average_cost=?, updated_at=NOW() WHERE id=?")->execute([$revStock, $revAvg, $mId]);
                }
            }
            
            // Delete old related records
            $db->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id=?")->execute([$poId]);
            $db->prepare("DELETE FROM inventory_movements WHERE reference_type='purchase_orders' AND reference_id=?")->execute([$poId]);
            $db->prepare("DELETE FROM vendor_payables WHERE purchase_order_id=?")->execute([$poId]);

            // 2. APPLY NEW ITEMS
            $poNo = $po['po_number'];
            $paid = (float)($d['paid_amount'] ?? $total); 
            $debt = max(0, $total - $paid);
            $status = $debt > 0 ? ($paid > 0 ? 'partial' : 'unpaid') : 'paid';
            
            $db->prepare("UPDATE purchase_orders SET vendor_id=?, purchase_date=?, due_date=?, payment_status=?, subtotal=?, grand_total=?, paid_amount=?, debt_amount=?, notes=?, updated_at=NOW() WHERE id=?")
               ->execute([$vendorId, $date, ($d['due_date']?:null), $status, $total, $total, $paid, $debt, trim($d['notes']??''), $poId]);

            foreach ($items as $it) {
                $materialId = $it['id'];
                $qty = $it['qty'];
                $unitCost = $it['cost'];
                $itemTotal = $it['sub'];
                $affectedMaterials[$materialId] = true;

                $mat = $this->one("SELECT * FROM raw_materials WHERE id=? FOR UPDATE", [$materialId]);
                if (!$mat) continue;

                $db->prepare("INSERT INTO purchase_order_items (purchase_order_id,raw_material_id,qty,unit_id,unit_cost,total_cost) VALUES (?,?,?,?,?,?)")
                   ->execute([$poId,$materialId,$qty,$mat['unit_id'],$unitCost,$itemTotal]);

                $outletStock = function_exists('inventory_get_material_stock') ? inventory_get_material_stock($db, $materialId, $outletId) : $mat;
                $oldStock = (float)($outletStock['stock_qty'] ?? $mat['stock_qty']);
                $oldAvg = (float)($outletStock['average_cost'] ?? $mat['average_cost']);
                
                $newStock = $oldStock + $qty; 
                $newAvg = $newStock > 0 ? (($oldStock * $oldAvg) + $itemTotal) / $newStock : $unitCost;

                if (function_exists('inventory_set_material_stock')) {
                    inventory_set_material_stock($db, $materialId, $newStock, $newAvg, $outletId);
                } else {
                    $db->prepare("UPDATE raw_materials SET stock_qty=?, average_cost=?, updated_at=NOW() WHERE id=?")->execute([$newStock,$newAvg,$materialId]);
                }

                $db->prepare("INSERT INTO inventory_movements (outlet_id,raw_material_id,movement_date,business_date,movement_type,reference_type,reference_id,qty_in,qty_out,unit_cost,total_cost,stock_after,notes,created_by,created_at) VALUES (?,?,NOW(),?,'purchase','purchase_orders',?,?,?,?,?,?,?,?,NOW())")
                    ->execute([$outletId,$materialId,$date,$poId,$qty,0,$unitCost,$itemTotal,$newStock,'Edit belanja '.$poNo,Auth::id()]);
            }

            if ($debt > 0 && $vendorId) {
                $db->prepare("INSERT INTO vendor_payables (outlet_id,vendor_id,purchase_order_id,amount,paid_amount,remaining_amount,due_date,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())")
                    ->execute([$outletId,$vendorId,$poId,$total,$paid,$debt,($d['due_date']?:null),$status]);
            }

            // Recalculate HPP for all affected materials
            foreach (array_keys($affectedMaterials) as $mId) {
                $recipeModel->cascadeRecalculateFromRawMaterial($mId, Auth::id() ?: 1);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

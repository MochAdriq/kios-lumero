<?php
class RecipeModel extends Model
{
    private function outletId(): int
    {
        if (function_exists('current_outlet_id')) {
            return current_outlet_id();
        }
        $user = Auth::user() ?? [];
        return (int)($user['outlet_id'] ?? 1) ?: 1;
    }

    public function listRecipes(): array
    {
        $outletId = $this->outletId();
        $pScope = outlet_scope_sql('p.outlet_id', $outletId);
        $pvScope = outlet_scope_sql('pv.outlet_id', $outletId);
        
        return $this->all("
            SELECT r.id as recipe_id, 
                   pv.id as variant_id,
                   COALESCE(r.recipe_type, 'final') as recipe_type,
                   COALESCE(r.total_hpp, 0) as total_hpp,
                   pv.variant_name, pv.sku, pv.hpp AS variant_hpp,
                   p.name product_name, p.category_id,
                   pc.name category_name
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            LEFT JOIN product_categories pc ON pc.id = p.category_id
            LEFT JOIN recipes r ON r.product_variant_id = pv.id
            WHERE p.is_active = 1 AND pv.is_active = 1
              AND {$pScope['sql']}
              AND {$pvScope['sql']}
            ORDER BY pc.sort_order, p.name, pv.variant_name
        ", array_merge($pScope['params'], $pvScope['params']));
    }

    public function listSubRecipes(): array
    {
        return $this->all("SELECT r.*, u.name as yield_unit_label FROM recipes r LEFT JOIN units u ON u.id = r.yield_unit_id WHERE r.recipe_type = 'sub_recipe' ORDER BY r.name ASC");
    }

    public function getRecipe(int $id): ?array
    {
        $recipe = $this->one("
            SELECT r.*, 
                   pv.variant_name, pv.sku, pv.selling_price, pv.hpp variant_hpp,
                   p.name product_name,
                   u.name as yield_unit_label
            FROM recipes r
            LEFT JOIN product_variants pv ON pv.id = r.product_variant_id
            LEFT JOIN products p ON p.id = pv.product_id
            LEFT JOIN units u ON u.id = r.yield_unit_id
            WHERE r.id = ?
        ", [$id]);
        
        if (!$recipe) return null;

        $recipe['items'] = $this->all("
            SELECT ri.*, 
                   u.symbol unit_symbol,
                   CASE 
                       WHEN ri.item_type = 'raw_material' THEN rm.name 
                       ELSE sr.name 
                   END as material_name,
                   CASE 
                       WHEN ri.item_type = 'raw_material' THEN rm.average_cost 
                       ELSE (sr.total_hpp / sr.yield_qty) 
                   END as current_unit_cost
            FROM recipe_items ri
            LEFT JOIN raw_materials rm ON rm.id = ri.raw_material_id
            LEFT JOIN recipes sr ON sr.id = ri.sub_recipe_id
            LEFT JOIN units u ON u.id = ri.unit_id
            WHERE ri.recipe_id = ?
            ORDER BY ri.item_type, material_name
        ", [$recipe['id']]);

        return $recipe;
    }

    public function findOrCreateByVariant(int $variantId): int
    {
        $recipe = $this->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$variantId]);
        if ($recipe) {
            return (int)$recipe['id'];
        }
        
        $v = $this->one("SELECT pv.id, p.name, pv.variant_name FROM product_variants pv JOIN products p ON p.id=pv.product_id WHERE pv.id = ?", [$variantId]);
        if (!$v) throw new Exception("Variant produk tidak ditemukan.");
        
        $name = $v['name'] . ' - ' . ($v['variant_name'] ?: 'Default');
        $stmt = $this->db->prepare("INSERT INTO recipes (product_variant_id, name, recipe_type, yield_qty, yield_unit_id, is_active, created_at, updated_at) VALUES (?, ?, 'final', 1, 4, 1, NOW(), NOW())");
        $stmt->execute([$variantId, $name]);
        return (int)$this->db->lastInsertId();
    }

    public function createSubRecipe(string $name, float $yieldQty, int $unitId): int
    {
        $this->execSql("
            INSERT INTO recipes (name, recipe_type, yield_qty, yield_unit_id, is_active, created_at, updated_at)
            VALUES (?, 'sub_recipe', ?, ?, 1, NOW(), NOW())
        ", [$name, $yieldQty, $unitId]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteSubRecipe(int $id): void
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recipe_items WHERE sub_recipe_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception("Sub-resep tidak bisa dihapus karena sedang dipakai sebagai bahan di resep lain.");
        }
        
        $this->db->beginTransaction();
        try {
            $this->execSql("DELETE FROM recipe_items WHERE recipe_id = ?", [$id]);
            $this->execSql("DELETE FROM recipes WHERE id = ?", [$id]);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findOrCreateUnit(string $symbol): int
    {
        $symbol = trim($symbol);
        if ($symbol === '') $symbol = 'Pcs';
        $row = $this->one("SELECT id FROM units WHERE symbol = ? OR name = ? LIMIT 1", [$symbol, $symbol]);
        if ($row) return (int)$row['id'];
        
        $this->execSql("INSERT INTO units (name, symbol) VALUES (?, ?)", [$symbol, $symbol]);
        return (int)$this->db->lastInsertId();
    }

    public function importSubRecipesCsv(array $rows): int
    {
        $imported = 0;
        foreach ($rows as $row) {
            $name = trim($row['Nama Sub-Resep'] ?? '');
            if ($name === '') continue;

            $yieldQty = (float)($row['Hasil Jadi'] ?? 1);
            if ($yieldQty <= 0) $yieldQty = 1;
            
            $unitSymbol = trim($row['Satuan Hasil'] ?? 'Porsi');
            $unitId = $this->findOrCreateUnit($unitSymbol);

            $this->createSubRecipe($name, $yieldQty, $unitId);
            $imported++;
        }
        return $imported;
    }

    public function importCompositionCsv(array $rows, int $userId): int
    {
        $imported = 0;
        foreach ($rows as $row) {
            $targetName = trim($row['Nama Resep Tujuan'] ?? '');
            $targetType = trim($row['Tipe Resep Tujuan'] ?? '');
            $itemTypeStr = trim($row['Tipe Bahan'] ?? '');
            $itemName = trim($row['Nama Bahan'] ?? '');
            $qty = (float)($row['Kebutuhan Qty'] ?? 0);
            $unitSymbol = trim($row['Satuan'] ?? 'Pcs');

            if ($targetName === '' || $itemName === '' || $qty <= 0) continue;

            $recipeTypeDb = (strtolower($targetType) === 'sub-resep' || strtolower($targetType) === 'sub_recipe') ? 'sub_recipe' : 'final';
            $recipeRow = $this->one("SELECT id FROM recipes WHERE name = ? AND recipe_type = ? LIMIT 1", [$targetName, $recipeTypeDb]);
            if (!$recipeRow) continue;
            $recipeId = (int)$recipeRow['id'];

            $itemTypeDb = (strtolower($itemTypeStr) === 'sub-resep' || strtolower($itemTypeStr) === 'sub_recipe') ? 'sub_recipe' : 'raw_material';
            $itemId = 0;

            if ($itemTypeDb === 'raw_material') {
                $rawRow = $this->one("SELECT id FROM raw_materials WHERE name = ? LIMIT 1", [$itemName]);
                if (!$rawRow) continue;
                $itemId = (int)$rawRow['id'];
            } else {
                $subRow = $this->one("SELECT id FROM recipes WHERE name = ? AND recipe_type = 'sub_recipe' LIMIT 1", [$itemName]);
                if (!$subRow) continue;
                $itemId = (int)$subRow['id'];
            }

            $unitId = $this->findOrCreateUnit($unitSymbol);
            
            $existing = $this->one(
                "SELECT id FROM recipe_items WHERE recipe_id = ? AND item_type = ? AND " . 
                ($itemTypeDb === 'raw_material' ? "raw_material_id" : "sub_recipe_id") . " = ?", 
                [$recipeId, $itemTypeDb, $itemId]
            );

            if (!$existing) {
                $this->addItem($recipeId, $itemTypeDb, $itemId, $qty, $unitId);
                $imported++;
            }
        }
        return $imported;
    }

    public function addItem(int $recipeId, string $itemType, int $itemId, float $qty, int $unitId): void
    {
        $rawId = $itemType === 'raw_material' ? $itemId : null;
        $subId = $itemType === 'sub_recipe' ? $itemId : null;

        $this->execSql("
            INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$recipeId, $itemType, $rawId, $subId, $qty, $unitId]);
        
        $this->recalculate($recipeId, Auth::id() ?: 1);
    }

    public function updateItemQty(int $itemId, float $qty, int $unitId, int $userId): void
    {
        $item = $this->one("SELECT recipe_id FROM recipe_items WHERE id = ?", [$itemId]);
        if ($item) {
            $this->execSql("UPDATE recipe_items SET qty = ?, unit_id = ? WHERE id = ?", [$qty, $unitId, $itemId]);
            $this->recalculate((int)$item['recipe_id'], $userId);
        }
    }

    public function removeItem(int $itemId, int $userId): void
    {
        $item = $this->one("SELECT recipe_id FROM recipe_items WHERE id = ?", [$itemId]);
        if ($item) {
            $this->execSql("DELETE FROM recipe_items WHERE id = ?", [$itemId]);
            $this->recalculate((int)$item['recipe_id'], $userId);
        }
    }

    public function recalculate(int $recipeId, int $userId): float
    {
        $recipe = $this->getRecipe($recipeId);
        if (!$recipe) throw new RuntimeException('Resep tidak ditemukan.');

        $total = 0.0;
        foreach ($recipe['items'] as $item) {
            $costPerUnit = (float)$item['current_unit_cost'];
            $lineTotal = (float)$item['qty'] * $costPerUnit;
            $total += $lineTotal;
            
            $this->execSql("UPDATE recipe_items SET cost_per_unit = ?, total_cost = ? WHERE id = ?", 
                [$costPerUnit, $lineTotal, $item['id']]);
        }

        $oldHpp = (float)$recipe['total_hpp'];
        $this->execSql("UPDATE recipes SET total_hpp = ?, updated_at = NOW() WHERE id = ?", [$total, $recipeId]);

        $variantId = $recipe['product_variant_id'];
        if ($recipe['recipe_type'] === 'final' && $variantId) {
            $sellingPrice = (float)$recipe['selling_price'];
            $margin = $sellingPrice - $total;
            $mp = ($sellingPrice > 0) ? ($margin / $sellingPrice * 100) : 0;
            
            $this->execSql("UPDATE product_variants SET hpp = ?, margin_amount = ?, margin_percent = ?, updated_at = NOW() WHERE id = ?", 
                [$total, $margin, $mp, $variantId]);
                
            $this->execSql("INSERT INTO recipe_cost_logs (recipe_id, product_variant_id, old_hpp, new_hpp, reason, recalculated_by, recalculated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())", 
                [$recipeId, $variantId, $oldHpp, $total, 'Recalculate HPP engine', $userId]);
        }

        if ($recipe['recipe_type'] === 'sub_recipe') {
            $parents = $this->all("SELECT DISTINCT recipe_id FROM recipe_items WHERE item_type = 'sub_recipe' AND sub_recipe_id = ?", [$recipeId]);
            foreach ($parents as $parent) {
                $this->recalculate((int)$parent['recipe_id'], $userId);
            }
        }

        return $total;
    }

    public function cascadeRecalculateFromRawMaterial(int $rawMaterialId, int $userId): void
    {
        $affectedRecipes = $this->all("SELECT DISTINCT recipe_id FROM recipe_items WHERE item_type = 'raw_material' AND raw_material_id = ?", [$rawMaterialId]);
        foreach ($affectedRecipes as $ar) {
            $this->recalculate((int)$ar['recipe_id'], $userId);
        }
    }

    public function recalculateAll(int $userId): int
    {
        $count = 0;
        
        $subRecipes = $this->listSubRecipes();
        foreach ($subRecipes as $sr) {
            $this->recalculate((int)$sr['id'], $userId);
            $count++;
        }
        
        $finalRecipes = $this->all("SELECT id FROM recipes WHERE recipe_type = 'final'");
        foreach ($finalRecipes as $fr) {
            $this->recalculate((int)$fr['id'], $userId);
            $count++;
        }
        
        return $count;
    }

    public function explodeBOM(int $recipeId, float $qtyMultiplier = 1.0, array &$visited = []): array
    {
        if (in_array($recipeId, $visited)) return []; 
        $visited[] = $recipeId;

        $recipe = $this->getRecipe($recipeId);
        if (!$recipe) {
            array_pop($visited);
            return [];
        }

        $ratio = $qtyMultiplier / (float)($recipe['yield_qty'] > 0 ? $recipe['yield_qty'] : 1.0);

        $bom = [];
        foreach ($recipe['items'] as $item) {
            $requiredQty = (float)$item['qty'] * $ratio;
            if ($item['item_type'] === 'raw_material') {
                $rmId = (int)$item['raw_material_id'];
                if (!isset($bom[$rmId])) $bom[$rmId] = 0.0;
                $bom[$rmId] += $requiredQty;
            } elseif ($item['item_type'] === 'sub_recipe') {
                $subBom = $this->explodeBOM((int)$item['sub_recipe_id'], $requiredQty, $visited);
                foreach ($subBom as $rmId => $subQty) {
                    if (!isset($bom[$rmId])) $bom[$rmId] = 0.0;
                    $bom[$rmId] += $subQty;
                }
            }
        }
        
        array_pop($visited);
        return $bom;
    }

    public function calculateMaxYield(int $productVariantId, ?int $outletId = null): float
    {
        if ($outletId === null) {
            $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
        }
        if (function_exists('inventory_ensure_outlet_stocks')) inventory_ensure_outlet_stocks($this->db);

        $recipe = $this->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$productVariantId]);
        if (!$recipe) return 0.0;

        $bom = $this->explodeBOM((int)$recipe['id'], 1.0);
        if (empty($bom)) return 0.0;

        $maxYield = PHP_FLOAT_MAX;
        foreach ($bom as $rmId => $qtyNeeded) {
            if ($qtyNeeded <= 0) continue;
            $rm = function_exists('inventory_get_material_stock') ? inventory_get_material_stock($this->db, $rmId, $outletId) : $this->one("SELECT stock_qty FROM raw_materials WHERE id = ?", [$rmId]);
            $stock = $rm ? (float)$rm['stock_qty'] : 0.0;
            $possible = floor($stock / $qtyNeeded);
            if ($possible < $maxYield) {
                $maxYield = $possible;
            }
        }
        
        return $maxYield === PHP_FLOAT_MAX ? 0.0 : max(0.0, $maxYield);
    }

    public function calculateBulkMaxYield(array $productVariantIds, ?int $outletId = null): array
    {
        if (empty($productVariantIds)) return [];
        if ($outletId === null) {
            $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
        }
        if (function_exists('inventory_ensure_outlet_stocks')) inventory_ensure_outlet_stocks($this->db);

        $stocks = [];
        $stmt = $this->db->prepare("
            SELECT rm.id, COALESCE(orm.stock_qty, (CASE WHEN ? = 1 THEN rm.stock_qty ELSE 0 END), 0) AS stock_qty
            FROM raw_materials rm
            LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ?
        ");
        $stmt->execute([$outletId, $outletId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $stocks[(int)$row['id']] = (float)$row['stock_qty'];
        }

        $results = array_fill_keys($productVariantIds, 0.0);
        
        $allRecipes = $this->all("SELECT * FROM recipes");
        $recipeMap = [];
        foreach ($allRecipes as $r) {
            $recipeMap[(int)$r['id']] = $r;
        }

        $allRecipeItems = $this->all("SELECT * FROM recipe_items");
        $itemsByRecipe = [];
        foreach ($allRecipeItems as $i) {
            $itemsByRecipe[(int)$i['recipe_id']][] = $i;
        }

        foreach ($recipeMap as $id => &$r) {
            $r['items'] = $itemsByRecipe[$id] ?? [];
        }
        unset($r);

        $variantToRecipeId = [];
        foreach ($recipeMap as $id => $r) {
            if ($r['product_variant_id']) {
                $variantToRecipeId[(int)$r['product_variant_id']] = $id;
            }
        }

        $explodeMem = function($recipeId, $qtyMultiplier, &$visited = []) use (&$explodeMem, &$recipeMap) {
            if (in_array($recipeId, $visited)) return [];
            $visited[] = $recipeId;
            if (!isset($recipeMap[$recipeId])) {
                array_pop($visited);
                return [];
            }
            $recipe = $recipeMap[$recipeId];
            $ratio = $qtyMultiplier / (float)($recipe['yield_qty'] > 0 ? $recipe['yield_qty'] : 1.0);
            $bom = [];
            foreach ($recipe['items'] as $item) {
                $reqQty = (float)$item['qty'] * $ratio;
                if ($item['item_type'] === 'raw_material') {
                    $rmId = (int)$item['raw_material_id'];
                    $bom[$rmId] = ($bom[$rmId] ?? 0) + $reqQty;
                } elseif ($item['item_type'] === 'sub_recipe') {
                    $subBom = $explodeMem((int)$item['sub_recipe_id'], $reqQty, $visited);
                    foreach ($subBom as $rmId => $subQty) {
                        $bom[$rmId] = ($bom[$rmId] ?? 0) + $subQty;
                    }
                }
            }
            array_pop($visited);
            return $bom;
        };

        foreach ($productVariantIds as $vid) {
            if (!isset($variantToRecipeId[$vid])) continue;
            $recipeId = $variantToRecipeId[$vid];
            $bom = $explodeMem($recipeId, 1.0);
            if (empty($bom)) continue;

            $maxYield = PHP_FLOAT_MAX;
            foreach ($bom as $rmId => $qtyNeeded) {
                if ($qtyNeeded <= 0) continue;
                $stock = $stocks[$rmId] ?? 0.0;
                $possible = floor($stock / $qtyNeeded);
                if ($possible < $maxYield) $maxYield = $possible;
            }
            $results[$vid] = $maxYield === PHP_FLOAT_MAX ? 0.0 : max(0.0, $maxYield);
        }

        return $results;
    }

    public function backflushRawMaterials(int $productVariantId, float $qtySold, int $orderItemId, int $userId, string $businessDate, int $outletId): void
    {
        if (function_exists('inventory_ensure_outlet_stocks')) inventory_ensure_outlet_stocks($this->db);
        $recipe = $this->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$productVariantId]);
        if (!$recipe) return;

        $bom = $this->explodeBOM((int)$recipe['id'], $qtySold);
        if (empty($bom)) return;

        foreach ($bom as $rmId => $qtyToDeduct) {
            if ($qtyToDeduct <= 0) continue;
            
            $rm = function_exists('inventory_get_material_stock') ? inventory_get_material_stock($this->db, $rmId, $outletId) : $this->one("SELECT stock_qty, average_cost FROM raw_materials WHERE id = ?", [$rmId]);
            if (!$rm) continue;

            $stockAfter = (float)$rm['stock_qty'] - $qtyToDeduct;
            $avgCost = (float)($rm['average_cost'] ?? 0);

            if (function_exists('inventory_set_material_stock')) {
                inventory_set_material_stock($this->db, $rmId, $stockAfter, $avgCost, $outletId);
            } else {
                $this->execSql("UPDATE raw_materials SET stock_qty = ?, updated_at = NOW() WHERE id = ?", [$stockAfter, $rmId]);
            }
            
            $this->execSql("INSERT INTO inventory_movements (outlet_id, raw_material_id, movement_date, business_date, movement_type, reference_type, reference_id, qty_in, qty_out, unit_cost, total_cost, stock_after, notes, created_by, created_at)
                VALUES (?, ?, NOW(), ?, 'sales_usage', 'pos_sale', ?, 0, ?, ?, ?, ?, 'Potongan otomatis (backflush) dari POS', ?, NOW())",
                [$outletId, $rmId, $businessDate, $orderItemId, $qtyToDeduct, $avgCost, $qtyToDeduct * $avgCost, $stockAfter, $userId]);
        }
    }
}

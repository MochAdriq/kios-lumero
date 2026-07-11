<?php
class ProductModel extends Model
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
        $scope = outlet_scope_sql('outlet_id', $this->outletId());
        return $this->all(
            "SELECT * FROM product_categories
            WHERE is_active=1 AND {$scope['sql']}
            ORDER BY sort_order,name",
            $scope['params']
        );
    }

    public function list(string $search=''): array
    {
        $outletId = $this->outletId();
        $pScope = outlet_scope_sql('p.outlet_id', $outletId);
        $pvScope = outlet_scope_sql('pv.outlet_id', $outletId);
        $pcScope = outlet_scope_sql('pc.outlet_id', $outletId);
        $params = array_merge($pScope['params'], $pvScope['params'], $pcScope['params']);
        $where = 'WHERE p.is_active=1 AND pv.is_active=1 AND pc.is_active=1
            AND ' . $pScope['sql'] . '
            AND ' . $pvScope['sql'] . '
            AND ' . $pcScope['sql'];
        if ($search !== '') {
            $where .= ' AND (p.name LIKE ? OR pv.variant_name LIKE ? OR pv.sku LIKE ?)';
            $s='%'.$search.'%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        return $this->all("SELECT pv.*, p.name product_name, p.category_id, pc.name category_name
            FROM product_variants pv
            JOIN products p ON p.id=pv.product_id
            JOIN product_categories pc ON pc.id=p.category_id
            $where
            ORDER BY pc.sort_order, pc.name, p.name, pv.variant_name
            LIMIT 300", $params);
    }

    public function findVariant(int $id): ?array
    {
        $outletId = $this->outletId();
        $pScope = outlet_scope_sql('p.outlet_id', $outletId);
        $pvScope = outlet_scope_sql('pv.outlet_id', $outletId);
        $pcScope = outlet_scope_sql('pc.outlet_id', $outletId);
        $params = array_merge([$id], $pScope['params'], $pvScope['params'], $pcScope['params']);
        return $this->one("SELECT pv.*, p.name product_name, p.category_id, pc.name category_name, p.outlet_id product_outlet_id, pv.outlet_id variant_outlet_id, pc.outlet_id category_outlet_id
            FROM product_variants pv
            JOIN products p ON p.id=pv.product_id
            JOIN product_categories pc ON pc.id=p.category_id
            WHERE pv.id=? AND p.is_active=1 AND pv.is_active=1 AND pc.is_active=1
                AND {$pScope['sql']}
                AND {$pvScope['sql']}
                AND {$pcScope['sql']}
            LIMIT 1", $params);
    }

    public function createVariant(array $d): int
    {
        $outletId = $this->outletId();
        $this->db->beginTransaction();
        try {
            $slugSku = $d['sku'] ?: 'SKU-' . time();
            $stmt=$this->db->prepare("INSERT INTO products (outlet_id,category_id,sku,name,product_type,unit_name,base_hpp,base_price,margin_amount,margin_percent,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,1,?,?)");
            $margin=(float)$d['selling_price']-(float)$d['hpp'];
            $mp=((float)$d['selling_price']>0)?($margin/(float)$d['selling_price']*100):0;
            $stmt->execute([$outletId,$d['category_id'],$slugSku,$d['name'],'single','porsi',$d['hpp'],$d['selling_price'],$margin,$mp,now(),now()]);
            $pid=(int)$this->db->lastInsertId();
            $stmt=$this->db->prepare("INSERT INTO product_variants (outlet_id,product_id,sku,variant_name,hpp,selling_price,margin_amount,margin_percent,is_default,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,1,?,?)");
            $stmt->execute([$outletId,$pid,$slugSku,$d['variant_name'] ?: 'Default',$d['hpp'],$d['selling_price'],$margin,$mp,now(),now()]);
            $vid=(int)$this->db->lastInsertId();
            
            // Auto create an empty final recipe container for this variant
            $stmt=$this->db->prepare("INSERT INTO recipes (product_variant_id, name, recipe_type, yield_qty, yield_unit_id, is_active, created_at, updated_at) VALUES (?, ?, 'final', 1, 4, 1, ?, ?)");
            $stmt->execute([$vid, $d['name'] . ' - ' . ($d['variant_name'] ?: 'Default'), now(), now()]);
            $this->db->commit();
            return $vid;
        } catch(Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateVariant(int $vid, array $d): void
    {
        $outletId = $this->outletId();
        
        $v = $this->findVariant($vid);
        if (!$v) throw new Exception("Variant tidak ditemukan.");
        $pid = $v['product_id'];

        $this->db->beginTransaction();
        try {
            $margin=(float)$d['selling_price']-(float)$d['hpp'];
            $mp=((float)$d['selling_price']>0)?($margin/(float)$d['selling_price']*100):0;
            
            // Update product (since this is a single variant system, we update the product directly)
            $stmt=$this->db->prepare("UPDATE products SET category_id=?, name=?, base_hpp=?, base_price=?, margin_amount=?, margin_percent=?, updated_at=? WHERE id=?");
            $stmt->execute([$d['category_id'], $d['name'], $d['hpp'], $d['selling_price'], $margin, $mp, now(), $pid]);
            
            // Update variant
            $stmt=$this->db->prepare("UPDATE product_variants SET hpp=?, selling_price=?, margin_amount=?, margin_percent=?, updated_at=? WHERE id=?");
            $stmt->execute([$d['hpp'], $d['selling_price'], $margin, $mp, now(), $vid]);
            
            $this->db->commit();
        } catch(Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteVariant(int $vid): void
    {
        $v = $this->findVariant($vid);
        if (!$v) throw new Exception("Variant tidak ditemukan.");
        $pid = $v['product_id'];

        $this->db->beginTransaction();
        try {
            // Soft delete
            $this->db->prepare("UPDATE product_variants SET is_active=0, updated_at=? WHERE id=?")->execute([now(), $vid]);
            $this->db->prepare("UPDATE products SET is_active=0, updated_at=? WHERE id=?")->execute([now(), $pid]);
            $this->db->commit();
        } catch(Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * List all product variants with override data for a specific outlet.
     */
    public function listWithOverrides(int $outletId, string $search = ''): array
    {
        $params = [$outletId];
        $where = "WHERE p.is_active = 1 AND pv.is_active = 1";
        if ($search !== '') {
            $where .= " AND (p.name LIKE ? OR pv.variant_name LIKE ? OR pv.sku LIKE ?)";
            $s = '%' . $search . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        return $this->all("
            SELECT
                pv.id AS variant_id,
                pv.sku,
                p.name AS product_name,
                pv.variant_name,
                pv.selling_price AS master_price,
                pv.hpp AS master_hpp,
                pv.is_active AS master_active,
                pbo.selling_price AS override_price,
                pbo.hpp AS override_hpp,
                pbo.is_active AS override_active
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            LEFT JOIN product_branch_overrides pbo ON pbo.product_variant_id = pv.id AND pbo.outlet_id = ?
            $where
            ORDER BY p.name, pv.variant_name
            LIMIT 500
        ", $params);
    }

    /**
     * Save product overrides for a specific outlet.
     */
    public function saveOverrides(int $outletId, array $items): int
    {
        $saved = 0;
        $this->db->beginTransaction();
        try {
            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                if ($variantId <= 0) continue;

                $price  = trim($item['selling_price'] ?? '');
                $hpp    = trim($item['hpp'] ?? '');
                $active = trim($item['is_active'] ?? '');

                // If all empty, delete any existing override
                if ($price === '' && $hpp === '' && $active === '') {
                    $this->execSql("DELETE FROM product_branch_overrides WHERE outlet_id = ? AND product_variant_id = ?", [$outletId, $variantId]);
                    continue;
                }

                $priceVal  = $price !== '' ? (float)$price : null;
                $hppVal    = $hpp !== '' ? (float)$hpp : null;
                $activeVal = $active !== '' ? (int)$active : null;

                $this->execSql("
                    INSERT INTO product_branch_overrides (outlet_id, product_variant_id, selling_price, hpp, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE selling_price = VALUES(selling_price), hpp = VALUES(hpp), is_active = VALUES(is_active), updated_at = NOW()
                ", [$outletId, $variantId, $priceVal, $hppVal, $activeVal]);
                $saved++;
            }
            $this->db->commit();
            return $saved;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

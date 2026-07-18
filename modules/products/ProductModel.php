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
        $outletId = $this->outletId();
        $scope = ['sql' => 'outlet_id = ?', 'params' => [$outletId]];
        return $this->all(
            "SELECT * FROM product_categories
            WHERE is_active=1 AND {$scope['sql']}
            ORDER BY sort_order,name",
            $scope['params']
        );
    }

    public function ensureImageColumnsAndSeed(): void
    {
        try {
            // Ensure column in products
            $colsP = $this->db->query("SHOW COLUMNS FROM products LIKE 'image'")->fetch();
            if (!$colsP) {
                $this->db->exec("ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL");
            }
            // Ensure column in product_variants
            $colsV = $this->db->query("SHOW COLUMNS FROM product_variants LIKE 'image'")->fetch();
            if (!$colsV) {
                $this->db->exec("ALTER TABLE product_variants ADD COLUMN image VARCHAR(255) DEFAULT NULL");
            }

            // Seed empty static images into database
            $unseeded = $this->all("SELECT id, name FROM products WHERE image IS NULL OR image = ''");
            foreach ($unseeded as $p) {
                $n = strtolower($p['name']);
                $img = 'images/pos-products/original.png';
                if (str_contains($n, 'paha bawah')) $img = 'images/pos-products/paha-bawah.png';
                elseif (str_contains($n, 'paha atas')) $img = 'images/pos-products/paha-atas.png';
                elseif (str_contains($n, 'dada')) $img = 'images/pos-products/dada.png';
                elseif (str_contains($n, 'sayap')) $img = 'images/pos-products/sayap.png';
                elseif (str_contains($n, 'kentang') || str_contains($n, 'potato')) $img = 'images/pos-products/kentang-kriwil.png';
                elseif (str_contains($n, 'matcha') || str_contains($n, 'minuman') || str_contains($n, 'drink')) $img = 'images/pos-products/matcha.png';
                elseif (str_contains($n, 'kopi') || str_contains($n, 'coffee')) $img = 'images/pos-products/kopi.png';
                elseif (str_contains($n, 'saus') || str_contains($n, 'sauce') || str_contains($n, 'celup')) $img = 'images/pos-products/celup-saus.png';
                elseif (str_contains($n, 'nasi') || str_contains($n, 'rice')) $img = 'images/pos-products/nasi.png';

                $stmt = $this->db->prepare("UPDATE products SET image = ? WHERE id = ?");
                $stmt->execute([$img, $p['id']]);
            }

            // Sync product_variants image with parent if variant image is empty
            $this->db->exec("UPDATE product_variants pv JOIN products p ON p.id = pv.product_id SET pv.image = p.image WHERE pv.image IS NULL OR pv.image = ''");
        } catch (Throwable $e) {}
    }

    public function list(string $search='', int $categoryId=0): array
    {
        $this->ensureImageColumnsAndSeed();
        $outletId = $this->outletId();
        $pScope = ['sql' => 'p.outlet_id = ?', 'params' => [$outletId]];
        $pvScope = ['sql' => 'pv.outlet_id = ?', 'params' => [$outletId]];
        $pcScope = ['sql' => 'pc.outlet_id = ?', 'params' => [$outletId]];
        $params = array_merge($pScope['params'], $pvScope['params'], $pcScope['params']);
        $where = 'WHERE p.is_active=1 AND pv.is_active=1 AND pc.is_active=1
            AND ' . $pScope['sql'] . '
            AND ' . $pvScope['sql'] . '
            AND ' . $pcScope['sql'];
        if ($categoryId > 0) {
            $where .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search !== '') {
            $where .= ' AND (p.name LIKE ? OR pv.variant_name LIKE ? OR pv.sku LIKE ?)';
            $s='%'.$search.'%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        return $this->all("SELECT pv.*, p.name product_name, COALESCE(pv.image, p.image) AS image, p.category_id, pc.name category_name
            FROM product_variants pv
            JOIN products p ON p.id=pv.product_id
            JOIN product_categories pc ON pc.id=p.category_id
            $where
            ORDER BY pc.sort_order, pc.name, p.name, pv.variant_name
            LIMIT 300", $params);
    }

    public function findVariant(int $id): ?array
    {
        return $this->one("SELECT pv.*, p.name product_name, COALESCE(pv.image, p.image) AS image, p.category_id, COALESCE(pc.name,'Umum') category_name, p.outlet_id product_outlet_id, pv.outlet_id variant_outlet_id, pc.outlet_id category_outlet_id
            FROM product_variants pv
            JOIN products p ON p.id=pv.product_id
            LEFT JOIN product_categories pc ON pc.id=p.category_id
            WHERE pv.id=?
            LIMIT 1", [$id]);
    }

    public function createVariant(array $d): int
    {
        $outletId = $this->outletId();
        $inTrans = $this->db->inTransaction();
        if (!$inTrans) $this->db->beginTransaction();
        try {
            $slugSku = $d['sku'] ?: 'SKU-' . time();
            $img = trim((string)($d['image'] ?? 'images/pos-products/original.png'));
            $stmt=$this->db->prepare("INSERT INTO products (outlet_id,category_id,sku,name,image,product_type,unit_name,base_hpp,base_price,margin_amount,margin_percent,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,1,?,?)");
            $margin=(float)$d['selling_price']-(float)$d['hpp'];
            $mp=((float)$d['selling_price']>0)?($margin/(float)$d['selling_price']*100):0;
            $stmt->execute([$outletId,$d['category_id'],$slugSku,$d['name'],$img,'single','porsi',$d['hpp'],$d['selling_price'],$margin,$mp,now(),now()]);
            $pid=(int)$this->db->lastInsertId();
            $stmt=$this->db->prepare("INSERT INTO product_variants (outlet_id,product_id,sku,variant_name,image,hpp,selling_price,margin_amount,margin_percent,is_default,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,1,1,?,?)");
            $stmt->execute([$outletId,$pid,$slugSku,$d['variant_name'] ?: 'Default',$img,$d['hpp'],$d['selling_price'],$margin,$mp,now(),now()]);
            $vid=(int)$this->db->lastInsertId();
            
            // Auto create an empty final recipe container for this variant
            $stmt=$this->db->prepare("INSERT INTO recipes (product_variant_id, name, recipe_type, yield_qty, yield_unit_id, is_active, created_at, updated_at) VALUES (?, ?, 'final', 1, 4, 1, ?, ?)");
            $stmt->execute([$vid, $d['name'] . ' - ' . ($d['variant_name'] ?: 'Default'), now(), now()]);
            if (!$inTrans) $this->db->commit();
            return $vid;
        } catch(Throwable $e){
            if (!$inTrans) $this->db->rollBack();
            throw $e;
        }
    }

    public function updateVariant(int $vid, array $d): void
    {
        $v = $this->findVariant($vid);
        if (!$v) throw new Exception("Variant tidak ditemukan (ID: $vid).");
        $pid = $v['product_id'];

        $inTrans = $this->db->inTransaction();
        if (!$inTrans) $this->db->beginTransaction();
        try {
            $catId = (int)($d['category_id'] ?? $v['category_id'] ?? 1);
            $prodName = isset($d['product_name']) && trim($d['product_name']) !== '' ? trim($d['product_name']) : $v['product_name'];
            $variantName = isset($d['variant_name']) && trim($d['variant_name']) !== '' ? trim($d['variant_name']) : $v['variant_name'];
            $hpp = isset($d['hpp']) ? (float)$d['hpp'] : (float)($v['hpp'] ?? 0);
            $selling = isset($d['selling_price']) ? (float)$d['selling_price'] : (float)($v['selling_price'] ?? 0);
            $margin = $selling - $hpp;
            $mp = ($selling > 0) ? ($margin / $selling * 100) : 0;
            
            if (isset($d['image'])) {
                $img = trim((string)$d['image']);
                $stmt=$this->db->prepare("UPDATE products SET category_id=?, name=?, image=?, base_hpp=?, base_price=?, margin_amount=?, margin_percent=?, updated_at=? WHERE id=?");
                $stmt->execute([$catId, $prodName, $img, $hpp, $selling, $margin, $mp, now(), $pid]);
                $stmt=$this->db->prepare("UPDATE product_variants SET variant_name=?, image=?, hpp=?, selling_price=?, margin_amount=?, margin_percent=?, updated_at=? WHERE id=?");
                $stmt->execute([$variantName, $img, $hpp, $selling, $margin, $mp, now(), $vid]);
            } else {
                $stmt=$this->db->prepare("UPDATE products SET category_id=?, name=?, base_hpp=?, base_price=?, margin_amount=?, margin_percent=?, updated_at=? WHERE id=?");
                $stmt->execute([$catId, $prodName, $hpp, $selling, $margin, $mp, now(), $pid]);
                $stmt=$this->db->prepare("UPDATE product_variants SET variant_name=?, hpp=?, selling_price=?, margin_amount=?, margin_percent=?, updated_at=? WHERE id=?");
                $stmt->execute([$variantName, $hpp, $selling, $margin, $mp, now(), $vid]);
            }
            
            if (!$inTrans) $this->db->commit();
        } catch(Throwable $e){
            if (!$inTrans) $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteVariant(int $vid): void
    {
        $v = $this->findVariant($vid);
        if (!$v) throw new Exception("Variant tidak ditemukan.");
        $pid = $v['product_id'];

        $inTrans = $this->db->inTransaction();
        if (!$inTrans) $this->db->beginTransaction();
        try {
            // Soft delete
            $this->db->prepare("UPDATE product_variants SET is_active=0, updated_at=? WHERE id=?")->execute([now(), $vid]);
            $this->db->prepare("UPDATE products SET is_active=0, updated_at=? WHERE id=?")->execute([now(), $pid]);
            if (!$inTrans) $this->db->commit();
        } catch(Throwable $e){
            if (!$inTrans) $this->db->rollBack();
            throw $e;
        }
    }



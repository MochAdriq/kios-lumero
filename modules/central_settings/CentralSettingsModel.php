<?php
class CentralSettingsModel extends Model
{
    public function getAllItems(): array
    {
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
        $raws = $this->all("SELECT id, name, sku, 'raw_material' as type, COALESCE(average_cost, 0) as unit_cost, unit_id FROM raw_materials WHERE is_active=1 AND (outlet_id = ? OR outlet_id = 0 OR outlet_id IS NULL)", [$outletId]);
        $subs = $this->all("SELECT id, name, '' as sku, 'sub_recipe' as type, COALESCE(total_hpp / IF(yield_qty > 0, yield_qty, 1), 0) as unit_cost, yield_unit_id as unit_id FROM recipes WHERE recipe_type='sub_recipe' AND (outlet_id = ? OR outlet_id = 0 OR outlet_id IS NULL)", [$outletId]);
        return array_merge($raws, $subs);
    }

    public function processWizard(array $post, array $files): int
    {
        $type = $post['item_type'] ?? '';
        $name = trim($post['name'] ?? '');
        $sku = trim($post['sku'] ?? '');
        
        if ($name === '') {
            throw new Exception("Nama tidak boleh kosong.");
        }

        $this->db->beginTransaction();
        try {
            $createdId = 0;
            $recipeId = 0; // For composition

            if ($type === 'raw_material') {
                $invModel = new InventoryModel();
                $catId = (int)($post['raw_category_id'] ?? 0);
                $unitId = (int)($post['unit_id'] ?? 0);
                $minStock = (float)($post['min_stock_qty'] ?? 0);
                
                $createdId = $invModel->createRawMaterial([
                    'category_id' => $catId,
                    'unit_id' => $unitId,
                    'name' => $name,
                    'sku' => $sku,
                    'min_stock_qty' => $minStock
                ]);

            } elseif ($type === 'product') {
                throw new Exception("Penambahan Produk Jual Final telah dipindahkan ke modul khusus. Silakan gunakan menu Product Builder (/products/builder).");
            } elseif ($type === 'sub_recipe') {
                $recipeModel = new RecipeModel();
                $yieldQty = (float)($post['yield_qty'] ?? 1);
                $unitId = (int)($post['unit_id'] ?? 0);
                
                $recipeId = $recipeModel->createSubRecipe($name, $yieldQty, $unitId);
                $createdId = $recipeId;
            } else {
                throw new Exception("Tipe item tidak valid.");
            }

            if ($recipeId > 0 && !empty($post['comp_item_id'])) {
                $recipeModel = new RecipeModel();
                foreach ($post['comp_item_id'] as $idx => $compIdStr) {
                    $compId = (int)$compIdStr;
                    $compType = trim($post['comp_item_type'][$idx] ?? '');
                    $compQty = (float)($post['comp_qty'][$idx] ?? 0);
                    $compUnitId = (int)($post['comp_unit_id'][$idx] ?? 0);

                    if ($compId > 0 && $compQty > 0 && $compUnitId > 0 && in_array($compType, ['raw_material', 'sub_recipe'])) {
                        $recipeModel->addItem($recipeId, $compType, $compId, $compQty, $compUnitId);
                    }
                }
            }

            $this->db->commit();
            return $createdId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function uploadProductImage(int $variantId, array $file): void
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Format gambar tidak didukung (hanya JPG, PNG, WEBP).");
        }

        $v = $this->one("SELECT product_id FROM product_variants WHERE id=?", [$variantId]);
        if (!$v) return;

        $uploadDir = __DIR__ . '/../../public/assets/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . $v['product_id'] . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $imgPath = 'uploads/products/' . $filename;
            $this->execSql("UPDATE products SET image = ? WHERE id = ?", [$imgPath, $v['product_id']]);
            $this->execSql("UPDATE product_variants SET image = ? WHERE id = ?", [$imgPath, $variantId]);
        } else {
            throw new Exception("Gagal mengupload gambar.");
        }
    }
}

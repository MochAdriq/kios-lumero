<?php
class CentralSettingsModel extends Model
{
    public function getAllItems(): array
    {
        $raws = $this->all("SELECT id, name, sku, 'raw_material' as type FROM raw_materials WHERE is_active=1");
        $subs = $this->all("SELECT id, name, '' as sku, 'sub_recipe' as type FROM recipes WHERE recipe_type='sub_recipe'");
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
                $prodModel = new ProductModel();
                $catId = (int)($post['product_category_id'] ?? 0);
                $sellPrice = (float)($post['selling_price'] ?? 0);
                $hpp = 0; // Calculated from recipe
                
                $variantId = $prodModel->createVariant([
                    'category_id' => $catId,
                    'name' => $name,
                    'variant_name' => trim($post['variant_name'] ?? ''),
                    'image' => trim($post['image'] ?? 'images/pos-products/original.png'),
                    'sku' => $sku,
                    'selling_price' => $sellPrice,
                    'hpp' => $hpp
                ]);
                $createdId = $variantId;

                // Handle image upload
                if (!empty($files['image']['tmp_name']) && $files['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $this->uploadProductImage($variantId, $files['image']);
                }

                // Get recipe ID created by ProductModel::createVariant
                $recipeRow = $this->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$variantId]);
                if ($recipeRow) {
                    $recipeId = (int)$recipeRow['id'];
                }
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

        $uploadDir = __DIR__ . '/../../public/uploads/products/';
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

<?php
class ProductController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        $m = new ProductModel();
        $categoryId = (int)($_GET['cat'] ?? 0);
        $this->view('products/index', [
            'items'      => $m->list($_GET['q'] ?? '', $categoryId),
            'categories' => $m->categories(),
            'productNames' => $m->distinctProductNames(),
            'pageTitle'  => 'Produk & Menu',
        ]);
    }

    public function show($id): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        $this->view('products/show', [
            'item'      => (new ProductModel())->findVariant((int)$id),
            'pageTitle' => 'Detail Produk',
        ]);
    }

    public function update(): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        try {
            $d = [
                'product_name' => trim($_POST['product_name'] ?? ''),
                'variant_name' => trim($_POST['variant_name'] ?? ''),
                'image' => trim($_POST['image'] ?? ''),
                'selling_price' => max(0, (int)($_POST['selling_price'] ?? 0)),
            ];
            if (isset($_POST['category_id'])) {
                $d['category_id'] = (int)$_POST['category_id'];
            }
            if (isset($_POST['cost_price'])) {
                $d['hpp'] = max(0, (int)$_POST['cost_price']);
            }
            if (!empty($_FILES['image_file']['tmp_name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_file'];
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file['type'], $allowed)) {
                    $uploadDir = __DIR__ . '/../../public/assets/uploads/products/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
                    $filename = 'product_' . $id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $d['image'] = 'uploads/products/' . $filename;
                    }
                }
            }
            (new ProductModel())->updateVariant($id, $d);
            Audit::log('update_product', 'product_variants', $id, null, $d);
            $_SESSION['flash_success'] = 'Produk berhasil diupdate.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal mengupdate: ' . $e->getMessage();
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        $this->redirect('/products');
    }

    public function delete(): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        try {
            (new ProductModel())->deleteVariant($id);
            Audit::log('delete_product', 'product_variants', $id, null, null);
            $_SESSION['flash_success'] = 'Produk berhasil dihapus.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus: ' . $e->getMessage();
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        $this->redirect('/products');
    }

    /**
     * Show product override form for a specific branch.
     */

    public function builder(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        
        $catModel = new CategoryModel();
        $invModel = new InventoryModel();
        $csModel  = new CentralSettingsModel();
        
        $this->view('products/builder', [
            'pageTitle'  => 'Product Builder (Racik Produk Final)',
            'categories' => $catModel->productCategories(),
            'units'      => $invModel->units(),
            'compItems'  => $csModel->getAllItems(),
            'exp_id'     => (int)($_GET['exp_id'] ?? 0),
            'exp_name'   => $_GET['exp_name'] ?? '',
            'exp_hpp'    => (int)($_GET['exp_hpp'] ?? 0),
            'exp_price'  => (int)($_GET['exp_price'] ?? 0),
        ]);
    }

    public function saveBuilder(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();
        
        try {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new Exception("Nama produk tidak boleh kosong.");
            }

            $prodModel = new ProductModel();
            $catId     = (int)($_POST['product_category_id'] ?? 0);
            $sellPrice = max(0, (float)($_POST['selling_price'] ?? 0));
            $variant   = trim($_POST['variant_name'] ?? '');
            $sku       = trim($_POST['sku'] ?? '');
            $image     = trim($_POST['image'] ?? 'images/pos-products/original.png');

            $variantId = $prodModel->createVariant([
                'category_id'   => $catId,
                'name'          => $name,
                'variant_name'  => $variant,
                'image'         => $image,
                'sku'           => $sku,
                'selling_price' => $sellPrice,
                'hpp'           => 0
            ]);

            // Get product_id from variant
            $variantRow = $prodModel->one("SELECT product_id FROM product_variants WHERE id = ? LIMIT 1", [$variantId]);
            $productId = $variantRow ? (int)$variantRow['product_id'] : 0;

            // Handle image upload if file provided
            if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['image'];
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file['type'], $allowed)) {
                    $uploadDir = __DIR__ . '/../../public/assets/uploads/products/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
                    $filename = 'product_' . $productId . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $imgPath = 'uploads/products/' . $filename;
                        $prodModel->execSql("UPDATE products SET image = ? WHERE id = ?", [$imgPath, $productId]);
                        $prodModel->execSql("UPDATE product_variants SET image = ? WHERE id = ?", [$imgPath, $variantId]);
                    }
                }
            }

            // Get recipe ID created by createVariant
            $recipeRow = $prodModel->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$variantId]);
            $recipeId = $recipeRow ? (int)$recipeRow['id'] : 0;

            if (isset($_POST['is_quick_hpp']) && $_POST['is_quick_hpp'] == '1') {
                $newHpp = max(0, (float)($_POST['manual_hpp'] ?? 0));
                $prodModel->execSql("UPDATE product_variants SET hpp = ?, updated_at = NOW() WHERE id = ?", [$newHpp, $variantId]);
                
                if ($productId > 0) {
                    $margin = $sellPrice - $newHpp;
                    $mp = ($sellPrice > 0) ? ($margin / $sellPrice * 100) : 0;
                    $prodModel->execSql("UPDATE products SET base_hpp = ?, margin_amount = ?, margin_percent = ?, updated_at = NOW() WHERE id = ?", [$newHpp, $margin, $mp, $productId]);
                }
            } else if ($recipeId > 0 && !empty($_POST['comp_item_id'])) {
                $recipeModel = new RecipeModel();
                foreach ($_POST['comp_item_id'] as $idx => $compIdStr) {
                    $compId = (int)$compIdStr;
                    $compType = trim($_POST['comp_item_type'][$idx] ?? '');
                    $compQty = (float)($_POST['comp_qty'][$idx] ?? 0);
                    $compUnitId = (int)($_POST['comp_unit_id'][$idx] ?? 0);

                    if ($compId > 0 && $compQty > 0 && $compUnitId > 0 && in_array($compType, ['raw_material', 'sub_recipe'])) {
                        $recipeModel->addItem($recipeId, $compType, $compId, $compQty, $compUnitId);
                    }
                }

                // Recalculate HPP
                $newHpp = $recipeModel->recalculate($recipeId, Auth::id() ?: 1);

                // Update base_hpp and margin in products table as well
                if ($productId > 0) {
                    $margin = $sellPrice - $newHpp;
                    $mp = ($sellPrice > 0) ? ($margin / $sellPrice * 100) : 0;
                    $prodModel->execSql("UPDATE products SET base_hpp = ?, margin_amount = ?, margin_percent = ?, updated_at = NOW() WHERE id = ?", [$newHpp, $margin, $mp, $productId]);
                }
            }

            Audit::log('create_product_builder', 'product_variants', $variantId, null, [
                'name' => $name, 'variant_name' => $variant, 'selling_price' => $sellPrice
            ]);

            $expId = (int)($_POST['exp_id'] ?? 0);
            if ($expId > 0 && $productId > 0) {
                require_once __DIR__ . '/../executive/ExecutiveModel.php';
                (new ExecutiveModel())->linkExperimentProduct($expId, $productId);
            }

            if (Auth::can(['super_admin']) && isset($_POST['push_to_branches']) && $_POST['push_to_branches'] == '1') {
                $this->pushProductToBranches($productId, $variantId, $recipeId);
            }

            $_SESSION['flash_success'] = 'Produk Jual Final beserta komposisi resep berhasil dibuat!';
            $this->redirect('/products');
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal menyimpan produk: ' . $e->getMessage();
            $this->redirect('/products/builder');
        }
    }

    private function pushProductToBranches(int $masterProductId, int $masterVariantId, int $masterRecipeId): void
    {
        $m = new ProductModel();
        $branches = $m->all("SELECT id FROM outlets WHERE id != 1 AND is_active = 1 AND is_hq = 0");
        if (!$branches) return;

        $masterProd = $m->one("SELECT * FROM products WHERE id = ?", [$masterProductId]);
        $masterVar = $m->one("SELECT * FROM product_variants WHERE id = ?", [$masterVariantId]);
        $masterCat = $m->one("SELECT name FROM product_categories WHERE id = ?", [$masterProd['category_id']]);
        
        $catName = $masterCat['name'] ?? 'Umum';

        foreach ($branches as $b) {
            $branchId = (int)$b['id'];
            if ($branchId === (int)$masterProd['outlet_id']) continue;

            // Check if variant SKU already exists in this branch
            $existing = $m->one("SELECT id FROM product_variants WHERE outlet_id = ? AND sku = ?", [$branchId, $masterVar['sku']]);
            if ($existing) continue;
            
            // Find or create category in branch
            $branchCat = $m->one("SELECT id FROM product_categories WHERE outlet_id = ? AND name = ? LIMIT 1", [$branchId, $catName]);
            if ($branchCat) {
                $bCatId = (int)$branchCat['id'];
            } else {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $catName)) . '-b' . $branchId;
                $m->execSql("INSERT INTO product_categories (outlet_id, name, slug, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, 0, 1, NOW(), NOW())", [$branchId, $catName, $slug]);
                $bCatId = (int)Database::connection()->lastInsertId();
            }

            // Clone product
            $m->execSql("INSERT INTO products (category_id, outlet_id, sku, name, description, image, product_type, unit_name, base_hpp, base_price, margin_amount, margin_percent, lifetime_qty_sold, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), NOW())",
                [$bCatId, $branchId, $masterProd['sku'], $masterProd['name'], $masterProd['description'], $masterProd['image'], $masterProd['product_type'], $masterProd['unit_name'], $masterProd['base_hpp'], $masterProd['base_price'], $masterProd['margin_amount'], $masterProd['margin_percent']]);
            $bProdId = (int)Database::connection()->lastInsertId();

            // Clone variant
            $m->execSql("INSERT INTO product_variants (product_id, outlet_id, sku, variant_name, image, hpp, selling_price, margin_amount, margin_percent, is_default, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())",
                [$bProdId, $branchId, $masterVar['sku'], $masterVar['variant_name'], $masterVar['image'], $masterVar['hpp'], $masterVar['selling_price'], $masterVar['margin_amount'], $masterVar['margin_percent']]);
            $bVarId = (int)Database::connection()->lastInsertId();

            // Clone recipe along with branch-scoped raw materials and sub-recipes
            if ($masterRecipeId > 0) {
                $masterRecipe = $m->one("SELECT * FROM recipes WHERE id = ?", [$masterRecipeId]);
                if ($masterRecipe) {
                    $m->execSql("INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, yield_unit_label, version, total_hpp, is_active, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())", [
                        $branchId,
                        $bVarId,
                        $masterRecipe['name'] ?? $masterProd['name'],
                        $masterRecipe['recipe_type'] ?? 'final',
                        (float)($masterRecipe['yield_qty'] ?? 1.0),
                        $masterRecipe['yield_unit_id'] ?: null,
                        $masterRecipe['yield_unit_label'] ?? '',
                        (int)($masterRecipe['version'] ?? 1),
                        (float)($masterVar['hpp'] ?? 0),
                        $masterRecipe['notes'] ?? ''
                    ]);
                    $bRecipeId = (int)Database::connection()->lastInsertId();
                    
                    // Helper to get or create branch raw material category ID
                    $catMapBranch = [];
                    $getBranchRMCatId = function($pusatCatId) use ($m, $branchId, &$catMapBranch) {
                        if (isset($catMapBranch[$branchId][$pusatCatId])) return $catMapBranch[$branchId][$pusatCatId];
                        
                        $catName = 'Umum';
                        if ($pusatCatId > 0) {
                            $row = $m->one("SELECT name FROM raw_material_categories WHERE id = ?", [$pusatCatId]);
                            if ($row && !empty($row['name'])) {
                                $catName = $row['name'];
                            }
                        }

                        $bCat = $m->one("SELECT id FROM raw_material_categories WHERE outlet_id = ? AND name = ? LIMIT 1", [$branchId, $catName]);
                        if ($bCat) {
                            $cid = (int)$bCat['id'];
                        } else {
                            $m->execSql("INSERT INTO raw_material_categories (outlet_id, name, sort_order) VALUES (?, ?, 0)", [$branchId, $catName]);
                            $cid = (int)Database::connection()->lastInsertId();
                        }
                        $catMapBranch[$branchId][$pusatCatId] = $cid;
                        return $cid;
                    };

                    $recipeItems = $m->all("SELECT * FROM recipe_items WHERE recipe_id = ?", [$masterRecipeId]);
                    foreach ($recipeItems as $ri) {
                        $itemType = $ri['item_type'] ?? '';
                        $bRawId = null;
                        $bSubId = null;
                        
                        if ($itemType === 'raw_material' && !empty($ri['raw_material_id'])) {
                            $masterRM = $m->one("SELECT * FROM raw_materials WHERE id = ?", [$ri['raw_material_id']]);
                            if ($masterRM) {
                                $branchRM = $m->one("SELECT id FROM raw_materials WHERE outlet_id = ? AND (sku = ? OR name = ?) LIMIT 1", [$branchId, $masterRM['sku'], $masterRM['name']]);
                                if ($branchRM) {
                                    $bRawId = (int)$branchRM['id'];
                                } else {
                                    $bCatId = $getBranchRMCatId((int)($masterRM['category_id'] ?? 0));
                                    $unitId = (int)($masterRM['unit_id'] ?? 0);
                                    if ($unitId <= 0) $unitId = 1;
                                    $m->execSql("INSERT INTO raw_materials (outlet_id, category_id, unit_id, name, sku, min_stock_qty, stock_qty, average_cost, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())", [
                                        $branchId,
                                        $bCatId,
                                        $unitId,
                                        $masterRM['name'],
                                        $masterRM['sku'],
                                        (float)($masterRM['min_stock_qty'] ?? 0),
                                        (float)($masterRM['average_cost'] ?? 0)
                                    ]);
                                    $bRawId = (int)Database::connection()->lastInsertId();
                                }
                            }
                        } elseif ($itemType === 'sub_recipe' && !empty($ri['sub_recipe_id'])) {
                            $masterSub = $m->one("SELECT * FROM recipes WHERE id = ?", [$ri['sub_recipe_id']]);
                            if ($masterSub) {
                                $branchSub = $m->one("SELECT id FROM recipes WHERE outlet_id = ? AND recipe_type = 'sub_recipe' AND name = ? LIMIT 1", [$branchId, $masterSub['name']]);
                                if ($branchSub) {
                                    $bSubId = (int)$branchSub['id'];
                                } else {
                                    $m->execSql("INSERT INTO recipes (outlet_id, product_variant_id, name, recipe_type, yield_qty, yield_unit_id, yield_unit_label, version, total_hpp, is_active, notes, created_at, updated_at) VALUES (?, NULL, ?, 'sub_recipe', ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())", [
                                        $branchId,
                                        $masterSub['name'],
                                        (float)($masterSub['yield_qty'] ?? 1.0),
                                        $masterSub['yield_unit_id'] ?: null,
                                        $masterSub['yield_unit_label'] ?? '',
                                        (int)($masterSub['version'] ?? 1),
                                        (float)($masterSub['total_hpp'] ?? 0),
                                        $masterSub['notes'] ?? ''
                                    ]);
                                    $bSubId = (int)Database::connection()->lastInsertId();
                                    
                                    $subItems = $m->all("SELECT * FROM recipe_items WHERE recipe_id = ?", [$masterSub['id']]);
                                    foreach ($subItems as $si) {
                                        if (($si['item_type'] ?? '') === 'raw_material' && !empty($si['raw_material_id'])) {
                                            $masterSubRM = $m->one("SELECT * FROM raw_materials WHERE id = ?", [$si['raw_material_id']]);
                                            if ($masterSubRM) {
                                                $branchSubRM = $m->one("SELECT id FROM raw_materials WHERE outlet_id = ? AND (sku = ? OR name = ?) LIMIT 1", [$branchId, $masterSubRM['sku'], $masterSubRM['name']]);
                                                if ($branchSubRM) {
                                                    $subRawId = (int)$branchSubRM['id'];
                                                } else {
                                                    $bCatId = $getBranchRMCatId((int)($masterSubRM['category_id'] ?? 0));
                                                    $unitId = (int)($masterSubRM['unit_id'] ?? 0);
                                                    if ($unitId <= 0) $unitId = 1;
                                                    $m->execSql("INSERT INTO raw_materials (outlet_id, category_id, unit_id, name, sku, min_stock_qty, stock_qty, average_cost, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())", [
                                                        $branchId,
                                                        $bCatId,
                                                        $unitId,
                                                        $masterSubRM['name'],
                                                        $masterSubRM['sku'],
                                                        (float)($masterSubRM['min_stock_qty'] ?? 0),
                                                        (float)($masterSubRM['average_cost'] ?? 0)
                                                    ]);
                                                    $subRawId = (int)Database::connection()->lastInsertId();
                                                }
                                                $siUnitId = (int)($si['unit_id'] ?? 0);
                                                if ($siUnitId <= 0) $siUnitId = 1;
                                                $m->execSql("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost, notes) VALUES (?, 'raw_material', ?, NULL, ?, ?, ?, ?, ?)", [
                                                    $bSubId,
                                                    $subRawId,
                                                    (float)($si['qty'] ?? 0),
                                                    $siUnitId,
                                                    (float)($si['cost_per_unit'] ?? 0),
                                                    (float)($si['total_cost'] ?? 0),
                                                    $si['notes'] ?? ''
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($bRawId || $bSubId) {
                            $riUnitId = (int)($ri['unit_id'] ?? 0);
                            if ($riUnitId <= 0) $riUnitId = 1;
                            $m->execSql("INSERT INTO recipe_items (recipe_id, item_type, raw_material_id, sub_recipe_id, qty, unit_id, cost_per_unit, total_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                                $bRecipeId,
                                $itemType,
                                $bRawId,
                                $bSubId,
                                (float)($ri['qty'] ?? 0),
                                $riUnitId,
                                (float)($ri['cost_per_unit'] ?? 0),
                                (float)($ri['total_cost'] ?? 0),
                                $ri['notes'] ?? ''
                            ]);
                        }
                    }
                }
            }
        }
    }
}

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
            $variantRow = (new Model())->one("SELECT product_id FROM product_variants WHERE id = ? LIMIT 1", [$variantId]);
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
                        (new Model())->execSql("UPDATE products SET image = ? WHERE id = ?", [$imgPath, $productId]);
                        (new Model())->execSql("UPDATE product_variants SET image = ? WHERE id = ?", [$imgPath, $variantId]);
                    }
                }
            }

            // Get recipe ID created by createVariant
            $recipeRow = (new Model())->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$variantId]);
            $recipeId = $recipeRow ? (int)$recipeRow['id'] : 0;

            if ($recipeId > 0 && !empty($_POST['comp_item_id'])) {
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
                    (new Model())->execSql("UPDATE products SET base_hpp = ?, margin_amount = ?, margin_percent = ?, updated_at = NOW() WHERE id = ?", [$newHpp, $margin, $mp, $productId]);
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
        $m = new Model();
        $branches = $m->all("SELECT id FROM outlets WHERE id != 1 AND is_active = 1 AND is_hq = 0");
        if (!$branches) return;

        $masterProd = $m->one("SELECT * FROM products WHERE id = ?", [$masterProductId]);
        $masterVar = $m->one("SELECT * FROM product_variants WHERE id = ?", [$masterVariantId]);
        $masterCat = $m->one("SELECT name FROM product_categories WHERE id = ?", [$masterProd['category_id']]);
        
        $catName = $masterCat['name'] ?? 'Umum';

        foreach ($branches as $b) {
            $branchId = (int)$b['id'];
            
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

            // Clone recipe (simplified, assuming ingredients are globally referenced by ID anyway)
            // Wait, recipe items reference raw_material_id which are global master data! So this is safe.
            if ($masterRecipeId > 0) {
                $m->execSql("INSERT INTO recipes (product_variant_id, total_cost, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$bVarId, $masterVar['hpp']]);
                $bRecipeId = (int)Database::connection()->lastInsertId();
                
                $recipeItems = $m->all("SELECT * FROM recipe_items WHERE recipe_id = ?", [$masterRecipeId]);
                foreach ($recipeItems as $ri) {
                    $m->execSql("INSERT INTO recipe_items (recipe_id, component_type, component_id, qty, unit_id, estimated_cost) VALUES (?, ?, ?, ?, ?, ?)",
                        [$bRecipeId, $ri['component_type'], $ri['component_id'], $ri['qty'], $ri['unit_id'], $ri['estimated_cost']]);
                }
            }
        }
    }
}

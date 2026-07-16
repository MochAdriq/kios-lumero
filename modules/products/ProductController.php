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
                    $uploadDir = __DIR__ . '/../../public/uploads/products/';
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
    public function overrides(): void
    {
        Auth::requireRoles(['super_admin']);
        $m = new ProductModel();

        $outlets = (new BranchModel())->list();
        $outletId = (int)($_GET['outlet_id'] ?? 0);
        $search = trim($_GET['q'] ?? '');

        // Default to first non-HQ outlet if none selected
        if ($outletId === 0 && !empty($outlets)) {
            foreach ($outlets as $o) {
                if (empty($o['is_hq'])) { $outletId = (int)$o['id']; break; }
            }
            if ($outletId === 0) $outletId = (int)$outlets[0]['id'];
        }

        $currentOutlet = null;
        foreach ($outlets as $o) {
            if ((int)$o['id'] === $outletId) { $currentOutlet = $o; break; }
        }

        $items = $outletId > 0 ? $m->listWithOverrides($outletId, $search) : [];

        $this->view('products/overrides', [
            'pageTitle'     => 'Override Harga Cabang',
            'outlets'       => $outlets,
            'currentOutlet' => $currentOutlet,
            'items'         => $items,
            'search'        => $search,
        ]);
    }

    /**
     * Save product overrides for a branch.
     */
    public function saveOverride(): void
    {
        Auth::requireRoles(['super_admin']);
        verify_csrf();
        $outletId = (int)($_POST['outlet_id'] ?? 0);
        if ($outletId <= 0) {
            $_SESSION['flash_error'] = 'Outlet tidak valid.';
            $this->redirect('/products/overrides');
            return;
        }
        try {
            $saved = (new ProductModel())->saveOverrides($outletId, $_POST['items'] ?? []);
            Audit::log('save_product_overrides', 'product_branch_overrides', null, null, [
                'outlet_id' => $outletId, 'saved' => $saved,
            ]);
            $_SESSION['flash_success'] = "Override berhasil disimpan: {$saved} item.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/products/overrides?outlet_id=' . $outletId);
    }

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
            'compItems'  => $csModel->getAllItems()
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
                    $uploadDir = __DIR__ . '/../../public/uploads/products/';
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

            $_SESSION['flash_success'] = 'Produk Jual Final beserta komposisi resep berhasil dibuat!';
            $this->redirect('/products');
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal menyimpan produk: ' . $e->getMessage();
            $this->redirect('/products/builder');
        }
    }
}

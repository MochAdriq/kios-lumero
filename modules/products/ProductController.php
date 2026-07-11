<?php
class ProductController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin','administrator']);
        $m = new ProductModel();
        $this->view('products/index', [
            'items'      => $m->list($_GET['q'] ?? ''),
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
                'variant_name' => trim($_POST['variant_name'] ?? ''),
                'selling_price' => (int)($_POST['selling_price'] ?? 0),
            ];
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
}

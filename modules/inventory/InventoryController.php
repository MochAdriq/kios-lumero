<?php
class InventoryController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $m = new InventoryModel();
        $catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
        $this->view('inventory/index', [
            'items'      => $m->list($_GET['q'] ?? '', $catId),
            'categories' => $m->categories(),
            'units'      => $m->units(),
            'stats'      => $m->stats(),
            'pageTitle'  => 'Gudang Bahan',
            'activeCat'  => $catId,
        ]);
    }

    public function lowStock(): void
    {
        Auth::requireLogin();
        $m = new InventoryModel();
        $this->view('inventory/low-stock', [
            'items'     => $m->lowStock(),
            'pageTitle' => 'Peringatan Stok',
        ]);
    }

    /**
     * Store: only creates categories now (not raw materials).
     */
    public function store(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($name === '') {
            $_SESSION['flash_error'] = 'Nama kategori tidak boleh kosong.';
            $this->redirect('/inventory');
            return;
        }

        $id = (new InventoryModel())->createCategory($name, $sortOrder);
        Audit::log('create_raw_material_category', 'raw_material_categories', $id, null, $_POST);
        $_SESSION['flash_success'] = "Kategori \"$name\" berhasil ditambahkan.";
        $this->redirect('/inventory');
    }

    public function updateRaw(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id <= 0 || $name === '') {
            $_SESSION['flash_error'] = 'ID atau Nama bahan tidak valid.';
            $this->redirect('/inventory');
            return;
        }

        try {
            (new InventoryModel())->updateRawMaterial($id, $_POST);
            Audit::log('update_raw_material', 'raw_materials', $id, null, $_POST);
            $_SESSION['flash_success'] = "Info bahan baku \"$name\" berhasil diperbarui.";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Gagal memperbarui bahan: ' . $e->getMessage();
        }
        $this->redirect('/inventory');
    }
}

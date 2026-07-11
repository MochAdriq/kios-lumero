<?php
class CorrectionController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $model = new CorrectionModel();
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : Auth::outletId();

        $this->view('corrections/index', [
            'pageTitle'    => 'Koreksi & Void',
            'orders'       => $model->voidableOrders($outletId),
            'materials'    => $model->rawMaterials(),
            'corrections'  => $model->recentCorrections(50),
        ]);
    }

    public function voidOrder(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();

        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason  = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            $_SESSION['flash_error'] = 'Order tidak valid.';
            $this->redirect('/corrections');
            return;
        }
        if ($reason === '') {
            $_SESSION['flash_error'] = 'Alasan void wajib diisi.';
            $this->redirect('/corrections');
            return;
        }

        try {
            $model = new CorrectionModel();
            $model->voidOrder($orderId, $reason, Auth::id());
            $_SESSION['flash_success'] = 'Order berhasil di-void. Stok bahan baku telah dikembalikan.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal void order: ' . $e->getMessage();
        }

        $this->redirect('/corrections');
    }

    public function stockCorrection(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();

        $rawMaterialId = (int)($_POST['raw_material_id'] ?? 0);
        $type          = (string)($_POST['correction_type'] ?? '');
        $qty           = abs((float)($_POST['qty'] ?? 0));
        $reason        = trim((string)($_POST['reason'] ?? ''));

        if ($rawMaterialId <= 0 || $qty <= 0 || $reason === '') {
            $_SESSION['flash_error'] = 'Semua field wajib diisi dengan benar.';
            $this->redirect('/corrections');
            return;
        }

        try {
            $model = new CorrectionModel();
            $model->correctStock($rawMaterialId, $type, $qty, $reason, Auth::id());
            $label = $type === 'stock_addition' ? 'ditambahkan' : 'dikurangi';
            $_SESSION['flash_success'] = "Stok bahan berhasil {$label}. Perubahan tercatat di audit log.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal koreksi stok: ' . $e->getMessage();
        }

        $this->redirect('/corrections');
    }

    public function history(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);

        $model = new CorrectionModel();

        $this->view('corrections/index', [
            'pageTitle'    => 'Riwayat Koreksi',
            'orders'       => [],
            'materials'    => [],
            'corrections'  => $model->recentCorrections(200),
            'activeTab'    => 'history',
        ]);
    }
}

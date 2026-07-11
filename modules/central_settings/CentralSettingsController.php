<?php
class CentralSettingsController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        
        $this->view('central-settings/index', [
            'pageTitle' => 'Sentral Data Setting'
        ]);
    }

    public function wizard(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $invModel = new InventoryModel();
        
        // For products
        $catModel = new CategoryModel();
        
        // For materials
        $mRawCat = new InventoryModel();
        
        $this->view('central-settings/wizard', [
            'pageTitle' => 'Wizard Tambah Data',
            'productCategories' => $catModel->productCategories(),
            'rawCategories' => $mRawCat->categories(),
            'units' => $invModel->units()
        ]);
    }

    public function processWizard(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();
        
        try {
            $m = new CentralSettingsModel();
            $m->processWizard($_POST, $_FILES);
            $_SESSION['flash_success'] = 'Data berhasil disimpan via Wizard Sentral Data.';
            $this->redirect('/central-settings');
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/central-settings/wizard');
        }
    }

    public function apiItems(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $m = new CentralSettingsModel();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $m->getAllItems()
        ]);
    }
}

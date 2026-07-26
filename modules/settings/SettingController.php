<?php
require_once __DIR__ . '/../../helpers/delivery_helper.php';

class SettingController extends Controller
{
    public function index(): void { Auth::requireRoles(['super_admin', 'administrator']); $m=new SettingModel(); $settings=[]; foreach($m->settings() as $s){$settings[$s['setting_key']]=$s['setting_value'];} $this->view('settings/index',['pageTitle'=>'Setting Sistem','outlet'=>$m->outlet(),'settings'=>$settings,'gateways'=>$m->gateway()]); }
    
    public function save(): void { 
        Auth::requireRoles(['super_admin', 'administrator']); 
        verify_csrf(); 
        
        $postData = $_POST;
        file_put_contents(__DIR__ . '/../../scratch_dump.txt', "FILES:\n" . print_r($_FILES, true) . "\nPOST:\n" . print_r($_POST, true));

        
        // Handle file upload
        if (isset($_FILES['qris_image'])) {
            if ($_FILES['qris_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $uploadDir = __DIR__ . '/../../public/assets/images/pos-products/payment/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                    
                    $filename = 'qris_' . time() . '.' . $ext;
                    $targetFile = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $targetFile)) {
                        if (!isset($postData['settings'])) $postData['settings'] = [];
                        $postData['settings']['payment_qris_image'] = 'public/assets/images/pos-products/payment/' . $filename;
                    } else {
                        file_put_contents('scratch_upload_err.txt', "Failed to move uploaded file\n", FILE_APPEND);
                    }
                } else {
                    file_put_contents('scratch_upload_err.txt', "Invalid extension: $ext\n", FILE_APPEND);
                }
            } else if ($_FILES['qris_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                file_put_contents('scratch_upload_err.txt', "Upload error code: " . $_FILES['qris_image']['error'] . "\n", FILE_APPEND);
            }
        }
        
        (new SettingModel())->save($postData); 
        $_SESSION['flash_success']='Setting berhasil disimpan.'; 
        $this->redirect('/settings'); 
    }
}

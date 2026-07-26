<?php
require_once __DIR__ . '/../../helpers/delivery_helper.php';

class SettingController extends Controller
{
    public function index(): void { Auth::requireRoles(['super_admin']); $m=new SettingModel(); $settings=[]; foreach($m->settings() as $s){$settings[$s['setting_key']]=$s['setting_value'];} $this->view('settings/index',['pageTitle'=>'Setting Sistem','outlet'=>$m->outlet(),'settings'=>$settings,'gateways'=>$m->gateway()]); }
    
    public function save(): void { 
        Auth::requireRoles(['super_admin']); 
        verify_csrf(); 
        
        $postData = $_POST;
        
        // Handle file upload
        if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uploadDir = __DIR__ . '/../../public/assets/images/pos-products/payment/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                
                $filename = 'qris_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $targetFile)) {
                    if (!isset($postData['settings'])) $postData['settings'] = [];
                    $postData['settings']['payment_qris_image'] = 'public/assets/images/pos-products/payment/' . $filename;
                }
            }
        }
        
        (new SettingModel())->save($postData); 
        $_SESSION['flash_success']='Setting berhasil disimpan.'; 
        $this->redirect('/settings'); 
    }
}

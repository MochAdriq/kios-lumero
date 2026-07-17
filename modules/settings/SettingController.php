<?php
require_once __DIR__ . '/../../helpers/delivery_helper.php';

class SettingController extends Controller
{
 public function index(): void { Auth::requireRoles(['super_admin']); $m=new SettingModel(); $settings=[]; foreach($m->settings() as $s){$settings[$s['setting_key']]=$s['setting_value'];} $this->view('settings/index',['pageTitle'=>'Setting Sistem','outlet'=>$m->outlet(),'settings'=>$settings,'gateways'=>$m->gateway()]); }
 public function save(): void { Auth::requireRoles(['super_admin']); verify_csrf(); (new SettingModel())->save($_POST); $_SESSION['flash_success']='Setting berhasil disimpan.'; $this->redirect('/settings'); }
}

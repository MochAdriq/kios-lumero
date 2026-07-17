<?php
require_once __DIR__.'/../helpers/functions.php';
require_once __DIR__.'/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__.'/../config/loyalty.php';
require_once __DIR__.'/../helpers/WhatsAppGateway.php';
date_default_timezone_set('Asia/Jakarta');
try{ if(isset($pdo) && $pdo instanceof PDO) $pdo->exec("SET time_zone = '+07:00'"); }catch(Throwable $e){}
loyalty_ensure_tables($pdo);
$msg=$err='';
function mem_e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function mem_money($n){ return function_exists('rupiah') ? rupiah((int)$n) : 'Rp'.number_format((int)$n,0,',','.'); }
function mem_reward_img_src($img, $name=''){ $img=trim((string)$img); if($img!=='' && preg_match('~^https?://~i',$img)) return $img; $base=basename($img); if($base!=='' && file_exists(__DIR__.'/../public/assets/images/pos-products/'.$base)) return '../public/assets/images/pos-products/'.$base; $n=strtolower(trim((string)$name)); if(str_contains($n,'kentang')) return '../public/assets/images/pos-products/kentang-kriwil.png'; if(str_contains($n,'matcha')) return '../public/assets/images/pos-products/matcha.png'; if(str_contains($n,'kopi')) return '../public/assets/images/pos-products/kopi.png'; if(str_contains($n,'sayap')) return '../public/assets/images/pos-products/sayap.png'; if(str_contains($n,'nasi')) return '../public/assets/images/pos-products/nasi.png'; if(str_contains($n,'saus')) return '../public/assets/images/pos-products/saus.png'; return '../public/assets/images/pos-products/original.png'; }
function mem_csrf(){ if(empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(16)); return $_SESSION['member_csrf']; }
function mem_check_csrf(){ if(($_POST['csrf'] ?? '') !== ($_SESSION['member_csrf'] ?? '')) throw new Exception('Sesi form tidak valid. Muat ulang halaman.'); }
function mem_current_id(){ return (int)($_SESSION['member_id'] ?? 0); }
function mem_clear_login_step(){ unset($_SESSION['member_login_phone'], $_SESSION['member_login_mode']); }
function mem_profile_percent(array $m): int{ $fields=['name','email','gender','birth_date','address']; $filled=0; foreach($fields as $f){ if(trim((string)($m[$f] ?? ''))!=='') $filled++; } return (int)round($filled/max(1,count($fields))*100); }
function mem_flash(string $message='', string $error=''): void{ if($message!=='') $_SESSION['member_flash_msg']=$message; if($error!=='') $_SESSION['member_flash_err']=$error; }
function mem_take_flash(): array{ $r=[(string)($_SESSION['member_flash_msg'] ?? ''),(string)($_SESSION['member_flash_err'] ?? '')]; unset($_SESSION['member_flash_msg'],$_SESSION['member_flash_err']); return $r; }
function mem_clean_redirect(string $page='profil'): void{ header('Location: dashboard.php?page='.rawurlencode($page)); exit; }
function mem_auto_claim_pending(PDO $pdo, int $memberId): string{
  $code=strtoupper(trim((string)($_SESSION['member_prefill_claim'] ?? $_GET['claim'] ?? '')));
  if($code==='' || $memberId<=0) return '';
  $member=loyalty_member_by_id($pdo,$memberId);
  if(!$member) return '';
  try{
    $res=loyalty_claim_receipt($pdo,$memberId,$code);
    unset($_SESSION['member_prefill_claim']);
    loyalty_activity($pdo,$memberId,$member['phone'] ?? null,'member_claim_auto_success','Auto klaim dari QR '.$code);
    return ' Klaim otomatis berhasil: '.$res['points'].' point dari order '.$res['order_no'].' telah ditambahkan. Saldo sekarang '.$res['balance'].' point.';
  }catch(Throwable $e){
    // Jangan hilangkan kode jika gagal, agar member masih bisa melihat/mencoba klaim manual atau menunjukkan ke kasir.
    $_SESSION['member_prefill_claim']=$code;
    loyalty_activity($pdo,$memberId,$member['phone'] ?? null,'member_claim_auto_failed',$e->getMessage());
    return ' Namun klaim otomatis belum berhasil: '.$e->getMessage().'. Kode tetap tersedia di form klaim.';
  }
}
list($flashMsg,$flashErr)=mem_take_flash(); if($flashMsg!=='') $msg=$flashMsg; if($flashErr!=='') $err=$flashErr;
if(isset($_GET['logout'])){ unset($_SESSION['member_id']); mem_clear_login_step(); header('Location: login.php'); exit; }
if(isset($_GET['ulang'])){ mem_clear_login_step(); header('Location: login.php'); exit; }
$incomingClaim=strtoupper(trim((string)($_GET['claim'] ?? '')));
if($incomingClaim!=='') $_SESSION['member_prefill_claim']=$incomingClaim;
if (mem_current_id() <= 0 && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['login']) && !isset($_GET['ulang'])) {
    header('Location: index.php' . ($incomingClaim !== '' ? '?claim=' . urlencode($incomingClaim) : ''));
    exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    mem_check_csrf();
    $action=(string)($_POST['action'] ?? '');
    if($action==='check_phone'){
      $phone=loyalty_normalize_phone((string)($_POST['phone'] ?? ''));
      if(strlen($phone)<9) throw new Exception('Nomor HP tidak valid. Gunakan format 08xxxxxxxxxx.');
      if(loyalty_rate_limited($pdo,$phone,'member_login_failed',8,10)) throw new Exception('Terlalu banyak percobaan. Coba lagi beberapa menit.');
      $m=loyalty_find_member_by_phone($pdo,$phone);
      $_SESSION['member_login_phone']=$phone;
      if($m){ if(($m['status'] ?? 'active')!=='active') throw new Exception('Member sedang nonaktif. Hubungi kasir/admin.'); $_SESSION['member_login_mode']=empty($m['pin_hash'])?'setup':'pin'; loyalty_activity($pdo,(int)$m['id'],$phone,'member_phone_check','Cek nomor dari halaman member'); $msg=empty($m['pin_hash'])?'Nomor ditemukan. Buat PIN untuk aktivasi akun.':'Nomor sudah terdaftar. Silakan masukkan PIN.'; }
      else{ 
          $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
          $_SESSION['member_register_otp'] = $otpCode;
          $_SESSION['member_login_mode'] = 'verify_otp';
          WhatsAppGateway::sendOtp($phone, $otpCode);
          loyalty_activity($pdo,null,$phone,'member_phone_check_new','Kirim OTP WhatsApp member baru'); 
          $msg='Nomor belum terdaftar. Kode OTP 6 digit telah dikirim ke WhatsApp Anda.'; 
      }
    }elseif($action==='verify_otp'){
      $phone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? ''));
      $inputOtp=trim((string)($_POST['otp'] ?? ''));
      $savedOtp=(string)($_SESSION['member_register_otp'] ?? '');
      if(strlen($phone)<9) throw new Exception('Sesi nomor HP tidak ditemukan. Masukkan nomor HP kembali.');
      if($savedOtp==='' || $inputOtp!==$savedOtp) throw new Exception('Kode OTP salah. Periksa kembali pesan WhatsApp Anda.');
      $_SESSION['member_login_mode']='register';
      $msg='OTP WhatsApp valid! Silakan buat 6 digit PIN untuk akun member Anda.';
    }elseif($action==='login_pin'){
      $phone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? '')); $pin=trim((string)($_POST['pin'] ?? ''));
      if(strlen($phone)<9) throw new Exception('Sesi nomor HP tidak ditemukan. Masukkan nomor HP kembali.');
      if(loyalty_rate_limited($pdo,$phone,'member_login_failed',5,10)) throw new Exception('Terlalu banyak percobaan PIN gagal. Coba lagi beberapa menit.');
      $m=loyalty_find_member_by_phone($pdo,$phone);
      if(!$m || empty($m['pin_hash']) || !password_verify($pin,$m['pin_hash'])){ loyalty_activity($pdo,(int)($m['id'] ?? 0),$phone,'member_login_failed','PIN salah'); throw new Exception('PIN salah. Silakan coba lagi.'); }
      if(($m['status'] ?? 'active')!=='active') throw new Exception('Member sedang nonaktif. Hubungi kasir/admin.');
      $_SESSION['member_id']=(int)$m['id']; $autoMsg=mem_auto_claim_pending($pdo,(int)$m['id']); mem_clear_login_step(); loyalty_activity($pdo,(int)$m['id'],$phone,'member_login','Login berhasil halaman member'); mem_flash('Berhasil masuk. Selamat datang.'.$autoMsg); header('Location: dashboard.php?page=profil'); exit;
    }elseif($action==='create_pin'){
      $phone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? '')); $mode=(string)($_SESSION['member_login_mode'] ?? 'register'); $pin=trim((string)($_POST['pin'] ?? '')); $pin2=trim((string)($_POST['pin_confirm'] ?? ''));
      if(strlen($phone)<9) throw new Exception('Sesi nomor HP tidak ditemukan. Masukkan nomor HP kembali.');
      if(strlen($pin)<4) throw new Exception('PIN minimal 4 digit.'); if($pin!==$pin2) throw new Exception('Konfirmasi PIN belum sama.');
      $m=loyalty_find_member_by_phone($pdo,$phone);
      if($m){ if(!empty($m['pin_hash']) && $mode==='register') throw new Exception('Nomor ini sudah memiliki PIN. Silakan masuk dengan PIN.'); $pdo->prepare("UPDATE members SET pin_hash=?, updated_at=NOW() WHERE id=?")->execute([password_hash($pin,PASSWORD_DEFAULT),(int)$m['id']]); $_SESSION['member_id']=(int)$m['id']; loyalty_activity($pdo,(int)$m['id'],$phone,'member_pin_setup','Membuat PIN dari halaman member'); }
      else{ $m=loyalty_create_member($pdo,$phone,'',$pin,null); $_SESSION['member_id']=(int)$m['id']; loyalty_activity($pdo,(int)$m['id'],$phone,'member_register','Registrasi nomor dan PIN dari halaman member'); }
      $autoMsg=mem_auto_claim_pending($pdo,(int)$_SESSION['member_id']); mem_clear_login_step(); mem_flash('PIN berhasil disimpan. Silakan lengkapi data opsional untuk bonus point.'.$autoMsg); header('Location: dashboard.php?page=profil'); exit;
    }elseif($action==='claim'){
      $memberId=mem_current_id(); if($memberId<=0) throw new Exception('Silakan masuk terlebih dahulu.');
      $member=loyalty_member_by_id($pdo,$memberId); if(!$member) throw new Exception('Member tidak ditemukan.');
      if(loyalty_rate_limited($pdo,$member['phone'],'member_claim_failed',5,10)) throw new Exception('Terlalu banyak percobaan klaim gagal. Coba lagi beberapa menit.');
      $code=(string)($_POST['claim_code'] ?? '');
      try{ $res=loyalty_claim_receipt($pdo,$memberId,$code); unset($_SESSION['member_prefill_claim']); loyalty_activity($pdo,$memberId,$member['phone'],'member_claim_success','Klaim '.$code); $msg='Klaim berhasil. '.$res['points'].' point dari order '.$res['order_no'].' ditambahkan. Saldo sekarang '.$res['balance'].' point.'; }
      catch(Throwable $e){ loyalty_activity($pdo,$memberId,$member['phone'],'member_claim_failed',$e->getMessage()); throw $e; }
    }elseif($action==='update_profile'){
      $memberId=mem_current_id(); if($memberId<=0) throw new Exception('Silakan masuk terlebih dahulu.');
      $pin=trim((string)($_POST['pin'] ?? '')); $pin2=trim((string)($_POST['pin_confirm'] ?? ''));
      $res=loyalty_update_member_profile($pdo,$memberId,['name'=>$_POST['name'] ?? '', 'email'=>$_POST['email'] ?? '', 'gender'=>$_POST['gender'] ?? '', 'birth_date'=>$_POST['birth_date'] ?? '', 'address'=>$_POST['address'] ?? ''],null);
      if($pin!==''){ if(strlen($pin)<4) throw new Exception('PIN minimal 4 digit.'); if($pin!==$pin2) throw new Exception('Konfirmasi PIN belum sama.'); $pdo->prepare("UPDATE members SET pin_hash=?, updated_at=NOW() WHERE id=?")->execute([password_hash($pin,PASSWORD_DEFAULT),$memberId]); loyalty_activity($pdo,$memberId,null,'member_pin_update','Ubah PIN dari halaman member'); }
      $msg=$res['message'] ?? 'Profil member berhasil diperbarui.';
    }elseif($action==='redeem_reward'){
      $memberId=mem_current_id(); if($memberId<=0) throw new Exception('Silakan masuk terlebih dahulu.');
      $res=loyalty_request_reward_redemption($pdo,$memberId,(int)($_POST['reward_id'] ?? 0),null);
      $msg='Penukaran berhasil diajukan: '.$res['reward']['name'].' menggunakan '.$res['points_used'].' point. Kode penukaran: '.$res['code'].'. Tunjukkan halaman riwayat penukaran ke kasir/outlet.';
    }
  }catch(Throwable $e){ $err=$e->getMessage(); }
}
$member=null; $logs=[]; $orders=[]; $claims=[]; $rewardProducts=[]; $redemptions=[]; $settings=loyalty_settings($pdo);
if(mem_current_id()>0){
  $member=loyalty_member_by_id($pdo,mem_current_id());
  if(!$member){ unset($_SESSION['member_id']); }
  else{
    $st=$pdo->prepare("SELECT * FROM member_point_logs WHERE member_id=? ORDER BY id DESC LIMIT 80"); $st->execute([(int)$member['id']]); $logs=$st->fetchAll(PDO::FETCH_ASSOC);
    $st=$pdo->prepare("SELECT o.order_number AS order_no, o.grand_total AS total, COALESCE(p.payment_method,'-') AS payment_method, COALESCE(o.loyalty_points_earned,0) AS loyalty_points_earned, COALESCE(o.loyalty_points_redeemed,0) AS loyalty_points_redeemed, o.created_at FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE (o.member_id=? OR o.customer_id=?) AND o.payment_status='paid' ORDER BY o.id DESC LIMIT 60"); $st->execute([(int)$member['id'], (int)$member['id']]); $orders=$st->fetchAll(PDO::FETCH_ASSOC);
    $st=$pdo->prepare("SELECT rc.*, o.order_number AS order_no FROM receipt_claims rc LEFT JOIN orders o ON o.id=rc.transaction_id WHERE rc.claimed_by_member_id=? ORDER BY rc.id DESC LIMIT 60"); $st->execute([(int)$member['id']]); $claims=$st->fetchAll(PDO::FETCH_ASSOC);
    $rewardProducts=loyalty_get_reward_products($pdo);
    $redemptions=function_exists('loyalty_member_reward_redemptions') ? loyalty_member_reward_redemptions($pdo,(int)$member['id'],40) : [];
  }
}
$pendingPhone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? '')); $pendingMode=(string)($_SESSION['member_login_mode'] ?? '');
$profileComplete=$member ? loyalty_profile_is_complete($member) : false; $profilePercent=$member ? mem_profile_percent($member) : 0; $bonusPoints=(int)($settings['profile_bonus_points'] ?? 2); $csrf=mem_csrf();
$page=(string)($_GET['page'] ?? 'profil'); if(!in_array($page,['profil','riwayat','penukaran'],true)) $page='profil';
$prefillClaim=(string)($_SESSION['member_prefill_claim'] ?? $incomingClaim);
require __DIR__ . '/views/layout.php';

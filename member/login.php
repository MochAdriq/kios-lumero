<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
function mem_csrf(){ if(empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(16)); return $_SESSION['member_csrf']; }
function mem_check_csrf(){ if(($_POST['csrf'] ?? '') !== ($_SESSION['member_csrf'] ?? '')) throw new Exception('Sesi form tidak valid. Muat ulang halaman.'); }
function mem_current_id(){ return (int)($_SESSION['member_id'] ?? 0); }
function mem_clear_login_step(){ unset($_SESSION['member_login_phone'], $_SESSION['member_login_mode']); }
function mem_flash(string $message='', string $error=''): void{ if($message!=='') $_SESSION['member_flash_msg']=$message; if($error!=='') $_SESSION['member_flash_err']=$error; }
function mem_take_flash(): array{ $r=[(string)($_SESSION['member_flash_msg'] ?? ''),(string)($_SESSION['member_flash_err'] ?? '')]; unset($_SESSION['member_flash_msg'],$_SESSION['member_flash_err']); return $r; }
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
    $_SESSION['member_prefill_claim']=$code;
    loyalty_activity($pdo,$memberId,$member['phone'] ?? null,'member_claim_auto_failed',$e->getMessage());
    return ' Namun klaim otomatis belum berhasil: '.$e->getMessage().'. Kode tetap tersedia di form klaim.';
  }
}
function mem_process_pending_event_reward(PDO $pdo, int $memberId): string{
    if (empty($_SESSION['pending_event_reward'])) return '';
    $prize = $_SESSION['pending_event_reward'];
    unset($_SESSION['pending_event_reward']);
    $stmt = $pdo->prepare("SELECT status FROM reward_claims WHERE user_id = ? AND prize_id IN (SELECT id FROM event_prizes WHERE event_id = 'kalibunder_go') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$memberId]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['status'] === 'PENDING') {
            return ' 🚨 Maaf, Anda belum menukarkan kupon sebelumnya! Yuk, selesaikan dulu penukaran hadiah Anda di Outlet Lumero Kalibunder sebelum berburu kupon baru.';
        } elseif ($row['status'] === 'CLAIMED') {
            return ' 🚨 Sistem mendeteksi Anda sudah pernah mengambil hadiah Grand Opening ini. Terima kasih partisipasinya dan berikan kesempatan bagi yang lain ya! 😉';
        } else {
            return ' 🚨 Kupon Anda sebelumnya sudah hangus karena lewat batas 7 hari. Sayang sekali, kesempatan undian ini hanya berlaku satu kali untuk setiap akun.';
        }
    }
    $qr = 'KAL-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    $pdo->prepare("INSERT INTO reward_claims (user_id, prize_id, qr_code, expired_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))")->execute([$memberId, (int)$prize['id'], $qr]);

    return ' Kupon Grand Opening berhasil diklaim: ' . mem_e($prize['name']) . '!';
}
list($flashMsg,$flashErr)=mem_take_flash(); if($flashMsg!=='') $msg=$flashMsg; if($flashErr!=='') $err=$flashErr;
if (isset($_GET['source']) && $_GET['source'] === 'event_kalibunder' && !empty($_SESSION['pending_event_reward']) && empty($msg)) {
    $msg = 'Selamat! Anda berkesempatan mendapatkan ' . mem_e($_SESSION['pending_event_reward']['name']) . '. Yuk lengkapi data pengambilan hadiahnya!';
}
if(isset($_GET['logout'])){ unset($_SESSION['member_id']); mem_clear_login_step(); header('Location: index.php'); exit; }
if(isset($_GET['ulang'])){ mem_clear_login_step(); header('Location: login.php'); exit; }

if (mem_current_id() > 0) {
    header('Location: dashboard.php');
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
          $msg = empty($_SESSION['pending_event_reward']) ? 'Nomor belum terdaftar. Kode OTP 6 digit telah dikirim ke WhatsApp Anda.' : 'Satu langkah terakhir. Masukkan 6 digit kunci brankas yang kami kirimkan ke WhatsApp Anda agar kupon tidak diklaim orang lain.';
      }
    }elseif($action==='verify_otp'){
      $phone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? ''));
      $inputOtp=trim((string)($_POST['otp'] ?? ''));
      $savedOtp=(string)($_SESSION['member_register_otp'] ?? '');
      if(strlen($phone)<9) throw new Exception('Sesi nomor HP tidak ditemukan. Masukkan nomor HP kembali.');
      if($savedOtp==='' || $inputOtp!==$savedOtp) throw new Exception('Kode OTP salah. Periksa kembali pesan WhatsApp Anda.');
      $_SESSION['member_login_mode']='register';
      $msg='OTP WhatsApp valid! Silakan buat 4 digit PIN untuk akun member Anda.';
    }elseif($action==='login_pin'){
      $phone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? '')); $pin=trim((string)($_POST['pin'] ?? ''));
      if(strlen($phone)<9) throw new Exception('Sesi nomor HP tidak ditemukan. Masukkan nomor HP kembali.');
      if(loyalty_rate_limited($pdo,$phone,'member_login_failed',5,10)) throw new Exception('Terlalu banyak percobaan PIN gagal. Coba lagi beberapa menit.');
      $m=loyalty_find_member_by_phone($pdo,$phone);
      if(!$m || empty($m['pin_hash']) || !password_verify($pin,$m['pin_hash'])){ loyalty_activity($pdo,(int)($m['id'] ?? 0),$phone,'member_login_failed','PIN salah'); throw new Exception('PIN salah. Silakan coba lagi.'); }
      if(($m['status'] ?? 'active')!=='active') throw new Exception('Member sedang nonaktif. Hubungi kasir/admin.');
      $_SESSION['member_id']=(int)$m['id']; $autoMsg=mem_auto_claim_pending($pdo,(int)$m['id']); $evtMsg=mem_process_pending_event_reward($pdo,(int)$m['id']); mem_clear_login_step(); loyalty_activity($pdo,(int)$m['id'],$phone,'member_login','Login berhasil halaman member'); mem_flash('Berhasil masuk. Selamat datang.'.$autoMsg.$evtMsg); header('Location: ' . ($evtMsg !== '' ? 'reward-claim.php' : 'dashboard.php')); exit;
    }elseif($action==='create_pin'){
      $phone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? '')); $mode=(string)($_SESSION['member_login_mode'] ?? 'register'); $pin=trim((string)($_POST['pin'] ?? '')); $pin2=trim((string)($_POST['pin_confirm'] ?? ''));
      if(strlen($phone)<9) throw new Exception('Sesi nomor HP tidak ditemukan. Masukkan nomor HP kembali.');
      if(strlen($pin)!==4) throw new Exception('PIN harus 4 digit.'); if($pin!==$pin2) throw new Exception('Konfirmasi PIN belum sama.');
      $m=loyalty_find_member_by_phone($pdo,$phone);
      if($m){ if(!empty($m['pin_hash']) && $mode==='register') throw new Exception('Nomor ini sudah memiliki PIN. Silakan masuk dengan PIN.'); $pdo->prepare("UPDATE members SET pin_hash=?, updated_at=NOW() WHERE id=?")->execute([password_hash($pin,PASSWORD_DEFAULT),(int)$m['id']]); $_SESSION['member_id']=(int)$m['id']; loyalty_activity($pdo,(int)$m['id'],$phone,'member_pin_setup','Membuat PIN dari halaman member'); }
      else{ $m=loyalty_create_member($pdo,$phone,'',$pin,null); $_SESSION['member_id']=(int)$m['id']; loyalty_activity($pdo,(int)$m['id'],$phone,'member_register','Registrasi nomor dan PIN dari halaman member'); }
      $autoMsg=mem_auto_claim_pending($pdo,(int)$_SESSION['member_id']); $evtMsg=mem_process_pending_event_reward($pdo,(int)$_SESSION['member_id']); mem_clear_login_step(); mem_flash('PIN berhasil disimpan. Silakan lengkapi data opsional untuk bonus point.'.$autoMsg.$evtMsg); header('Location: ' . ($evtMsg !== '' ? 'reward-claim.php' : 'dashboard.php')); exit;
    }
  }catch(Throwable $e){ $err=$e->getMessage(); }
}

$pendingPhone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? '')); $pendingMode=(string)($_SESSION['member_login_mode'] ?? '');
$incomingClaim=strtoupper(trim((string)($_GET['claim'] ?? '')));
if($incomingClaim!=='') $_SESSION['member_prefill_claim']=$incomingClaim;
$prefillClaim=(string)($_SESSION['member_prefill_claim'] ?? $incomingClaim);
$csrf=mem_csrf();
$_GET['login'] = 1; // force login view rendering
$member = null; // fix undefined variable
require __DIR__ . '/views/layout.php';
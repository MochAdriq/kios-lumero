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
function mem_clean_redirect(string $page='profil'): void{ header('Location: index.php?page='.rawurlencode($page)); exit; }
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
if(isset($_GET['logout'])){ unset($_SESSION['member_id']); mem_clear_login_step(); header('Location: index.php'); exit; }
if(isset($_GET['ulang'])){ mem_clear_login_step(); header('Location: index.php'); exit; }
$incomingClaim=strtoupper(trim((string)($_GET['claim'] ?? '')));
if($incomingClaim!=='') $_SESSION['member_prefill_claim']=$incomingClaim;
if (mem_current_id() <= 0 && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['login']) && !isset($_GET['ulang'])) {
    header('Location: hook.php' . ($incomingClaim !== '' ? '?claim=' . urlencode($incomingClaim) : ''));
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
      $_SESSION['member_id']=(int)$m['id']; $autoMsg=mem_auto_claim_pending($pdo,(int)$m['id']); mem_clear_login_step(); loyalty_activity($pdo,(int)$m['id'],$phone,'member_login','Login berhasil halaman member'); mem_flash('Berhasil masuk. Selamat datang.'.$autoMsg); mem_clean_redirect('profil');
    }elseif($action==='create_pin'){
      $phone=loyalty_normalize_phone((string)($_SESSION['member_login_phone'] ?? '')); $mode=(string)($_SESSION['member_login_mode'] ?? 'register'); $pin=trim((string)($_POST['pin'] ?? '')); $pin2=trim((string)($_POST['pin_confirm'] ?? ''));
      if(strlen($phone)<9) throw new Exception('Sesi nomor HP tidak ditemukan. Masukkan nomor HP kembali.');
      if(strlen($pin)<4) throw new Exception('PIN minimal 4 digit.'); if($pin!==$pin2) throw new Exception('Konfirmasi PIN belum sama.');
      $m=loyalty_find_member_by_phone($pdo,$phone);
      if($m){ if(!empty($m['pin_hash']) && $mode==='register') throw new Exception('Nomor ini sudah memiliki PIN. Silakan masuk dengan PIN.'); $pdo->prepare("UPDATE members SET pin_hash=?, updated_at=NOW() WHERE id=?")->execute([password_hash($pin,PASSWORD_DEFAULT),(int)$m['id']]); $_SESSION['member_id']=(int)$m['id']; loyalty_activity($pdo,(int)$m['id'],$phone,'member_pin_setup','Membuat PIN dari halaman member'); }
      else{ $m=loyalty_create_member($pdo,$phone,'',$pin,null); $_SESSION['member_id']=(int)$m['id']; loyalty_activity($pdo,(int)$m['id'],$phone,'member_register','Registrasi nomor dan PIN dari halaman member'); }
      $autoMsg=mem_auto_claim_pending($pdo,(int)$_SESSION['member_id']); mem_clear_login_step(); mem_flash('PIN berhasil disimpan. Silakan lengkapi data opsional untuk bonus point.'.$autoMsg); mem_clean_redirect('profil');
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
?>
<!doctype html><html lang="id"><head><link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Member Loyalty</title><style>
:root{--red:#c41230;--red2:#7a001b;--gold:#ffc72c;--cream:#fff7e8;--ink:#231f20;--muted:#766b60;--line:#ecd9b8;--green:#166534}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 10% -10%,rgba(255,199,44,.42),transparent 30%),linear-gradient(180deg,var(--cream),#fff);font-family:Inter,system-ui,-apple-system,Segoe UI,Arial,sans-serif;color:var(--ink);min-height:100vh}.wrap{width:min(1120px,94vw);margin:auto;padding:20px 0 56px}.hero{background:linear-gradient(135deg,var(--red),var(--red2));border-radius:30px;padding:20px;color:#fff;box-shadow:0 18px 60px rgba(124,0,24,.24);display:flex;justify-content:space-between;gap:14px;align-items:center}.brand{display:flex;gap:12px;align-items:center}.brand img{width:58px;height:58px;border-radius:18px;background:#fff;padding:7px;border:2px solid var(--gold)}h1{margin:0;font-size:31px;letter-spacing:-.04em}.hero p{margin:4px 0 0;color:rgba(255,255,255,.82);font-weight:800}.btn{border:0;border-radius:999px;padding:12px 16px;background:linear-gradient(135deg,var(--red),var(--red2));color:#fff;font-weight:950;cursor:pointer;text-decoration:none;display:inline-flex;justify-content:center;align-items:center}.btn.gold{background:var(--gold);color:#322000}.btn.ghost{background:#fff;color:var(--red);border:1px solid var(--line)}.card{background:#fff;border:1px solid var(--line);border-radius:26px;padding:18px;box-shadow:0 12px 34px rgba(88,49,2,.08);margin-top:16px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.form{display:grid;gap:12px}.form label{font-size:12px;font-weight:950;color:var(--muted);text-transform:uppercase}.form input,.form select,.form textarea{width:100%;border:1px solid var(--line);border-radius:16px;padding:12px;font-weight:850;min-height:46px;background:#fff}.form textarea{min-height:90px}.muted{color:var(--muted);font-weight:760;line-height:1.5}.points{font-size:52px;font-weight:1000;color:var(--red);letter-spacing:-.06em}.alert{border-radius:18px;padding:13px 15px;margin-top:14px;font-weight:880}.ok{background:#ecfdf5;color:#166534}.err{background:#fef2f2;color:#b91c1c}.table{width:100%;border-collapse:collapse}.table th,.table td{border-bottom:1px solid var(--line);padding:10px;text-align:left;vertical-align:top}.table th{font-size:12px;color:var(--muted);text-transform:uppercase}.big-phone{font-size:28px;font-weight:1000;color:var(--red)}.step{display:flex;gap:7px;margin:10px 0}.dot{width:10px;height:10px;border-radius:50%;background:#f1d9a9}.dot.on{background:var(--red)}.progress{height:10px;background:#f2dfbd;border-radius:999px;overflow:hidden}.progress span{display:block;height:100%;background:linear-gradient(90deg,var(--red),var(--gold))}.scanbox{display:grid;grid-template-columns:92px 1fr;gap:12px;align-items:center}.scanbox img{width:92px;height:92px;object-fit:contain}.nav{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0 0}.nav a{padding:10px 13px;border-radius:999px;background:rgba(255,255,255,.15);color:#fff;text-decoration:none;font-weight:950;border:1px solid rgba(255,255,255,.25)}.nav a.active{background:#fff;color:var(--red)}.reward-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.reward{display:grid;gap:10px}.reward img{width:100%;height:150px;object-fit:contain;background:#fff7e8;border-radius:18px;border:1px solid var(--line);padding:10px}.badge{display:inline-flex;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:900;background:#eef2ff;color:#3730a3}.badge.ok{background:#ecfdf5;color:#166534}.badge.warn{background:#fff7ed;color:#9a3412}.reward h3{margin:0;font-size:20px;line-height:1.15}.reward-meta{display:flex;gap:7px;flex-wrap:wrap;align-items:center}.reward-note{border:1px dashed var(--line);background:#fffaf0;border-radius:14px;padding:9px;font-size:12px;color:var(--muted);font-weight:800}.reward-actions{display:grid;gap:7px;margin-top:auto}.shortage{font-size:12px;color:#9a3412;font-weight:900}.btn:disabled{cursor:not-allowed;filter:grayscale(1);opacity:.48}@media(max-width:850px){.wrap{width:min(100%,93vw);padding:14px 0 44px}.grid,.grid3,.reward-grid{grid-template-columns:1fr;gap:14px}.hero{align-items:flex-start;flex-direction:column;padding:18px;border-radius:24px}.brand{flex-direction:column;align-items:flex-start;gap:10px}.brand img{width:48px;height:48px}h1{font-size:24px}.nav{display:flex;gap:6px;overflow-x:auto;width:100%;padding-bottom:4px;-webkit-overflow-scrolling:touch}.nav a{white-space:nowrap;font-size:13px;padding:9px 14px}.vip-card{padding:18px;border-radius:22px;margin-top:14px}.vip-header{flex-direction:column;align-items:flex-start;gap:14px}.vip-header > div:last-child{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}.vip-header .btn{width:100%;text-align:center;justify-content:center}.vip-points{font-size:38px}.vip-meta{flex-direction:column;gap:8px;font-size:12px}.voucher-box{padding:18px;border-radius:22px}}@media(max-width:480px){.vip-points{font-size:33px}.points{font-size:38px}.hero p{font-size:13px}}
.vip-card{background:linear-gradient(135deg,#690014 0%,#a80824 55%,#d9822b 100%);border-radius:28px;padding:26px;color:#fff;box-shadow:0 20px 45px rgba(105,0,20,.24);position:relative;overflow:hidden;margin-top:18px;border:1px solid rgba(255,199,44,.45)}.vip-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px}.vip-badge{background:var(--gold);color:#322000;font-weight:1000;font-size:11px;padding:6px 14px;border-radius:999px;letter-spacing:.08em;text-transform:uppercase;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(255,199,44,.35)}.vip-body{display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}.vip-points{font-size:52px;font-weight:1000;color:#fff;line-height:1;letter-spacing:-.05em;margin:6px 0 4px}.vip-meta{display:flex;gap:20px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid rgba(255,255,255,.18);font-size:13px;color:rgba(255,255,255,.88);font-weight:800}.voucher-box{background:linear-gradient(145deg,#fffbf2,#ffffff);border:2px dashed #e4be7a;border-radius:26px;padding:24px;box-shadow:0 14px 36px rgba(180,120,20,.08);position:relative}
.activity-list{display:grid;gap:12px;margin-top:14px}.activity-card{display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid var(--line);border-radius:20px;padding:15px 18px;gap:12px;transition:transform .18s,box-shadow .18s}.activity-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.06)}.activity-icon{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}.activity-icon.order{background:#fff7e8;border:1px solid #ebd3a8}.activity-icon.in{background:#ecfdf5;border:1px solid #a7f3d0}.activity-icon.out{background:#fef2f2;border:1px solid #fecaca}.reward-card{display:flex;flex-direction:column;background:#fff;border:1px solid var(--line);border-radius:26px;overflow:hidden;box-shadow:0 12px 34px rgba(88,49,2,.06);transition:all .22s}.reward-card:hover{transform:translateY(-4px);box-shadow:0 18px 45px rgba(124,0,24,.12)}.reward-img-wrap{position:relative;height:180px;background:#fffaf0;overflow:hidden;display:flex;align-items:center;justify-content:center;padding:14px}.reward-img-wrap img{width:100%;height:100%;object-fit:contain;transition:transform .3s}.reward-card:hover .reward-img-wrap img{transform:scale(1.08)}
</style></head><body><div class="wrap"><header class="hero"><div class="brand"><img src="../public/assets/images/pos-products/icon-192.png" alt="Logo Lumero"><div><h1>Member Loyalty</h1><p>Klaim bonus, cek saldo point, dan tukar reward member.</p><?php if($member): ?><nav class="nav"><a class="<?=$page==='profil'?'active':''?>" href="?page=profil">Profil</a><a class="<?=$page==='riwayat'?'active':''?>" href="?page=riwayat">Riwayat Transaksi</a><a class="<?=$page==='penukaran'?'active':''?>" href="?page=penukaran">Penukaran Point</a><a href="redemption-history.php">Riwayat Penukaran</a><a href="online-order.php">Online Order</a></nav><?php endif; ?></div></div><?php if($member): ?><a class="btn gold" href="?logout=1">Logout</a><?php endif; ?></header><?php if($msg): ?><div class="alert ok"><?=mem_e($msg)?></div><?php endif; ?><?php if($err): ?><div class="alert err"><?=mem_e($err)?></div><?php endif; ?>
<?php if(!$member): ?>
<section class="grid"><div class="card"><h2>Masuk Member</h2><div class="step"><span class="dot on"></span><span class="dot <?=($pendingPhone?'on':'') ?>"></span><span class="dot"></span></div><?php if(!$pendingPhone): ?><p class="muted">Masukkan nomor HP. Sistem akan memeriksa apakah nomor sudah terdaftar.</p><form method="post" class="form" autocomplete="off"><input type="hidden" name="csrf" value="<?=mem_e($csrf)?>"><input type="hidden" name="action" value="check_phone"><div><label>Nomor HP</label><input name="phone" inputmode="tel" required placeholder="08xxxxxxxxxx" autofocus></div><button class="btn">Masuk</button></form><?php else: ?><p class="muted">Nomor yang digunakan:</p><div class="big-phone"><?=mem_e(loyalty_mask_phone($pendingPhone))?></div><br><?php if($pendingMode==='pin'): ?><form method="post" class="form" autocomplete="off"><input type="hidden" name="csrf" value="<?=mem_e($csrf)?>"><input type="hidden" name="action" value="login_pin"><div><label>PIN Member</label><input name="pin" type="password" inputmode="numeric" required placeholder="Masukkan PIN" autofocus></div><button class="btn">Masuk ke Member</button><a class="btn ghost" href="?ulang=1">Ganti Nomor</a></form><?php elseif($pendingMode==='verify_otp'): ?><form method="post" class="form" autocomplete="off"><input type="hidden" name="csrf" value="<?=mem_e($csrf)?>"><input type="hidden" name="action" value="verify_otp"><div><label>Kode OTP WhatsApp</label><input name="otp" type="text" inputmode="numeric" maxlength="6" required placeholder="123456" autofocus></div><small class="muted">Kode 6 digit telah dikirim ke WhatsApp Anda. <?php if(isset($_SESSION['debug_wa_otp'])) echo '<span style="color:#d97706;"><b>[DEBUG LOKAL: '.$_SESSION['debug_wa_otp'].']</b></span>'; ?></small><button class="btn">Verifikasi OTP</button><a class="btn ghost" href="?ulang=1">Ganti Nomor</a></form><?php else: ?><p class="muted"><?=($pendingMode==='setup')?'Akun ini belum memiliki PIN. Buat PIN untuk aktivasi.':'Nomor baru. Buat PIN untuk mendaftar sebagai member.'?></p><form method="post" class="form" autocomplete="off"><input type="hidden" name="csrf" value="<?=mem_e($csrf)?>"><input type="hidden" name="action" value="create_pin"><div><label>Buat PIN</label><input name="pin" type="password" inputmode="numeric" required placeholder="Minimal 4 digit" autofocus></div><div><label>Ulangi PIN</label><input name="pin_confirm" type="password" inputmode="numeric" required placeholder="Masukkan kembali PIN"></div><button class="btn">Simpan PIN & Masuk</button><a class="btn ghost" href="?ulang=1">Ganti Nomor</a></form><?php endif; ?><?php endif; ?></div><div class="card"><h2>Klaim Bonus dari Struk</h2><div style="display:flex; gap:14px; align-items:center; margin-top:10px;"><div style="font-size:36px; background:#fffaf0; padding:12px; border-radius:18px; border:1px solid var(--line);">🎁</div><div><?php if($prefillClaim!==''): ?><b>Struk Terdeteksi: <?=mem_e($prefillClaim)?></b><p class="muted" style="margin:4px 0 0;">Poin dari struk ini akan langsung ditambahkan ke saldo Anda secara otomatis setelah berhasil masuk atau mendaftar di atas.</p><?php else: ?><b>Punya Struk Belanja Lumero?</b><p class="muted" style="margin:4px 0 0;">Scan QR di struk belanja Anda atau masukkan kodenya di menu profil setelah login untuk mengumpulkan poin & hadiah hidangan gratis!</p><?php endif; ?></div></div></div></section>
<?php else: ?>
<div class="vip-card">
  <div class="vip-header">
    <div>
      <span class="vip-badge">👑 LUMERO VIP MEMBER</span>
      <div style="font-size:22px; font-weight:1000; margin-top:8px;"><?=mem_e($member['name'] ?: 'Member Setia Lumero')?></div>
      <div style="font-size:14px; color:rgba(255,255,255,.75); font-weight:800;"><?=mem_e(loyalty_mask_phone($member['phone']))?></div>
    </div>
    <div style="display:flex; gap:8px;">
      <a class="btn gold" href="?page=penukaran" style="padding:10px 18px; font-size:13px;">🎁 Tukar Poin</a>
      <a class="btn" href="?page=riwayat" style="background:rgba(0,0,0,.28); border:1px solid rgba(255,255,255,.3); padding:10px 16px; font-size:13px;">📜 Riwayat</a>
    </div>
  </div>
  <div class="vip-body">
    <div>
      <div style="font-size:12px; text-transform:uppercase; letter-spacing:.08em; color:rgba(255,255,255,.78); font-weight:900;">Saldo Poin Aktif</div>
      <div class="vip-points">✨ <?=number_format((int)$member['total_points'],0,',','.')?></div>
    </div>
  </div>
  <div class="vip-meta">
    <span>🛍️ <?=number_format((int)$member['total_transactions'],0,',','.')?>x Transaksi Belanja</span>
    <span>💰 Total Belanja: <?=mem_money((int)$member['total_spent'])?></span>
    <span>📋 Kelengkapan Profil: <?=$profilePercent?>%</span>
  </div>
</div>
<?php if($page==='profil'): ?>
<section class="grid" style="margin-top:18px;">
  <div class="voucher-box">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
      <div style="font-size:36px; background:#fff7e8; padding:10px; border-radius:18px; border:1px solid #ecc988;">🎟️</div>
      <div>
        <h2 style="margin:0; font-size:20px;">Klaim Poin Struk</h2>
        <span style="font-size:12px; font-weight:800; color:#9a3412;">Voucher Poin Belanja Lumero</span>
      </div>
    </div>
    <p class="muted" style="margin-bottom:16px;">Masukkan kode unik pada struk belanja kasir Anda (contoh: <b>CLM-XXXXXX</b>) untuk langsung mencairkan poin bonus ke dalam Kartu VIP Anda.</p>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
      <input type="hidden" name="action" value="claim">
      <div>
        <label>Kode Struk Belanja</label>
        <input name="claim_code" value="<?=mem_e($prefillClaim)?>" required placeholder="Contoh: CLM-8F29KQ" style="text-transform:uppercase; font-size:16px; letter-spacing:.06em;">
      </div>
      <button class="btn" style="width:100%; margin-top:4px; padding:14px; font-size:15px;">⚡ Klaim Poin Sekarang</button>
    </form>
  </div>
  <?php if(!$profileComplete): ?>
  <div class="card" style="margin-top:0;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
      <div style="width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg,var(--red),var(--red2)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:1000;"><?=mem_e(strtoupper(substr($member['name'] ?: 'M',0,1)))?></div>
      <div>
        <h2 style="margin:0; font-size:20px;">Lengkapi Identitas</h2>
        <span style="font-size:12px; font-weight:800; color:var(--green);">Dapatkan Bonus +<?=$bonusPoints?> Poin Sekali Klaim!</span>
      </div>
    </div>
    <p class="muted" style="margin-bottom:14px;">Lengkapi data diri Anda untuk keamanan akun dan pengiriman hadiah ulang tahun spesial dari Lumero POS.</p>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
      <input type="hidden" name="action" value="update_profile">
      <div><label>Nama Lengkap</label><input name="name" value="<?=mem_e($member['name'] ?? '')?>" placeholder="Nama member"></div>
      <div><label>Email</label><input name="email" type="email" value="<?=mem_e($member['email'] ?? '')?>" placeholder="email@contoh.com"></div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        <div>
          <label>Jenis Kelamin</label>
          <select name="gender"><option value="">Pilih</option><option value="pria" <?=($member['gender']??'')==='pria'?'selected':''?>>Pria</option><option value="wanita" <?=($member['gender']??'')==='wanita'?'selected':''?>>Wanita</option><option value="lainnya" <?=($member['gender']??'')==='lainnya'?'selected':''?>>Lainnya</option></select>
        </div>
        <div>
          <label>Tanggal Lahir</label>
          <input name="birth_date" type="date" value="<?=mem_e($member['birth_date'] ?? '')?>">
        </div>
      </div>
      <div><label>Alamat / Kota Domisili</label><textarea name="address" placeholder="Alamat singkat / area domisili" style="min-height:64px;"><?=mem_e($member['address'] ?? '')?></textarea></div>
      <button class="btn" style="width:100%; margin-top:6px;">💾 Simpan & Ambil Bonus Poin</button>
    </form>
  </div>
  <?php else: ?>
  <div class="card" style="margin-top:0;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
      <div style="width:50px; height:50px; border-radius:50%; background:linear-gradient(135deg,var(--red),var(--red2)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:1000;"><?=mem_e(strtoupper(substr($member['name'] ?: 'M',0,1)))?></div>
      <div>
        <h2 style="margin:0; font-size:20px;"><?=mem_e($member['name'] ?: 'Member Setia')?></h2>
        <span class="badge ok">✓ Profil Lengkap & Terverifikasi</span>
      </div>
    </div>
    <table class="table">
      <tr><th>Nomor WhatsApp</th><td><b><?=mem_e(loyalty_mask_phone($member['phone']))?></b></td></tr>
      <tr><th>Email</th><td><?=mem_e($member['email'] ?: '-')?></td></tr>
      <tr><th>Tanggal Lahir</th><td><?=!empty($member['birth_date']) ? mem_e(date('d/m/Y',strtotime($member['birth_date']))) : '-'?></td></tr>
      <tr><th>Alamat</th><td><?=mem_e($member['address'] ?: '-')?></td></tr>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php elseif($page==='riwayat'): ?>
<section class="grid" style="margin-top:16px;">
  <div class="card" style="margin-top:0;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <div>
        <h2 style="margin:0; font-size:20px;">🛍️ Riwayat Belanja</h2>
        <span class="muted" style="font-size:12px;">Daftar pesanan & poin yang Anda peroleh</span>
      </div>
      <span class="badge" style="background:#fff7e8; color:#9a3412;"><?=count($orders)?> Transaksi</span>
    </div>
    <div class="activity-list">
      <?php foreach($orders as $o): ?>
      <div class="activity-card">
        <div style="display:flex; align-items:center; gap:14px;">
          <div class="activity-icon order">🛍️</div>
          <div>
            <div style="font-weight:950; font-size:15px;"><?=mem_e($o['order_no'])?></div>
            <div style="display:flex; gap:6px; align-items:center; margin-top:3px;">
              <span class="badge" style="font-size:10px; padding:3px 7px;"><?=mem_e(strtoupper($o['payment_method']))?></span>
              <span class="muted" style="font-size:11px;"><?=mem_e(date('d M Y, H:i',strtotime($o['created_at'])))?></span>
            </div>
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-weight:1000; font-size:15px; color:var(--ink);"><?=mem_money($o['total'])?></div>
          <?php if((int)($o['loyalty_points_earned'] ?? 0) > 0): ?>
          <span class="badge ok" style="font-size:11px; margin-top:4px;">+<?=(int)$o['loyalty_points_earned']?> Poin</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(!$orders): ?>
      <div style="text-align:center; padding:36px 16px; background:#fffaf0; border-radius:18px; border:1px dashed var(--line);">
        <div style="font-size:32px; margin-bottom:8px;">🛒</div>
        <b style="color:var(--ink);">Belum ada riwayat belanja</b>
        <p class="muted" style="font-size:13px; margin:4px 0 0;">Yuk lakukan transaksi pertama di outlet Lumero dan nikmati poin rewardnya!</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="margin-top:0;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <div>
        <h2 style="margin:0; font-size:20px;">✨ Mutasi Poin</h2>
        <span class="muted" style="font-size:12px;">Catatan masuk & keluar poin loyalty</span>
      </div>
      <span class="badge ok"><?=count($logs)?> Aktivitas</span>
    </div>
    <div class="activity-list">
      <?php foreach($logs as $l): $isIn = ((int)$l['points_in'] > 0); ?>
      <div class="activity-card">
        <div style="display:flex; align-items:center; gap:14px;">
          <div class="activity-icon <?=$isIn?'in':'out'?>"><?=$isIn?'🪙':'🎁'?></div>
          <div>
            <div style="font-weight:950; font-size:14px; color:var(--ink);"><?=mem_e($l['type'])?></div>
            <div class="muted" style="font-size:12px; margin-top:2px;"><?=mem_e($l['description'])?></div>
            <div class="muted" style="font-size:11px; margin-top:3px;"><?=mem_e(date('d M Y, H:i',strtotime($l['created_at'])))?></div>
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-weight:1000; font-size:16px; color:<?=$isIn?'var(--green)':'var(--red)'?>;">
            <?=$isIn?('+'.(int)$l['points_in']):('-'.(int)$l['points_out'])?> Poin
          </div>
          <div class="muted" style="font-size:11px; margin-top:2px;">Saldo: <?=(int)$l['balance_after']?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(!$logs): ?>
      <div style="text-align:center; padding:36px 16px; background:#fffaf0; border-radius:18px; border:1px dashed var(--line);">
        <div style="font-size:32px; margin-bottom:8px;">💎</div>
        <b style="color:var(--ink);">Belum ada riwayat poin</b>
        <p class="muted" style="font-size:13px; margin:4px 0 0;">Poin yang Anda dapatkan atau tukarkan akan otomatis tercatat di sini.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php elseif($page==='penukaran'): ?>
<section class="card" style="background:linear-gradient(135deg,#fffbf2,#ffffff); border:2px solid #f2dfbd; border-radius:26px; padding:22px;">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
      <span class="badge" style="background:var(--gold); color:#322000; font-weight:1000; margin-bottom:6px;">🎁 KATALOG REWARD EKSKLUSIF</span>
      <h2 style="margin:0; font-size:24px;">Tukarkan Poin Jadi Hidangan Gratis</h2>
      <p class="muted" style="margin:4px 0 0; font-size:13px;">Pilih menu reward impian Anda. Setelah diklaim, tunjukkan tiket digital penukaran ke kasir kami.</p>
    </div>
    <div style="background:#fff; border:1px solid var(--line); border-radius:18px; padding:10px 18px; text-align:right; box-shadow:0 4px 14px rgba(0,0,0,.04);">
      <span class="muted" style="font-size:11px; display:block;">SALDO POIN ANDA</span>
      <strong style="font-size:24px; color:var(--red); font-weight:1000;">✨ <?=number_format((int)$member['total_points'],0,',','.')?></strong>
    </div>
  </div>
</section>

<section class="reward-grid" style="margin-top:16px;">
  <?php foreach($rewardProducts as $r): 
    $need=(int)$r['required_points']; 
    $saldo=(int)$member['total_points']; 
    $img=trim((string)($r['image_url'] ?? '')); 
    if($img==='' && !empty($r['source_menu_image_url'])) $img=trim((string)$r['source_menu_image_url']); 
    $stockLimited=($r['stock_qty'] !== null && $r['stock_qty'] !== ''); 
    $stockHabis=$stockLimited && (int)$r['stock_qty']<=0; 
    $notEnough=$saldo<$need; 
    $disabled=$stockHabis || $notEnough; 
  ?>
  <div class="reward-card">
    <div class="reward-img-wrap">
      <img src="<?=mem_e(mem_reward_img_src($img, $r['name'] ?? ''))?>" alt="<?=mem_e($r['name'])?>">
      <div style="position:absolute; top:12px; left:12px; display:flex; gap:6px; flex-wrap:wrap;">
        <?php if(!empty($r['category'])): ?>
        <span class="badge" style="background:#fff; color:var(--ink); box-shadow:0 2px 8px rgba(0,0,0,.1);"><?=mem_e($r['category'])?></span>
        <?php endif; ?>
      </div>
      <div style="position:absolute; bottom:12px; right:12px;">
        <span class="badge ok" style="background:var(--red); color:#fff; font-size:13px; font-weight:1000; box-shadow:0 4px 12px rgba(196,18,48,.3);">✨ <?=number_format($need,0,',','.')?> Poin</span>
      </div>
    </div>
    <div style="padding:18px; display:flex; flex-direction:column; flex:1; gap:10px;">
      <div>
        <h3 style="margin:0; font-size:19px; color:var(--ink);"><?=mem_e($r['name'])?></h3>
        <?php if(!empty($r['description'])): ?>
        <p class="muted" style="margin:6px 0 0; font-size:13px; line-height:1.45;"><?=mem_e($r['description'])?></p>
        <?php endif; ?>
      </div>

      <?php if(!empty($r['terms'])): ?>
      <div class="reward-note" style="font-size:11px;">📌 <?=mem_e($r['terms'])?></div>
      <?php endif; ?>

      <form method="post" style="margin-top:auto; display:grid; gap:8px; padding-top:8px;">
        <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
        <input type="hidden" name="action" value="redeem_reward">
        <input type="hidden" name="reward_id" value="<?=(int)$r['id']?>">
        
        <?php if($stockHabis): ?>
          <div class="shortage" style="background:#fef2f2; padding:8px 12px; border-radius:12px; text-align:center;">🚫 Stok reward sedang habis</div>
        <?php elseif($notEnough): ?>
          <div class="shortage" style="background:#fff7ed; padding:8px 12px; border-radius:12px; text-align:center;">⏳ Kurang <?=number_format($need-$saldo,0,',','.')?> poin lagi</div>
        <?php else: ?>
          <div style="background:#ecfdf5; color:#166534; font-size:12px; font-weight:850; padding:8px 12px; border-radius:12px; text-align:center;">🎉 Poin cukup untuk klaim ini!</div>
        <?php endif; ?>

        <button class="btn" <?=$disabled?'disabled':''?> style="width:100%; font-size:14px; padding:13px;">🎁 Tukar Sekarang</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if(!$rewardProducts): ?>
  <div style="grid-column:1/-1; text-align:center; padding:50px 20px; background:#fff; border-radius:26px; border:1px dashed var(--line);">
    <div style="font-size:48px; margin-bottom:12px;">🎁</div>
    <h3 style="margin:0; font-size:20px;">Belum Ada Produk Penukaran</h3>
    <p class="muted" style="max-width:400px; margin:8px auto 0;">Produk reward baru sedang disiapkan oleh admin. Cek kembali secara berkala!</p>
  </div>
  <?php endif; ?>
</section>

<section class="card" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; background:linear-gradient(135deg,#fff,#fffaf0);">
  <div>
    <h2 style="margin:0; font-size:20px;">🎟️ Sudah Menukarkan Poin?</h2>
    <p class="muted" style="margin:4px 0 0; font-size:13px;">Buka halaman tiket digital Anda untuk ditunjukkan kepada kasir saat pengambilan hadiah.</p>
  </div>
  <a class="btn gold" href="redemption-history.php" style="padding:12px 22px; font-size:14px;">Buka Riwayat Penukaran →</a>
</section>
<?php endif; ?>
<?php endif; ?></div></body></html>

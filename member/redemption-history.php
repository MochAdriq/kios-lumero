<?php
require_once __DIR__.'/../helpers/functions.php';
require_once __DIR__.'/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__.'/../config/loyalty.php';
date_default_timezone_set('Asia/Jakarta');
try{ if(isset($pdo) && $pdo instanceof PDO) $pdo->exec("SET time_zone = '+07:00'"); }catch(Throwable $e){}
loyalty_ensure_tables($pdo);
function rh_e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function rh_money($n){ return function_exists('rupiah') ? rupiah((int)$n) : 'Rp'.number_format((int)$n,0,',','.'); }
function rh_status_label($s){
  $map=['requested'=>'Menunggu Diserahkan','approved'=>'Disetujui','completed'=>'Sudah Diserahkan','cancelled'=>'Dibatalkan'];
  return $map[$s] ?? $s;
}
function rh_status_class($s){ return $s==='completed'?'ok':($s==='cancelled'?'danger':($s==='approved'?'info':'warn')); }
$memberId=(int)($_SESSION['member_id'] ?? 0);
if($memberId<=0){ header('Location: index.php?page=penukaran'); exit; }
$member=loyalty_member_by_id($pdo,$memberId);
if(!$member){ unset($_SESSION['member_id']); header('Location: index.php'); exit; }
$redemptions=loyalty_member_reward_redemptions($pdo,$memberId,120);
?>
<!doctype html><html lang="id"><head><link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tiket Penukaran Hadiah - Lumero</title>
<style>
:root{--red:#c41230;--red2:#7a001b;--gold:#ffc72c;--cream:#fff7e8;--ink:#231f20;--muted:#766b60;--line:#ecd9b8;--green:#166534}
*{box-sizing:border-box}
body{margin:0;background:radial-gradient(circle at 12% -12%,rgba(255,199,44,.44),transparent 32%),linear-gradient(180deg,var(--cream),#fff);font-family:Inter,system-ui,-apple-system,Segoe UI,Arial,sans-serif;color:var(--ink);min-height:100vh}
.wrap{width:min(1080px,94vw);margin:auto;padding:20px 0 56px}
.hero{background:linear-gradient(135deg,var(--red),var(--red2));border-radius:30px;padding:22px;color:#fff;box-shadow:0 18px 60px rgba(124,0,24,.24);display:flex;justify-content:space-between;gap:14px;align-items:center}
.brand{display:flex;gap:12px;align-items:center}
.brand img{width:58px;height:58px;border-radius:18px;background:#fff;padding:7px;border:2px solid var(--gold)}
h1{margin:0;font-size:29px;letter-spacing:-.04em}
.hero p{margin:4px 0 0;color:rgba(255,255,255,.85);font-weight:800}
.nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.nav a{padding:10px 14px;border-radius:999px;background:rgba(255,255,255,.15);color:#fff;text-decoration:none;font-weight:950;border:1px solid rgba(255,255,255,.25)}
.nav a.active{background:#fff;color:var(--red)}
.btn{border:0;border-radius:999px;padding:12px 18px;background:linear-gradient(135deg,var(--red),var(--red2));color:#fff;font-weight:950;cursor:pointer;text-decoration:none;display:inline-flex;justify-content:center;align-items:center}
.btn.gold{background:var(--gold);color:#322000}
.card{background:#fff;border:1px solid var(--line);border-radius:26px;padding:20px;box-shadow:0 12px 34px rgba(88,49,2,.06);margin-top:16px}
.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:16px}
.summary-card{background:#fff;border:1px solid var(--line);border-radius:24px;padding:18px;position:relative;overflow:hidden;box-shadow:0 8px 24px rgba(88,49,2,.05)}
.summary-card .num{font-size:34px;font-weight:1000;color:var(--red);letter-spacing:-.05em;margin-top:4px}
.muted{color:var(--muted);font-weight:760;line-height:1.5}

/* Digital Ticket Coupon Styling */
.ticket-list{display:grid;gap:16px;margin-top:16px}
.ticket-coupon{display:grid;grid-template-columns:1fr 220px;background:#fff;border:2px solid #ebd3a8;border-radius:24px;overflow:hidden;box-shadow:0 12px 36px rgba(180,120,20,.09);position:relative;transition:transform .2s}
.ticket-coupon:hover{transform:translateY(-3px)}
.ticket-body{padding:22px;display:flex;flex-direction:column;justify-content:space-between;gap:14px}
.ticket-qr-panel{background:linear-gradient(135deg,#fffbf2,#fff);border-left:2px dashed #ebd3a8;padding:20px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:10px}
.code-box{font-size:26px;font-weight:1000;letter-spacing:.06em;color:var(--red);background:#fff7e8;padding:8px 16px;border-radius:14px;border:1px solid #ebd3a8;display:inline-block}
.badge{display:inline-flex;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}
.badge.ok{background:#ecfdf5;color:#166534}
.badge.warn{background:#fff7ed;color:#9a3412}
.badge.danger{background:#fef2f2;color:#b91c1c}
.badge.info{background:#eef2ff;color:#3730a3}
.qr{width:130px;height:130px;object-fit:contain;background:#fff;border-radius:16px;border:1px solid var(--line);padding:8px;box-shadow:0 4px 14px rgba(0,0,0,.06)}

@media(max-width:850px){
  .wrap{width:min(100%,93vw);padding:14px 0 44px}
  .hero{align-items:flex-start;flex-direction:column;padding:18px;border-radius:24px}
  .brand{flex-direction:column;align-items:flex-start;gap:10px}
  .brand img{width:48px;height:48px}
  h1{font-size:24px}
  .nav{display:flex;gap:6px;overflow-x:auto;width:100%;padding-bottom:4px;-webkit-overflow-scrolling:touch}
  .nav a{white-space:nowrap;font-size:13px;padding:9px 14px}
  .summary{grid-template-columns:1fr}
  .ticket-coupon{grid-template-columns:1fr}
  .ticket-qr-panel{border-left:none;border-top:2px dashed #ebd3a8;padding:18px}
}
@media print{
  body{background:#fff}
  .hero,.nav,.no-print{display:none}
  .wrap{width:100%;padding:0}
  .ticket-coupon{break-inside:avoid;box-shadow:none;margin-bottom:12px}
  .btn{display:none}
}
</style></head>
<body><div class="wrap">
  <header class="hero">
    <div class="brand">
      <img src="../public/assets/images/pos-products/icon-192.png" alt="Logo Lumero">
      <div>
        <h1>Dompet Tiket Penukaran</h1>
        <p>Tunjukkan QR / Kode tiket di bawah ini kepada Kasir saat mengambil hadiah.</p>
        <nav class="nav">
          <a href="index.php?page=profil">Profil</a>
          <a href="index.php?page=riwayat">Riwayat Transaksi</a>
          <a href="index.php?page=penukaran">Penukaran Point</a>
          <a class="active" href="redemption-history.php">Riwayat Penukaran</a>
          <a href="online-order.php">Online Order</a>
        </nav>
      </div>
    </div>
    <a class="btn gold" href="index.php?logout=1">Logout</a>
  </header>

  <section class="summary">
    <div class="summary-card">
      <span class="muted" style="font-size:12px; text-transform:uppercase;">MEMBER LOMERO</span>
      <div class="num" style="font-size:24px;"><?=rh_e($member['name'] ?: 'Member Setia')?></div>
      <p class="muted" style="margin:4px 0 0; font-size:13px;"><?=rh_e(loyalty_mask_phone($member['phone']))?></p>
    </div>
    <div class="summary-card">
      <span class="muted" style="font-size:12px; text-transform:uppercase;">SALDO POIN AKTIF</span>
      <div class="num">✨ <?=number_format((int)$member['total_points'],0,',','.')?></div>
      <p class="muted" style="margin:4px 0 0; font-size:13px;">Siap ditukarkan dengan hadiah</p>
    </div>
    <div class="summary-card">
      <span class="muted" style="font-size:12px; text-transform:uppercase;">TOTAL TIKET HADIAH</span>
      <div class="num" style="color:var(--ink);"><?=number_format(count($redemptions),0,',','.')?></div>
      <p class="muted" style="margin:4px 0 0; font-size:13px;">Klaim penukaran tercatat</p>
    </div>
  </section>

  <section class="card" style="margin-top:16px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <div>
        <h2 style="margin:0; font-size:22px;">🎟️ Tiket Digital Pengambilan Hadiah</h2>
        <p class="muted" style="margin:4px 0 0; font-size:13px;">Kasir akan memindai (scan) QR atau memasukkan kode tiket di POS Lumero saat penyerahan hadiah.</p>
      </div>
      <button class="btn gold no-print" onclick="window.print()" style="font-size:13px; padding:10px 16px;">🖨️ Cetak / Simpan PDF</button>
    </div>

    <div class="ticket-list">
      <?php foreach($redemptions as $r): 
        $code=(string)($r['redemption_code'] ?: ('RDM-'.$r['id'])); 
        $qr='https://quickchart.io/qr?size=180&margin=1&text='.rawurlencode($code); 
      ?>
      <article class="ticket-coupon">
        <div class="ticket-body">
          <div>
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
              <span class="badge <?=rh_status_class($r['status'])?>"><?=rh_e(rh_status_label($r['status']))?></span>
              <span class="muted" style="font-size:12px;">📅 <?=rh_e(date('d M Y, H:i',strtotime($r['created_at'])))?></span>
            </div>
            <h3 style="margin:12px 0 6px; font-size:23px; color:var(--ink);"><?=rh_e($r['product_name'] ?? 'Hadiah Spesial')?></h3>
            <div class="code-box"><?=rh_e($code)?></div>
          </div>

          <div style="border-top:1px solid var(--line); padding-top:12px; display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; font-size:13px;">
            <div>
              <span class="muted">Poin Dipakai:</span>
              <strong style="color:var(--red);">✨ <?=number_format((int)$r['points_used'],0,',','.')?> Poin</strong>
            </div>
            <div>
              <span class="muted">No. Transaksi Kasir:</span>
              <strong><?=rh_e($r['order_no'] ?: '-')?></strong>
            </div>
            <?php if(!empty($r['completed_at'])): ?>
            <div>
              <span class="muted">Diserahkan Pada:</span>
              <strong style="color:var(--green);">✓ <?=rh_e(date('d/m/Y H:i',strtotime($r['completed_at'])))?></strong>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="ticket-qr-panel">
          <img class="qr" src="<?=rh_e($qr)?>" alt="QR kode penukaran">
          <span style="font-size:11px; font-weight:850; color:var(--ink);">Scan QR di Kasir</span>
        </div>
      </article>
      <?php endforeach; ?>

      <?php if(!$redemptions): ?>
      <div style="text-align:center; padding:54px 20px; background:#fffaf0; border-radius:24px; border:1px dashed var(--line);">
        <div style="font-size:48px; margin-bottom:12px;">🎟️</div>
        <h3 style="margin:0; font-size:20px;">Belum Ada Tiket Hadiah</h3>
        <p class="muted" style="max-width:420px; margin:8px auto 16px;">Anda belum menukarkan poin dengan hadiah. Buka katalog penukaran sekarang!</p>
        <a class="btn" href="index.php?page=penukaran">🎁 Lihat Katalog Penukaran Poin</a>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div></body></html>

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
function rh_status_class($s){ return $s==='completed'?'badge-green':($s==='cancelled'?'badge-red':($s==='approved'?'badge-blue':'badge-gold')); }
$memberId=(int)($_SESSION['member_id'] ?? 0);
if($memberId<=0){ header('Location: index.php?page=penukaran'); exit; }
$member=loyalty_member_by_id($pdo,$memberId);
if(!$member){ unset($_SESSION['member_id']); header('Location: index.php'); exit; }
$redemptions=loyalty_member_reward_redemptions($pdo,$memberId,120);
?>
<!doctype html><html lang="id"><head>
<link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png">
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Riwayat Penukaran — Lumero Member</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --red: #c41230; --red2: #7a001b; --gold: #ffc72c; --gold2: #f59e0b;
  --ink: #0f172a; --muted: #64748b; --subtle: #94a3b8;
  --bg: #fafafa; --surface: #ffffff; --border: #e5e7eb; --border-light: #f3f4f6;
  --green: #16a34a; --green-bg: #f0fdf4; --green-border: #bbf7d0;
  --red-bg: #fef2f2; --red-border: #fecaca;
  --gold-bg: #fffbeb; --gold-border: #fde68a;
  --radius: 24px; --radius-sm: 16px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', system-ui, sans-serif;
  background: var(--bg); color: var(--ink); min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}
a { text-decoration: none; color: inherit; }

/* Topbar */
.topbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(250,250,250,0.85); backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0,0,0,0.04);
}
.topbar-inner {
  max-width: 1080px; margin: 0 auto; padding: 14px 24px;
  display: flex; justify-content: space-between; align-items: center;
}
.topbar-brand { display: flex; align-items: center; gap: 12px; }
.topbar-brand img { width: 36px; height: 36px; border-radius: 10px; }
.topbar-brand span { font-size: 16px; font-weight: 800; letter-spacing: -0.02em; }
.topbar-logout {
  font-size: 13px; font-weight: 700; color: var(--red); background: var(--red-bg);
  border: 1px solid var(--red-border); border-radius: 99px; padding: 8px 16px;
  transition: all 0.2s;
}
.topbar-logout:hover { background: var(--red); color: #fff; border-color: var(--red); }

/* Nav */
.nav-tabs {
  max-width: 1080px; margin: 0 auto; padding: 0 24px;
  display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none;
}
.nav-tabs::-webkit-scrollbar { display: none; }
.nav-tab {
  white-space: nowrap; padding: 10px 18px; border-radius: 99px;
  font-size: 13px; font-weight: 700; color: var(--muted);
  background: transparent; border: 1px solid transparent; transition: all 0.25s;
}
.nav-tab:hover { background: var(--surface); color: var(--ink); border-color: var(--border); }
.nav-tab.active { background: var(--red); color: #fff; border-color: var(--red); box-shadow: 0 4px 16px rgba(196,18,48,0.2); }

/* Page */
.page { max-width: 1080px; margin: 0 auto; padding: 32px 24px 80px; }

/* Reveal */
.reveal {
  opacity: 0; transform: translateY(32px);
  transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1);
}
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }

/* Summary Cards */
.summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
.summary-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
  padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.summary-label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; }
.summary-value { font-size: 28px; font-weight: 900; letter-spacing: -0.03em; margin-top: 6px; color: var(--ink); }
.summary-note { font-size: 13px; font-weight: 600; color: var(--muted); margin-top: 4px; }

/* Badge */
.badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 99px;
  background: var(--border-light); color: var(--ink);
}
.badge-green { background: var(--green-bg); color: var(--green); }
.badge-red { background: var(--red-bg); color: #991b1b; }
.badge-gold { background: var(--gold-bg); color: #92400e; }
.badge-blue { background: #eff6ff; color: #1d4ed8; }

/* Ticket */
.ticket-list { display: grid; gap: 20px; }
.ticket {
  display: grid; grid-template-columns: 1fr 220px;
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
  overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.03);
  transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.ticket:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(0,0,0,0.06); }
.ticket-body { padding: 28px; display: flex; flex-direction: column; gap: 16px; }
.ticket-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.ticket-title { font-size: 20px; font-weight: 900; letter-spacing: -0.02em; margin-top: 12px; }
.ticket-code {
  font-size: 22px; font-weight: 900; letter-spacing: 0.06em; color: var(--red);
  background: var(--red-bg); padding: 8px 16px; border-radius: var(--radius-sm);
  border: 1px solid var(--red-border); display: inline-block; margin-top: 4px;
}
.ticket-meta {
  display: flex; gap: 24px; flex-wrap: wrap;
  border-top: 1px solid var(--border-light); padding-top: 16px;
  font-size: 13px; font-weight: 600;
}
.ticket-meta-label { color: var(--muted); }
.ticket-meta-value { font-weight: 800; color: var(--ink); margin-top: 2px; }
.ticket-qr {
  background: var(--border-light); border-left: 2px dashed var(--border);
  padding: 28px; display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 12px; text-align: center;
}
.ticket-qr img {
  width: 140px; height: 140px; border-radius: var(--radius-sm);
  background: #fff; padding: 8px; border: 1px solid var(--border);
  box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}
.ticket-qr span { font-size: 12px; font-weight: 700; color: var(--muted); }

/* Btn */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 12px 20px; border-radius: var(--radius-sm); border: none;
  font-size: 14px; font-weight: 800; cursor: pointer; transition: all 0.25s;
  font-family: inherit;
}
.btn-red { background: linear-gradient(135deg, var(--red), var(--red2)); color: #fff; box-shadow: 0 8px 24px rgba(196,18,48,0.15); }
.btn-gold { background: linear-gradient(135deg, var(--gold), var(--gold2)); color: #78350f; box-shadow: 0 8px 24px rgba(245,158,11,0.15); }

/* Empty */
.empty-state { text-align: center; padding: 64px 24px; border-radius: var(--radius); background: var(--surface); border: 2px dashed var(--border); }
.empty-state-title { font-size: 18px; font-weight: 800; margin-bottom: 8px; }
.empty-state-desc { font-size: 14px; color: var(--muted); font-weight: 600; max-width: 400px; margin: 0 auto 20px; line-height: 1.6; }

/* Responsive */
@media(max-width: 768px) {
  .topbar-inner { padding: 12px 16px; }
  .nav-tabs { padding: 0 16px; }
  .page { padding: 24px 16px 80px; }
  .summary-grid { grid-template-columns: 1fr; }
  .ticket { grid-template-columns: 1fr; }
  .ticket-qr { border-left: none; border-top: 2px dashed var(--border); }
}
@media print {
  .topbar, .nav-tabs, .no-print { display: none !important; }
  body { background: #fff; }
  .page { padding: 0; max-width: 100%; }
  .ticket { break-inside: avoid; box-shadow: none; }
  .btn { display: none; }
}
</style></head>
<body>

<header class="topbar">
  <div class="topbar-inner">
    <div class="topbar-brand">
      <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero">
      <span>Lumero Member</span>
    </div>
    <a class="topbar-logout" href="index.php?logout=1">Keluar</a>
  </div>
  <nav class="nav-tabs">
    <a class="nav-tab" href="index.php?page=profil">Dashboard</a>
    <a class="nav-tab" href="index.php?page=riwayat">Aktivitas</a>
    <a class="nav-tab" href="index.php?page=penukaran">Tukar Poin</a>
    <a class="nav-tab active" href="redemption-history.php">Riwayat Hadiah</a>
    <a class="nav-tab" href="online-order.php">Order Online</a>
  </nav>
</header>

<main class="page">

  <!-- Summary -->
  <section class="summary-grid reveal">
    <div class="summary-card">
      <div class="summary-label">Member</div>
      <div class="summary-value" style="font-size:22px;"><?=rh_e($member['name'] ?: 'Member Setia')?></div>
      <div class="summary-note"><?=rh_e(loyalty_mask_phone($member['phone']))?></div>
    </div>
    <div class="summary-card">
      <div class="summary-label">Saldo Poin Aktif</div>
      <div class="summary-value" style="color:var(--red);"><?=number_format((int)$member['total_points'],0,',','.')?></div>
      <div class="summary-note">Siap ditukarkan dengan hadiah</div>
    </div>
    <div class="summary-card">
      <div class="summary-label">Total Tiket Hadiah</div>
      <div class="summary-value"><?=number_format(count($redemptions),0,',','.')?></div>
      <div class="summary-note">Klaim penukaran tercatat</div>
    </div>
  </section>

  <!-- Tickets -->
  <section class="reveal reveal-delay-1">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px;">
      <div>
        <h2 style="font-size:24px; font-weight:900; letter-spacing:-0.03em;">Tiket Pengambilan Hadiah</h2>
        <p style="font-size:14px; color:var(--muted); font-weight:600; margin-top:4px;">Kasir akan memindai QR atau memasukkan kode tiket di POS saat penyerahan hadiah.</p>
      </div>
      <button class="btn btn-gold no-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="ticket-list">
      <?php foreach($redemptions as $r): 
        $code=(string)($r['redemption_code'] ?: ('RDM-'.$r['id'])); 
        $qr='https://quickchart.io/qr?size=180&margin=1&text='.rawurlencode($code); 
      ?>
      <article class="ticket">
        <div class="ticket-body">
          <div>
            <div class="ticket-head">
              <span class="badge <?=rh_status_class($r['status'])?>"><?=rh_e(rh_status_label($r['status']))?></span>
              <span style="font-size:12px; font-weight:600; color:var(--subtle);"><?=rh_e(date('d M Y, H:i',strtotime($r['created_at'])))?></span>
            </div>
            <h3 class="ticket-title"><?=rh_e($r['product_name'] ?? 'Hadiah Spesial')?></h3>
            <div class="ticket-code"><?=rh_e($code)?></div>
          </div>

          <div class="ticket-meta">
            <div>
              <div class="ticket-meta-label">Poin Dipakai</div>
              <div class="ticket-meta-value" style="color:var(--red);"><?=number_format((int)$r['points_used'],0,',','.')?> Poin</div>
            </div>
            <div>
              <div class="ticket-meta-label">No. Transaksi Kasir</div>
              <div class="ticket-meta-value"><?=rh_e($r['order_no'] ?: '-')?></div>
            </div>
            <?php if(!empty($r['completed_at'])): ?>
            <div>
              <div class="ticket-meta-label">Diserahkan Pada</div>
              <div class="ticket-meta-value" style="color:var(--green);"><?=rh_e(date('d/m/Y H:i',strtotime($r['completed_at'])))?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="ticket-qr">
          <img src="<?=rh_e($qr)?>" alt="QR kode penukaran">
          <span>Scan QR di Kasir</span>
        </div>
      </article>
      <?php endforeach; ?>

      <?php if(!$redemptions): ?>
      <div class="empty-state">
        <div style="font-size:40px; color:var(--subtle); margin-bottom:16px;">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a2 2 0 000-4V7a2 2 0 012-2z"/></svg>
        </div>
        <div class="empty-state-title">Belum Ada Tiket Hadiah</div>
        <div class="empty-state-desc">Anda belum menukarkan poin dengan hadiah. Buka katalog penukaran sekarang.</div>
        <a class="btn btn-red" href="index.php?page=penukaran">Lihat Katalog Penukaran</a>
      </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<script>
const reveals = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
reveals.forEach(el => observer.observe(el));
</script>
</body></html>

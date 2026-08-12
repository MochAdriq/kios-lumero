<!doctype html><html lang="id"><head>
<link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png">
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Member Loyalty — Lumero</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════
   LUMERO MEMBER — Opsi B: Single Page Scroll
   ═══════════════════════════════════════════════ */

/* Design Tokens */
:root {
  --red: #c41230; --red2: #7a001b; --gold: #ffc72c; --gold2: #f59e0b;
  --ink: #0f172a; --ink2: #1e293b; --muted: #64748b; --subtle: #94a3b8;
  --bg: #fafafa; --surface: #ffffff; --border: #e5e7eb; --border-light: #f3f4f6;
  --green: #16a34a; --green-bg: #f0fdf4; --green-border: #bbf7d0;
  --red-bg: #fef2f2; --red-border: #fecaca;
  --gold-bg: #fffbeb; --gold-border: #fde68a;
  --radius: 24px; --radius-sm: 16px; --radius-xs: 12px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
  --shadow-md: 0 8px 30px rgba(0,0,0,0.04);
  --shadow-lg: 0 20px 60px rgba(0,0,0,0.06);
  --shadow-red: 0 16px 40px rgba(196,18,48,0.15);
}

/* Reset */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: var(--bg); color: var(--ink); min-height: 100vh;
  -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
}
a { text-decoration: none; color: inherit; }
button { font-family: inherit; }
img { max-width: 100%; display: block; }

/* ── Sticky Top Bar ─────────────────────────── */
.topbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(250,250,250,0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0,0,0,0.04);
  transition: box-shadow 0.3s, padding 0.3s;
}
.topbar.scrolled { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
.topbar-inner {
  max-width: 1080px; margin: 0 auto; padding: 14px 24px;
  display: flex; justify-content: space-between; align-items: center;
}
.topbar-brand { display: flex; align-items: center; gap: 12px; }
.topbar-brand img { width: 36px; height: 36px; border-radius: 10px; }
.topbar-brand span { font-size: 16px; font-weight: 800; letter-spacing: -0.02em; }
.topbar-actions { display: flex; align-items: center; gap: 10px; }
.topbar-user {
  font-size: 13px; font-weight: 700; color: var(--muted);
  background: var(--surface); border: 1px solid var(--border); border-radius: 99px;
  padding: 8px 16px; display: none;
}
.topbar-logout {
  font-size: 13px; font-weight: 700; color: var(--red); background: var(--red-bg);
  border: 1px solid var(--red-border); border-radius: 99px; padding: 8px 16px;
  transition: all 0.2s;
}
.topbar-logout:hover { background: var(--red); color: #fff; border-color: var(--red); }

/* ── Navigation Tabs ────────────────────────── */
.nav-tabs {
  max-width: 1080px; margin: 0 auto; padding: 0 24px;
  display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}
.nav-tabs::-webkit-scrollbar { display: none; }
.nav-tab {
  white-space: nowrap; padding: 10px 18px; border-radius: 99px;
  font-size: 13px; font-weight: 700; color: var(--muted);
  background: transparent; border: 1px solid transparent;
  transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
}
.nav-tab:hover { background: var(--surface); color: var(--ink); border-color: var(--border); }
.nav-tab.active {
  background: var(--red); color: #fff; border-color: var(--red);
  box-shadow: 0 4px 16px rgba(196,18,48,0.2);
}

/* ── Page Container ─────────────────────────── */
.page { max-width: 1080px; margin: 0 auto; padding: 32px 24px 80px; }

/* ── Scroll Reveal Animation ────────────────── */
.reveal {
  opacity: 0; transform: translateY(32px);
  transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1);
}
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }

/* ── Hero VIP Card ──────────────────────────── */
.vip-hero {
  background: linear-gradient(135deg, var(--red2) 0%, var(--red) 40%, #d4213b 100%);
  border-radius: 32px; padding: 48px; position: relative; overflow: hidden;
  color: #fff; margin-bottom: 40px;
  box-shadow: var(--shadow-red);
}
.vip-hero::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.08) 50%, transparent 70%);
  background-size: 200% 200%; animation: shimmer 4s ease-in-out infinite;
}
@keyframes shimmer {
  0% { background-position: -200% -200%; }
  100% { background-position: 200% 200%; }
}
.vip-hero::after {
  content: ''; position: absolute; top: -50%; right: -20%; width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(255,199,44,0.12) 0%, transparent 70%);
  pointer-events: none;
}
.vip-content { position: relative; z-index: 2; }
.vip-label {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(90deg, var(--gold), var(--gold2)); color: #78350f;
  font-size: 11px; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase;
  padding: 6px 16px; border-radius: 99px; margin-bottom: 24px;
  box-shadow: 0 4px 16px rgba(245,158,11,0.3);
}
.vip-name { font-size: 18px; font-weight: 700; color: rgba(255,255,255,0.85); margin-bottom: 4px; }
.vip-phone { font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.5); margin-bottom: 28px; }
.vip-points-row { display: flex; align-items: baseline; gap: 12px; margin-bottom: 32px; }
.vip-points-num {
  font-size: 72px; font-weight: 900; letter-spacing: -0.04em; line-height: 1;
  background: linear-gradient(180deg, #fff 30%, rgba(255,255,255,0.6));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.vip-points-unit { font-size: 18px; font-weight: 800; color: rgba(255,255,255,0.6); }
.vip-stats {
  display: flex; gap: 40px; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 24px;
}
.vip-stat-item { }
.vip-stat-value { font-size: 18px; font-weight: 900; color: #fff; margin-bottom: 2px; }
.vip-stat-label { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.55); }
.vip-cta {
  position: absolute; top: 48px; right: 48px; z-index: 3;
  display: flex; flex-direction: column; gap: 10px;
}
.btn-vip {
  padding: 12px 24px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 800;
  border: none; cursor: pointer; transition: all 0.25s; display: inline-flex; align-items: center; gap: 8px;
}
.btn-vip-gold {
  background: linear-gradient(135deg, var(--gold), var(--gold2)); color: #78350f;
  box-shadow: 0 8px 24px rgba(245,158,11,0.3);
}
.btn-vip-gold:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(245,158,11,0.4); }
.btn-vip-ghost {
  background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2);
  backdrop-filter: blur(8px);
}
.btn-vip-ghost:hover { background: rgba(255,255,255,0.18); transform: translateY(-2px); }

/* ── Section Headings ───────────────────────── */
.section { margin-bottom: 40px; }
.section-header { margin-bottom: 24px; }
.section-title {
  font-size: 24px; font-weight: 900; letter-spacing: -0.03em; color: var(--ink); margin-bottom: 6px;
}
.section-subtitle { font-size: 14px; font-weight: 600; color: var(--muted); line-height: 1.5; }
.section-badge {
  display: inline-flex; font-size: 12px; font-weight: 800; padding: 5px 12px;
  border-radius: 99px; margin-left: 10px; vertical-align: middle;
}
.section-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }

/* ── Cards ──────────────────────────────────── */
.card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 28px;
  box-shadow: var(--shadow-sm); transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.card-static { transition: none; }
.card-static:hover { transform: none; box-shadow: var(--shadow-sm); }

/* ── Grid Layouts ───────────────────────────── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.grid-auto { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }

/* ── Buttons ────────────────────────────────── */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 14px 24px; border-radius: var(--radius-sm); border: none;
  font-size: 14px; font-weight: 800; cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
}
.btn-red {
  background: linear-gradient(135deg, var(--red), var(--red2)); color: #fff;
  box-shadow: var(--shadow-red);
}
.btn-red:hover { transform: translateY(-2px); box-shadow: 0 20px 48px rgba(196,18,48,0.25); }
.btn-gold {
  background: linear-gradient(135deg, var(--gold), var(--gold2)); color: #78350f;
  box-shadow: 0 8px 24px rgba(245,158,11,0.2);
}
.btn-gold:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(245,158,11,0.3); }
.btn-outline {
  background: var(--surface); color: var(--ink); border: 2px solid var(--border);
}
.btn-outline:hover { border-color: var(--ink); }
.btn-full { width: 100%; }
.btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

/* ── Forms ──────────────────────────────────── */
.form-grid { display: grid; gap: 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-label {
  font-size: 11px; font-weight: 800; color: var(--muted);
  text-transform: uppercase; letter-spacing: 0.08em;
}
.form-input {
  width: 100%; border: 2px solid var(--border-light); border-radius: var(--radius-sm);
  padding: 14px 16px; font-size: 15px; font-weight: 700; color: var(--ink);
  background: var(--border-light); transition: all 0.25s; font-family: inherit;
}
.form-input:focus {
  outline: none; border-color: var(--red); background: #fff;
  box-shadow: 0 0 0 4px rgba(196,18,48,0.08);
}
textarea.form-input { min-height: 88px; resize: vertical; }

/* ── Alerts ─────────────────────────────────── */
.alert {
  border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 24px;
  font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 10px;
  animation: slideDown 0.4s cubic-bezier(0.16,1,0.3,1);
}
@keyframes slideDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
.alert-ok { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
.alert-err { background: var(--red-bg); color: #991b1b; border: 1px solid var(--red-border); }

/* ── Badges ─────────────────────────────────── */
.badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 99px;
  background: var(--border-light); color: var(--ink);
}
.badge-green { background: var(--green-bg); color: var(--green); }
.badge-red { background: var(--red-bg); color: var(--red); }
.badge-gold { background: var(--gold-bg); color: #92400e; }
.badge-blue { background: #eff6ff; color: #1d4ed8; }

/* ── Activity List ──────────────────────────── */
.activity-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 20px 0; border-bottom: 1px solid var(--border-light);
  transition: background 0.2s;
}
.activity-item:last-child { border-bottom: none; }
.activity-left { display: flex; align-items: center; gap: 16px; }
.activity-dot {
  width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
  box-shadow: 0 0 0 4px rgba(0,0,0,0.04);
}
.activity-dot-in { background: var(--green); box-shadow: 0 0 0 4px rgba(22,163,74,0.1); }
.activity-dot-out { background: var(--red); box-shadow: 0 0 0 4px rgba(196,18,48,0.1); }
.activity-dot-order { background: var(--gold2); box-shadow: 0 0 0 4px rgba(245,158,11,0.1); }
.activity-title { font-size: 14px; font-weight: 800; color: var(--ink); }
.activity-desc { font-size: 13px; font-weight: 600; color: var(--muted); margin-top: 2px; }
.activity-date { font-size: 12px; font-weight: 600; color: var(--subtle); margin-top: 3px; }
.activity-right { text-align: right; }
.activity-amount { font-size: 16px; font-weight: 900; }
.activity-amount.positive { color: var(--green); }
.activity-amount.negative { color: var(--red); }
.activity-balance { font-size: 12px; color: var(--subtle); font-weight: 600; margin-top: 2px; }

/* ── Reward Cards ───────────────────────────── */
.reward-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
  overflow: hidden; display: flex; flex-direction: column;
  box-shadow: var(--shadow-sm); transition: all 0.35s cubic-bezier(0.16,1,0.3,1);
}
.reward-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: #cbd5e1; }
.reward-img {
  height: 200px; background: var(--border-light); display: flex; align-items: center;
  justify-content: center; padding: 24px; position: relative; overflow: hidden;
}
.reward-img img {
  max-height: 100%; object-fit: contain; transition: transform 0.5s cubic-bezier(0.16,1,0.3,1);
  filter: drop-shadow(0 8px 16px rgba(0,0,0,0.08));
}
.reward-card:hover .reward-img img { transform: scale(1.08); }
.reward-img-badge {
  position: absolute; bottom: 12px; right: 12px;
  background: var(--red); color: #fff; font-size: 13px; font-weight: 900;
  padding: 6px 14px; border-radius: 99px; box-shadow: 0 4px 12px rgba(196,18,48,0.3);
}
.reward-img-cat {
  position: absolute; top: 12px; left: 12px;
  background: #fff; color: var(--ink); font-size: 11px; font-weight: 800;
  padding: 5px 10px; border-radius: 99px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.reward-body { padding: 24px; flex: 1; display: flex; flex-direction: column; gap: 12px; }
.reward-name { font-size: 18px; font-weight: 900; letter-spacing: -0.02em; }
.reward-desc { font-size: 13px; color: var(--muted); font-weight: 600; line-height: 1.5; }
.reward-terms {
  font-size: 12px; color: var(--muted); font-weight: 700; padding: 10px 14px;
  background: var(--border-light); border-radius: var(--radius-xs); line-height: 1.5;
}
.reward-status {
  font-size: 13px; font-weight: 800; padding: 10px 14px; border-radius: var(--radius-xs); text-align: center;
}
.reward-status-ok { background: var(--green-bg); color: var(--green); }
.reward-status-warn { background: var(--gold-bg); color: #92400e; }
.reward-status-err { background: var(--red-bg); color: #991b1b; }

/* ── Empty States ───────────────────────────── */
.empty-state {
  text-align: center; padding: 64px 24px; border-radius: var(--radius);
  background: var(--surface); border: 2px dashed var(--border);
}
.empty-state-icon { font-size: 40px; margin-bottom: 16px; color: var(--subtle); }
.empty-state-title { font-size: 18px; font-weight: 800; margin-bottom: 8px; }
.empty-state-desc { font-size: 14px; color: var(--muted); font-weight: 600; max-width: 400px; margin: 0 auto; line-height: 1.6; }

/* ── Tables ─────────────────────────────────── */
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; padding: 12px 0; text-align: left; border-bottom: 2px solid var(--border); }
.data-table td { padding: 16px 0; border-bottom: 1px solid var(--border-light); font-weight: 600; }

/* ── Responsive ─────────────────────────────── */
@media(max-width: 768px) {
  .topbar-inner { padding: 12px 16px; }
  .topbar-user { display: none; }
  .nav-tabs { padding: 0 16px; }
  .page { padding: 24px 16px 80px; }
  .vip-hero { padding: 32px 24px; border-radius: 24px; }
  .vip-points-num { font-size: 48px; }
  .vip-cta { position: static; flex-direction: row; margin-top: 24px; }
  .vip-stats { flex-wrap: wrap; gap: 24px; }
  .grid-2, .grid-3, .grid-auto { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .section-row { flex-direction: column; }
}

/* ── Print ──────────────────────────────────── */
@media print {
  .topbar, .nav-tabs { display: none !important; }
  body { background: #fff; }
  .page { padding: 0; max-width: 100%; }
}
</style>
    <link rel="manifest" href="../manifest-user.json">
    <link rel="apple-touch-icon" href="../public/assets/images/icon-512x512.png">
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('../sw.js').catch(err => console.log('SW registration failed: ', err));
        });
      }
    </script>
</head>
<body>

<?php if(!$member): ?>
    <?php require __DIR__ . "/login.php"; ?>
<?php else: ?>

<!-- Sticky Top Bar -->
<header class="topbar" id="topbar">
  <div class="topbar-inner">
    <div class="topbar-brand">
      <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero">
      <span>Lumero Member</span>
    </div>
    <div class="topbar-actions">
      <span class="topbar-user"><?=mem_e(explode(' ', $member['name'] ?: 'Member')[0])?></span>
      <a class="topbar-logout" href="dashboard.php?logout=1">Keluar</a>
    </div>
  </div>
  <nav class="nav-tabs" id="navTabs">
    <a class="nav-tab <?=$page==='profil'?'active':''?>" href="dashboard.php?page=profil">Dashboard</a>
    <a class="nav-tab <?=$page==='riwayat'?'active':''?>" href="dashboard.php?page=riwayat">Aktivitas</a>
    <a class="nav-tab <?=$page==='penukaran'?'active':''?>" href="dashboard.php?page=penukaran">Tukar Poin</a>
    <a class="nav-tab <?=$page==='raffle'?'active':''?>" href="raffle.php">Event Undian</a>
    <a class="nav-tab" href="redemption-history.php">Riwayat Hadiah</a>
    <a class="nav-tab" href="online-order.php">Order Online</a>
  </nav>
</header>

<main class="page">
  <?php if($msg): ?><div class="alert alert-ok"><?=mem_e($msg)?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-err"><?=mem_e($err)?></div><?php endif; ?>

  <!-- Hero VIP Card -->
  <section class="vip-hero reveal">
    <div class="vip-content">
      <div class="vip-label">Lumero VIP Member</div>
      <div class="vip-name"><?=mem_e($member['name'] ?: 'Member Setia Lumero')?></div>
      <div class="vip-phone"><?=mem_e(loyalty_mask_phone($member['phone']))?></div>
      <div class="vip-points-row">
        <span class="vip-points-num"><?=number_format((int)$member['total_points'],0,',','.')?></span>
        <span class="vip-points-unit">poin</span>
      </div>
      <div class="vip-stats">
        <div class="vip-stat-item">
          <div class="vip-stat-value"><?=number_format((int)$member['total_transactions'],0,',','.')?></div>
          <div class="vip-stat-label">Transaksi</div>
        </div>
        <div class="vip-stat-item">
          <div class="vip-stat-value"><?=mem_money((int)$member['total_spent'])?></div>
          <div class="vip-stat-label">Total Belanja</div>
        </div>
        <div class="vip-stat-item">
          <div class="vip-stat-value"><?=$profilePercent?>%</div>
          <div class="vip-stat-label">Kelengkapan Profil</div>
        </div>
      </div>
    </div>
    <div class="vip-cta">
      <a href="dashboard.php?page=penukaran" class="btn-vip btn-vip-gold">Tukar Poin</a>
      <a href="raffle.php" class="btn-vip" style="background:var(--red); color:#fff; border-color:var(--red);">Event Undian</a>
      <a href="dashboard.php?page=riwayat" class="btn-vip btn-vip-ghost">Lihat Aktivitas</a>
    </div>
  </section>

  <!-- Sub-page Content -->
  <?php if($page==="profil"): ?>
      <?php require __DIR__ . "/profil.php"; ?>
  <?php elseif($page==="riwayat"): ?>
      <?php require __DIR__ . "/riwayat.php"; ?>
  <?php elseif($page==="penukaran"): ?>
      <?php require __DIR__ . "/penukaran.php"; ?>
  <?php elseif($page==="raffle"): ?>
      <?php require __DIR__ . "/raffle-content.php"; ?>
  <?php endif; ?>
</main>

<script>
// Sticky top bar shadow on scroll
const tb = document.getElementById('topbar');
window.addEventListener('scroll', () => {
  tb.classList.toggle('scrolled', window.scrollY > 10);
}, { passive: true });

// Scroll reveal
const reveals = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
reveals.forEach(el => observer.observe(el));
</script>

<?php endif; ?>
</body></html>
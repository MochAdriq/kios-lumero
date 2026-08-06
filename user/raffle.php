<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/loyalty.php';

$pdo      = Database::connection();
$memberId = (int)($_SESSION['member_id'] ?? 0);
if ($memberId <= 0) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM loyalty_members WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$member) { header('Location: login.php'); exit; }

$flashOk  = '';
$flashErr = '';
$activeBatch = null;
$prizes    = [];
$myTickets = [];

try {
    $r = $pdo->query("SELECT * FROM raffle_batches WHERE status = 'active' ORDER BY end_date ASC LIMIT 1");
    $activeBatch = $r ? $r->fetch(PDO::FETCH_ASSOC) : null;
} catch (Throwable $e) {
    $activeBatch = null;
}

if ($activeBatch) {
    $batchId = (int)$activeBatch['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
        $qty  = max(0, (int)$_POST['qty']);
        $cost = $qty * 10;
        $bal  = (int)($member['points_balance'] ?? 0);
        if ($qty > 0 && $bal >= $cost) {
            try {
                $pdo->beginTransaction();
                loyalty_deduct_points($pdo, $memberId, $cost, 'raffle_ticket', "Tukar {$qty} Tiket ({$activeBatch['name']})");
                $ins = $pdo->prepare("INSERT INTO raffle_tickets (ticket_code, batch_id, member_id) VALUES (?, ?, ?)");
                for ($i = 0; $i < $qty; $i++) {
                    $code = 'UND-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));
                    $ins->execute([$code, $batchId, $memberId]);
                }
                $pdo->commit();
                $flashOk = "Berhasil tukar {$cost} poin jadi {$qty} tiket! Semoga beruntung 🎉";
                $stmt->execute([$memberId]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $flashErr = "Terjadi kesalahan. Silakan coba lagi.";
            }
        } elseif ($qty <= 0) {
            $flashErr = "Jumlah tiket tidak valid.";
        } else {
            $flashErr = "Poin tidak cukup. Butuh {$cost} poin untuk {$qty} tiket.";
        }
    }

    try {
        $sp = $pdo->prepare("SELECT * FROM raffle_prizes WHERE batch_id = ? ORDER BY id ASC");
        $sp->execute([$batchId]);
        $prizes = $sp->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $prizes = []; }

    try {
        $st = $pdo->prepare("SELECT ticket_code, created_at FROM raffle_tickets WHERE batch_id = ? AND member_id = ? ORDER BY created_at DESC");
        $st->execute([$batchId, $memberId]);
        $myTickets = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $myTickets = []; }
}

$bal = (int)($member['points_balance'] ?? 0);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Event Undian – Lumero</title>
<link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:#f8f8f8;color:#0f172a;min-height:100vh;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}button{font-family:inherit}
.topbar{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.93);backdrop-filter:blur(16px);border-bottom:1px solid #e5e7eb;padding:12px 20px;display:flex;align-items:center;justify-content:space-between}
.topbar-brand{font-weight:800;font-size:1rem;color:#c41230;display:flex;align-items:center;gap:8px}
.topbar-brand img{width:28px;height:28px;border-radius:8px}
.topbar-back{font-size:.82rem;font-weight:700;color:#64748b;padding:6px 14px;border-radius:99px;border:1.5px solid #e5e7eb;background:#fff}
.page{max-width:480px;margin:0 auto;padding:20px 16px 80px}
.hero{background:linear-gradient(135deg,#FF6B00 0%,#c41230 100%);border-radius:24px;padding:28px 24px 24px;color:#fff;text-align:center;margin-bottom:20px}
.hero h1{font-size:1.6rem;font-weight:900;margin-bottom:4px}
.hero p{font-size:.88rem;opacity:.85}
.hero .pts{display:inline-block;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.35);border-radius:99px;padding:6px 18px;font-size:.85rem;font-weight:700;margin-top:12px}
.hero .pts b{font-size:1.15rem}
.flash{padding:14px 18px;border-radius:14px;font-weight:600;font-size:.9rem;margin-bottom:16px}
.flash-ok{background:#f0fdf4;border:1.5px solid #bbf7d0;color:#15803d}
.flash-err{background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626}
.card{background:#fff;border-radius:20px;border:1.5px solid #f3f4f6;padding:20px;margin-bottom:16px}
.card-ttl{font-weight:800;font-size:.95rem;color:#0f172a;margin-bottom:14px}
.prize-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.prize-item{border-radius:14px;overflow:hidden;border:1.5px solid #f3f4f6;text-align:center}
.prize-item img{width:100%;height:100px;object-fit:cover;display:block}
.prize-ph{height:100px;background:#f9fafb;display:flex;align-items:center;justify-content:center;font-size:2rem}
.prize-name{padding:8px 6px;font-weight:700;font-size:.8rem}
.xchg-wrap{display:flex;flex-direction:column;gap:10px}
.btn-x{display:block;width:100%;padding:14px;border-radius:99px;font-size:.95rem;font-weight:700;border:none;cursor:pointer;transition:opacity .15s,transform .1s}
.btn-x:active{transform:scale(.97)}
.btn-x:disabled{opacity:.35;cursor:not-allowed}
.b1{background:#f3f4f6;color:#374151}
.b5{background:#c41230;color:#fff}
.b10{background:linear-gradient(135deg,#FF6B00,#c41230);color:#fff}
.ticket-list{border-radius:16px;overflow:hidden;border:1.5px solid #f3f4f6}
.ticket-row{display:flex;justify-content:space-between;align-items:center;padding:13px 16px;border-bottom:1px solid #f3f4f6}
.ticket-row:last-child{border-bottom:none}
.ticket-code{font-family:'Courier New',monospace;font-size:1rem;font-weight:800;letter-spacing:.05em;color:#0f172a}
.ticket-time{font-size:.75rem;color:#94a3b8}
.empty-box{text-align:center;padding:28px 16px;color:#94a3b8;font-size:.88rem;background:#f9fafb;border-radius:16px;border:1.5px solid #f3f4f6}
.ev-badge{display:inline-block;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#FF6B00;background:#fff7ed;border:1.5px solid #FFE5C0;border-radius:99px;padding:3px 10px;margin-bottom:6px}
.ev-name{font-weight:800;font-size:1rem;color:#0f172a}
.ev-end{font-size:.78rem;color:#64748b;margin-top:2px}
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero">
    Lumero
  </div>
  <a href="dashboard.php" class="topbar-back">← Dashboard</a>
</header>

<div class="page">

  <div class="hero">
    <h1>🎫 Event Undian</h1>
    <p>Tukar poin, raih hadiah impian!</p>
    <div class="pts">Saldo: <b><?= number_format($bal) ?></b> Poin</div>
  </div>

  <?php if ($flashOk):  ?><div class="flash flash-ok"><?= htmlspecialchars($flashOk)  ?></div><?php endif ?>
  <?php if ($flashErr): ?><div class="flash flash-err"><?= htmlspecialchars($flashErr) ?></div><?php endif ?>

  <?php if (!$activeBatch): ?>

    <div class="empty-box" style="padding:48px 24px">
      <div style="font-size:3rem;margin-bottom:12px">🎯</div>
      <div style="font-weight:700;font-size:1rem;color:#0f172a;margin-bottom:8px">Belum Ada Event Aktif</div>
      <div>Terus kumpulkan poin dan nantikan event undian berikutnya!</div>
      <a href="dashboard.php" style="display:inline-block;margin-top:20px;background:#c41230;color:#fff;padding:12px 28px;border-radius:99px;font-weight:700;font-size:.9rem">Kembali ke Dashboard</a>
    </div>

  <?php else: ?>

    <div class="card">
      <div class="ev-badge">Sedang Berlangsung</div>
      <div class="ev-name"><?= htmlspecialchars($activeBatch['name']) ?></div>
      <div class="ev-end">Tutup: <?= date('d F Y', strtotime($activeBatch['end_date'])) ?></div>
    </div>

    <?php if (!empty($prizes)): ?>
    <div class="card">
      <div class="card-ttl">🎁 Hadiah</div>
      <div class="prize-grid">
        <?php foreach ($prizes as $p): ?>
        <div class="prize-item">
          <?php if (!empty($p['image_url'])): ?>
            <img src="../public/assets/<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
          <?php else: ?>
            <div class="prize-ph">🎁</div>
          <?php endif ?>
          <div class="prize-name"><?= htmlspecialchars($p['name']) ?></div>
        </div>
        <?php endforeach ?>
      </div>
    </div>
    <?php endif ?>

    <div class="card">
      <div class="card-ttl">🔄 Tukar Poin ke Tiket</div>
      <p style="font-size:.85rem;color:#64748b;margin-bottom:14px;font-weight:600">10 Poin = 1 Tiket · Poin hangus setelah ditukar</p>
      <form method="POST" onsubmit="return confirm('Poin yang ditukar tidak bisa dikembalikan. Lanjutkan?')">
        <div class="xchg-wrap">
          <button type="submit" name="qty" value="1"  class="btn-x b1"  <?= $bal < 10  ? 'disabled' : '' ?>>1 Tiket · 10 Pts</button>
          <button type="submit" name="qty" value="5"  class="btn-x b5"  <?= $bal < 50  ? 'disabled' : '' ?>>5 Tiket · 50 Pts</button>
          <button type="submit" name="qty" value="10" class="btn-x b10" <?= $bal < 100 ? 'disabled' : '' ?>>10 Tiket · 100 Pts</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-ttl">🎫 Tiket Saya (<?= count($myTickets) ?>)</div>
      <?php if (empty($myTickets)): ?>
        <div class="empty-box">Belum ada tiket. Tukar poin di atas!</div>
      <?php else: ?>
        <div class="ticket-list">
          <?php foreach ($myTickets as $t): ?>
          <div class="ticket-row">
            <span class="ticket-code"><?= htmlspecialchars($t['ticket_code']) ?></span>
            <span class="ticket-time"><?= date('d M, H:i', strtotime($t['created_at'])) ?></span>
          </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>

  <?php endif ?>

</div>
</body>
</html>

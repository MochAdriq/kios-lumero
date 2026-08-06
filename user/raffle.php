<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/loyalty.php';

$pdo = Database::connection();
$memberId = (int)($_SESSION['member_id'] ?? 0);

// --- Redirect if not logged in ---
if ($memberId <= 0) {
    header('Location: login.php?source=raffle');
    exit;
}

// --- Fetch member ---
$stmt = $pdo->prepare("SELECT * FROM loyalty_members WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: login.php?source=raffle');
    exit;
}

// --- Helper functions (same as dashboard.php) ---
if (!function_exists('mem_e')) {
    function mem_e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('mem_money')) {
    function mem_money($n){ return function_exists('rupiah') ? rupiah((int)$n) : 'Rp'.number_format((int)$n,0,',','.'); }
}
if (!function_exists('mem_profile_percent')) {
    function mem_profile_percent(array $m): int {
        $fields = ['name','email','gender','birth_date','address'];
        $filled = 0;
        foreach ($fields as $f) { if (trim((string)($m[$f] ?? '')) !== '') $filled++; }
        return (int)round($filled / max(1, count($fields)) * 100);
    }
}
if (!function_exists('mem_csrf')) {
    function mem_csrf(){ if(empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(16)); return $_SESSION['member_csrf']; }
}

// --- Variables expected by layout.php ---
$page          = 'raffle'; // no active tab in nav
$profilePercent = mem_profile_percent($member);
$msg           = '';
$err           = '';

// --- Check raffle tables exist (safe fallback) ---
$activeBatch = null;
$prizes      = [];
$myTickets   = [];

try {
    $stmtBatch = $pdo->query("SELECT * FROM raffle_batches WHERE status = 'active' ORDER BY end_date ASC LIMIT 1");
    $activeBatch = $stmtBatch->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Table might not exist yet — silently ignore
    $activeBatch = null;
}

if ($activeBatch) {
    $batchId = (int)$activeBatch['id'];

    // --- Handle POST: exchange points for tickets ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
        $qty  = max(0, (int)$_POST['qty']);
        $cost = $qty * 10;

        if ($qty > 0 && $member['points_balance'] >= $cost) {
            try {
                $pdo->beginTransaction();
                loyalty_deduct_points($pdo, $memberId, $cost, 'raffle_ticket', "Tukar {$qty} Tiket Undian ({$activeBatch['name']})");
                $stmtIns = $pdo->prepare("INSERT INTO raffle_tickets (ticket_code, batch_id, member_id) VALUES (?, ?, ?)");
                for ($i = 0; $i < $qty; $i++) {
                    $code = 'UND-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));
                    $stmtIns->execute([$code, $batchId, $memberId]);
                }
                $pdo->commit();
                $msg = "Berhasil menukar {$cost} poin menjadi {$qty} Tiket Undian! Semoga beruntung! 🎉";
                // Refresh member data
                $stmt->execute([$memberId]);
                $member = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $err = "Terjadi kesalahan saat memproses tiket. Silakan coba lagi.";
            }
        } elseif ($qty <= 0) {
            $err = "Jumlah tiket tidak valid.";
        } else {
            $err = "Poin Anda tidak cukup. Butuh {$cost} poin untuk {$qty} tiket.";
        }
    }

    // --- Fetch prizes ---
    try {
        $stmtPrizes = $pdo->prepare("SELECT * FROM raffle_prizes WHERE batch_id = ? ORDER BY id ASC");
        $stmtPrizes->execute([$batchId]);
        $prizes = $stmtPrizes->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $prizes = []; }

    // --- Fetch my tickets ---
    try {
        $stmtTickets = $pdo->prepare("SELECT ticket_code, created_at FROM raffle_tickets WHERE batch_id = ? AND member_id = ? ORDER BY created_at DESC");
        $stmtTickets->execute([$batchId, $memberId]);
        $myTickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $myTickets = []; }
}

// --- Inline raffle content rendered into $content for layout ---
ob_start();
?>

<?php if ($msg): ?>
    <div class="alert" style="background:#f0fdf4;border:1.5px solid #bbf7d0;color:#15803d;border-radius:16px;padding:16px 20px;margin-bottom:20px;font-weight:600;"><?= mem_e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert" style="background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626;border-radius:16px;padding:16px 20px;margin-bottom:20px;font-weight:600;"><?= mem_e($err) ?></div>
<?php endif; ?>

<style>
.raffle-hero { background: linear-gradient(135deg, #FF6B00 0%, #c41230 100%); border-radius: 24px; padding: 28px 24px; color: #fff; text-align: center; margin-bottom: 24px; }
.raffle-hero h2 { font-size: 1.6rem; font-weight: 900; margin-bottom: 4px; }
.raffle-hero p { opacity: .8; margin: 0; font-size: .9rem; }
.prize-card { border-radius: 16px; overflow: hidden; border: 1.5px solid #f3f4f6; background: #fff; text-align: center; }
.prize-card img { width: 100%; height: 120px; object-fit: cover; }
.prize-card .prize-img-placeholder { height: 120px; display: flex; align-items: center; justify-content: center; background: #f9fafb; color: #94a3b8; font-size: 2rem; }
.prize-card .prize-name { padding: 10px; font-weight: 700; font-size: .85rem; }
.exchange-card { background: linear-gradient(135deg, #fff9f5, #fff); border: 2px solid #FFE5D0; border-radius: 24px; padding: 28px 24px; text-align: center; margin-bottom: 24px; }
.exchange-card .points-num { font-size: 3rem; font-weight: 900; color: #c41230; line-height: 1; }
.ticket-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #f3f4f6; }
.ticket-item:last-child { border-bottom: none; }
.ticket-code { font-family: monospace; font-size: 1.1rem; font-weight: 800; color: #1e293b; letter-spacing: .06em; }
.ticket-time { font-size: .78rem; color: #94a3b8; }
.btn-exchange { display: block; width: 100%; padding: 14px; border-radius: 99px; font-weight: 700; font-size: 1rem; border: none; cursor: pointer; transition: all .2s; margin-bottom: 10px; }
.btn-exchange:last-child { margin-bottom: 0; }
.btn-exchange-1 { background: #f3f4f6; color: #374151; }
.btn-exchange-1:hover { background: #e5e7eb; }
.btn-exchange-5 { background: #c41230; color: #fff; }
.btn-exchange-5:hover { background: #a50f27; }
.btn-exchange-10 { background: linear-gradient(135deg, #FF6B00, #c41230); color: #fff; }
.btn-exchange-10:hover { opacity: .9; }
.btn-exchange:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
</style>

<div class="raffle-hero">
    <h2>🎫 Undian Spesial</h2>
    <p>Tukar poin Anda dan raih hadiah impian!</p>
</div>

<?php if (!$activeBatch): ?>

    <div style="background:#fff;border-radius:20px;padding:40px 24px;text-align:center;border:1.5px solid #f3f4f6;">
        <div style="font-size:3rem;margin-bottom:16px;">🎯</div>
        <h4 style="font-weight:700;color:#0f172a;margin-bottom:8px;">Belum Ada Event Aktif</h4>
        <p style="color:#64748b;margin-bottom:24px;font-size:.9rem;">Saat ini belum ada periode undian yang berlangsung.<br>Terus kumpulkan poin dan nantikan event berikutnya!</p>
        <a href="dashboard.php" style="background:#c41230;color:#fff;padding:12px 28px;border-radius:99px;text-decoration:none;font-weight:700;font-size:.9rem;">Kembali ke Dashboard</a>
    </div>

<?php else: ?>

    <!-- Event info -->
    <div style="background:#fff;border-radius:20px;padding:18px 20px;border:1.5px solid #e5e7eb;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <span style="font-size:.75rem;font-weight:700;color:#FF6B00;text-transform:uppercase;letter-spacing:.05em;">SEDANG BERLANGSUNG</span>
            <div style="font-weight:800;color:#0f172a;font-size:1.05rem;"><?= mem_e($activeBatch['name']) ?></div>
        </div>
        <div style="text-align:right;font-size:.78rem;color:#64748b;">
            Tutup:<br><strong><?= date('d M Y', strtotime($activeBatch['end_date'])) ?></strong>
        </div>
    </div>

    <?php if (!empty($prizes)): ?>
    <!-- Prizes -->
    <h5 style="font-weight:800;margin-bottom:12px;color:#0f172a;">🎁 Hadiah</h5>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:24px;">
        <?php foreach ($prizes as $p): ?>
        <div class="prize-card">
            <?php if (!empty($p['image_url'])): ?>
                <img src="../public/assets/<?= mem_e($p['image_url']) ?>" alt="<?= mem_e($p['name']) ?>">
            <?php else: ?>
                <div class="prize-img-placeholder">🎁</div>
            <?php endif; ?>
            <div class="prize-name"><?= mem_e($p['name']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Exchange form -->
    <div class="exchange-card">
        <p style="color:#64748b;font-size:.85rem;margin-bottom:4px;">Saldo Poin Anda</p>
        <div class="points-num"><?= number_format((int)$member['points_balance']) ?></div>
        <p style="color:#64748b;font-size:.8rem;margin-top:4px;margin-bottom:20px;">poin tersisa</p>
        <p style="font-weight:700;color:#0f172a;font-size:.9rem;margin-bottom:16px;">10 Poin = 1 Tiket Undian</p>
        <form method="POST" onsubmit="return confirm('Poin yang sudah ditukar tidak dapat dikembalikan. Lanjutkan?')">
            <?php $bal = (int)$member['points_balance']; ?>
            <button type="submit" name="qty" value="1"  class="btn-exchange btn-exchange-1"  <?= $bal < 10  ? 'disabled' : '' ?>>Tukar 1 Tiket &nbsp;(10 Pts)</button>
            <button type="submit" name="qty" value="5"  class="btn-exchange btn-exchange-5"  <?= $bal < 50  ? 'disabled' : '' ?>>Tukar 5 Tiket &nbsp;(50 Pts)</button>
            <button type="submit" name="qty" value="10" class="btn-exchange btn-exchange-10" <?= $bal < 100 ? 'disabled' : '' ?>>Tukar 10 Tiket (100 Pts)</button>
        </form>
    </div>

    <!-- My tickets -->
    <h5 style="font-weight:800;margin-bottom:12px;color:#0f172a;">🎫 Tiket Saya (<?= count($myTickets) ?>)</h5>
    <?php if (empty($myTickets)): ?>
        <div style="background:#f9fafb;border-radius:16px;padding:28px;text-align:center;color:#64748b;font-size:.9rem;border:1.5px solid #f3f4f6;">
            Anda belum punya tiket di periode ini.<br>Tukar poin di atas untuk mendapatkan tiket!
        </div>
    <?php else: ?>
        <div style="background:#fff;border-radius:20px;border:1.5px solid #f3f4f6;overflow:hidden;margin-bottom:20px;">
            <?php foreach ($myTickets as $t): ?>
            <div class="ticket-item">
                <span class="ticket-code"><?= mem_e($t['ticket_code']) ?></span>
                <span class="ticket-time"><?= date('d M, H:i', strtotime($t['created_at'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>


<?php
$content = ob_get_clean();
require __DIR__ . '/views/layout.php';



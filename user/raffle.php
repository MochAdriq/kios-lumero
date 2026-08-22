<?php
/* ── user/raffle.php ─────────────────────────────────────────────────────────
   Entry point halaman Event Undian.
   Pola identik dengan dashboard.php: proses logika → require views/layout.php
   ──────────────────────────────────────────────────────────────────────────── */
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/loyalty.php';

$pdo = Database::connection();
loyalty_ensure_tables($pdo);

$memberId = (int)($_SESSION['member_id'] ?? 0);
if ($memberId <= 0) { 
    $_SESSION['redirect_after_login'] = 'raffle.php';
    header('Location: login.php'); 
    exit; 
}

// Ambil data member (fungsi helper sama dengan dashboard)
$member = loyalty_member_by_id($pdo, $memberId);
if (!$member) { unset($_SESSION['member_id']); header('Location: login.php'); exit; }

// Helper functions yang dibutuhkan layout.php
if (!function_exists('mem_e')) {
    function mem_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('mem_money')) {
    function mem_money($n) { return function_exists('rupiah') ? rupiah((int)$n) : 'Rp' . number_format((int)$n, 0, ',', '.'); }
}
if (!function_exists('mem_profile_percent')) {
    function mem_profile_percent(array $m): int {
        $fields = ['name', 'email', 'gender', 'birth_date', 'address'];
        $filled = 0;
        foreach ($fields as $f) { if (trim((string)($m[$f] ?? '')) !== '') $filled++; }
        return (int)round($filled / max(1, count($fields)) * 100);
    }
}
if (!function_exists('mem_csrf')) {
    function mem_csrf() {
        if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(16));
        return $_SESSION['member_csrf'];
    }
}

$msg = $err = '';
$flashOk = $flashErr = '';
$activeBatch = null;
$prizes = [];
$myTickets = [];

// ── Cari batch undian yang aktif ──────────────────────────────────────────
try {
    $r = $pdo->query("SELECT * FROM raffle_batches WHERE status = 'active' ORDER BY end_date ASC LIMIT 1");
    $activeBatch = $r ? $r->fetch(PDO::FETCH_ASSOC) : null;
} catch (Throwable $e) {
    $activeBatch = null;
}

if ($activeBatch) {
    $batchId = (int)$activeBatch['id'];

    // ── Proses POST: tukar poin ke tiket ─────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
        $qty  = max(0, (int)$_POST['qty']);
        $cost = $qty * 10;
        $bal  = (int)($member['total_points'] ?? $member['points_balance'] ?? 0);
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
                // Refresh data member setelah transaksi
                $member = loyalty_member_by_id($pdo, $memberId);
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

    // ── Load data hadiah & tiket member ──────────────────────────────────
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

// Saldo poin (fallback ke kolom yang tersedia)
$bal = (int)($member['total_points'] ?? $member['points_balance'] ?? 0);

// Variabel wajib untuk layout.php
$page           = 'raffle';
$profilePercent = mem_profile_percent($member);
$csrf           = mem_csrf();
$msg            = $flashOk  ?: $msg;
$err            = $flashErr ?: $err;

require __DIR__ . '/views/layout.php';

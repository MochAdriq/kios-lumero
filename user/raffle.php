<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/loyalty.php';

$pdo = Database::connection();
$memberId = (int)($_SESSION['member_id'] ?? 0);

if ($memberId <= 0) {
    redirect('/user/login.php?source=raffle');
    exit;
}

// Fetch member data
$stmt = $pdo->prepare("SELECT * FROM loyalty_members WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    redirect('/user/login.php?source=raffle');
    exit;
}

// Fetch ACTIVE batch
$stmtBatch = $pdo->query("SELECT * FROM raffle_batches WHERE status = 'active' ORDER BY end_date ASC LIMIT 1");
$activeBatch = $stmtBatch->fetch(PDO::FETCH_ASSOC);

$prizes = [];
$myTickets = [];
$message = '';
$error = '';

if ($activeBatch) {
    $batchId = (int)$activeBatch['id'];
    
    // Fetch prizes
    $stmtPrizes = $pdo->prepare("SELECT * FROM raffle_prizes WHERE batch_id = ? ORDER BY id ASC");
    $stmtPrizes->execute([$batchId]);
    $prizes = $stmtPrizes->fetchAll(PDO::FETCH_ASSOC);

    // Process Exchange (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
        $qty = (int)$_POST['qty'];
        if ($qty > 0) {
            $cost = $qty * 10;
            if ($member['points_balance'] >= $cost) {
                try {
                    $pdo->beginTransaction();
                    
                    // Deduct points
                    loyalty_deduct_points($pdo, $memberId, $cost, 'raffle_ticket', "Tukar $qty Tiket Undian ({$activeBatch['name']})");
                    
                    // Generate Tickets
                    $stmtInsert = $pdo->prepare("INSERT INTO raffle_tickets (ticket_code, batch_id, member_id) VALUES (?, ?, ?)");
                    for ($i = 0; $i < $qty; $i++) {
                        $code = 'UND-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));
                        $stmtInsert->execute([$code, $batchId, $memberId]);
                    }
                    
                    $pdo->commit();
                    $message = "Berhasil menukar $cost poin menjadi $qty Tiket Undian! Semoga beruntung!";
                    
                    // Refresh member points
                    $stmt->execute([$memberId]);
                    $member = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Terjadi kesalahan saat memproses tiket. Silakan coba lagi.";
                }
            } else {
                $error = "Poin Anda tidak cukup. Butuh $cost poin untuk $qty tiket.";
            }
        }
    }

    // Fetch my tickets
    $stmtTickets = $pdo->prepare("SELECT ticket_code, created_at FROM raffle_tickets WHERE batch_id = ? AND member_id = ? ORDER BY created_at DESC");
    $stmtTickets->execute([$batchId, $memberId]);
    $myTickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
}

?>
<?php ob_start(); ?>

<div class="header-section text-center py-4 text-white" style="background: linear-gradient(135deg, #FF6B00 0%, #D40000 100%); border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; margin-top: -20px; padding-top: 40px !important;">
    <h2 class="fw-bold mb-1">Undian Spesial</h2>
    <p class="mb-0 opacity-75">Tukar poin Anda dan raih hadiah impian!</p>
</div>

<div class="container py-4">
    <?php if ($message): ?>
        <div class="alert alert-success fw-bold text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger fw-bold text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$activeBatch): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <h4 class="text-muted mb-3">Tidak Ada Event Undian</h4>
            <p>Saat ini belum ada periode undian yang aktif. Terus kumpulkan poin Anda dan nantikan event berikutnya!</p>
            <div>
                <a href="<?= url('/user/dashboard.php') ?>" class="btn btn-primary rounded-pill px-4">Kembali ke Dashboard</a>
            </div>
        </div>
    <?php else: ?>
        
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body text-center p-4">
                <div class="badge bg-warning text-dark mb-2 px-3 py-2 rounded-pill fw-bold">SEDANG BERLANGSUNG</div>
                <h4 class="fw-bold text-primary mb-1"><?= htmlspecialchars($activeBatch['name']) ?></h4>
                <p class="text-muted small mb-0">Periode ditutup: <?= date('d F Y', strtotime($activeBatch['end_date'])) ?></p>
            </div>
        </div>

        <h5 class="fw-bold mb-3">🎁 Hadiah Utama</h5>
        <div class="row g-3 mb-4">
            <?php foreach ($prizes as $p): ?>
            <div class="col-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden text-center">
                    <?php if ($p['image_url']): ?>
                        <img src="<?= url('/public/assets/' . $p['image_url']) ?>" alt="Prize" class="card-img-top" style="height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                            <span class="text-muted opacity-50">Hadiah</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body p-2">
                        <span class="fw-bold fs-6"><?= htmlspecialchars($p['name']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($prizes)): ?>
                <div class="col-12"><p class="text-muted fst-italic">Katalog hadiah sedang disiapkan...</p></div>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: #FFF9F5; border: 2px solid #FFE5D0 !important;">
            <div class="card-body p-4 text-center">
                <p class="text-muted mb-1">Saldo Poin Anda Saat Ini</p>
                <h1 class="display-4 fw-black text-primary mb-3"><?= number_format($member['points_balance']) ?> <small class="fs-5">Pts</small></h1>
                
                <h6 class="fw-bold mb-3">10 Poin = 1 Tiket Undian</h6>
                
                <form method="POST" class="d-flex flex-column gap-2" onsubmit="return confirm('Apakah Anda yakin ingin menukarkan poin dengan tiket ini? Poin yang sudah ditukar tidak dapat dikembalikan.')">
                    <?php if ($member['points_balance'] >= 10): ?>
                        <button type="submit" name="qty" value="1" class="btn btn-lg btn-outline-primary fw-bold rounded-pill">Tukar 1 Tiket (10 Pts)</button>
                    <?php endif; ?>
                    <?php if ($member['points_balance'] >= 50): ?>
                        <button type="submit" name="qty" value="5" class="btn btn-lg btn-primary fw-bold rounded-pill">Tukar 5 Tiket (50 Pts)</button>
                    <?php endif; ?>
                    <?php if ($member['points_balance'] >= 100): ?>
                        <button type="submit" name="qty" value="10" class="btn btn-lg btn-danger fw-bold rounded-pill">Tukar 10 Tiket (100 Pts)</button>
                    <?php endif; ?>
                    <?php if ($member['points_balance'] < 10): ?>
                        <button type="button" class="btn btn-lg btn-secondary fw-bold rounded-pill" disabled>Poin Tidak Cukup</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <h5 class="fw-bold mb-3">🎫 Tiket Saya (<?= count($myTickets) ?>)</h5>
        <?php if (empty($myTickets)): ?>
            <div class="alert alert-light border rounded-4 text-center py-4">
                <span class="text-muted">Anda belum memiliki tiket undian di periode ini.</span>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 mb-5">
                <div class="list-group list-group-flush rounded-4">
                    <?php foreach ($myTickets as $t): ?>
                    <div class="list-group-item py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark font-monospace fs-5"><?= $t['ticket_code'] ?></span>
                            <small class="text-muted"><?= date('d M H:i', strtotime($t['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$title = "Kupon Undian - Kios Lumero";
require __DIR__ . '/views/layout.php';
?>

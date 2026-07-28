<?php include __DIR__ . '/../../../views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-checkup-list me-2 text-primary"></i>Validasi Klaim (Poin & Undian)</h3>
                <p class="text-muted mb-0">Halaman terpadu untuk memvalidasi penukaran Poin dan hadiah Undian Event.</p>
            </div>
            <a href="<?= url('/loyalty/members') ?>" class="btn btn-outline-secondary rounded-pill">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Member
            </a>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="ti ti-check me-2"></i><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="ti ti-alert-triangle me-2"></i><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<!-- GLOBAL SCANNER -->
<div class="row mb-4">
    <div class="col-12 text-center">
        <button type="button" id="btn-global-scan" class="btn btn-primary rounded-pill px-4 shadow py-2 fw-bold d-inline-flex align-items-center gap-2">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7V4h3"></path><path d="M20 7V4h-3"></path><path d="M4 17v3h3"></path><path d="M20 17v3h-3"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            Buka Kamera Scanner
        </button>
        <div id="qr-reader" style="width:100%; max-width: 500px; display:none; margin: 20px auto 0; border-radius: 12px; overflow: hidden; border: 2px solid var(--bs-primary);"></div>
    </div>
</div>

<div class="row">
    <!-- KOLOM KIRI: REWARD POIN -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold text-primary mb-3"><i class="ti ti-gift me-2"></i>Validasi Reward (Poin)</h5>
                <form id="form-reward" action="<?= url('/loyalty/redemptions') ?>" method="GET" class="d-flex gap-2">
                    <input type="text" id="input-reward" name="q" class="form-control rounded-pill" placeholder="Cari Kode Penukaran..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary rounded-pill px-3">Cari</button>
                </form>
            </div>
            <div class="card-body px-4 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th>Kode Redeem</th>
                                <th>Member</th>
                                <th>Hadiah</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($redemptions)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">Belum ada riwayat penukaran hadiah.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($redemptions as $rd): ?>
                            <tr>
                                <td class="fw-bold"><code><?= htmlspecialchars($rd['redemption_code'] ?? '-' ) ?></code></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($rd['member_name'] ?? 'Member') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($rd['member_phone'] ?? '') ?></small>
                                </td>
                                <td><?= htmlspecialchars($rd['reward_name'] ?? 'Hadiah') ?></td>
                                <td class="text-end">
                                    <?php if (($rd['status'] ?? '') === 'requested'): ?>
                                    <form action="<?= url('/loyalty/redemptions/update-status') ?>" method="POST" class="d-inline" onsubmit="return confirm('Selesaikan penukaran ini?');">
                                        <input type="hidden" name="id" value="<?= (int)$rd['id'] ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill">Validasi</button>
                                    </form>
                                    <?php else: ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill">SELESAI</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Reward -->
                <?php if (($total_pages_reward ?? 0) > 1): ?>
                <div class="d-flex justify-content-center mt-3">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for($i=1; $i<=$total_pages_reward; $i++): ?>
                        <li class="page-item <?= $i == ($page_reward ?? 1) ? 'active' : '' ?>">
                            <a class="page-link" href="?page_reward=<?= $i ?>&page_event=<?= $page_event ?? 1 ?>&q=<?= urlencode($_GET['q'] ?? '') ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: UNDIAN EVENT -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold text-success mb-3"><i class="ti ti-ticket me-2"></i>Validasi Undian (Event)</h5>
                <form id="form-event" action="<?= url('/loyalty/processEventClaim') ?>" method="POST" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <input type="text" id="input-event" name="qr_code" class="form-control rounded-pill" placeholder="Masukkan Kode Klaim (KAL-...)" value="<?= htmlspecialchars($_GET['code'] ?? '') ?>" required>
                    <button type="submit" class="btn btn-success rounded-pill px-3" style="flex-shrink:0;">Selesai</button>
                </form>
            </div>
            <div class="card-body px-4 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th>Kode Klaim</th>
                                <th>Member</th>
                                <th>Hadiah</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentClaims)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">Belum ada tiket yang divalidasi.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recentClaims as $c): ?>
                            <tr>
                                <td class="fw-bold"><code><?= htmlspecialchars($c['qr_code']) ?></code></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($c['member_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['member_phone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($c['prize_name']) ?></td>
                                <td class="text-end">
                                    <?php if ($c['status'] === 'CLAIMED'): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill">DIAMBIL</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning rounded-pill">PENDING</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Event -->
                <?php if (($total_pages_event ?? 0) > 1): ?>
                <div class="d-flex justify-content-center mt-3">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for($i=1; $i<=$total_pages_event; $i++): ?>
                        <li class="page-item <?= $i == ($page_event ?? 1) ? 'active' : '' ?>">
                            <a class="page-link" href="?page_event=<?= $i ?>&page_reward=<?= $page_reward ?? 1 ?>&q=<?= urlencode($_GET['q'] ?? '') ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<form id="hidden-form" action="<?= url('/loyalty/redemptions') ?>" method="GET" style="display:none;">
    <input type="hidden" name="q" id="hidden-q">
</form>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const btnScan = document.getElementById('btn-global-scan');
    const readerDiv = document.getElementById('qr-reader');
    
    const inputReward = document.getElementById('input-reward');
    const formReward = document.getElementById('form-reward');
    
    const inputEvent = document.getElementById('input-event');
    const formEvent = document.getElementById('form-event');

    const hiddenForm = document.getElementById('hidden-form');
    const hiddenQ = document.getElementById('hidden-q');

    let html5QrcodeScanner = null;

    const scanIcon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h3"></path><path d="M20 7V4h-3"></path><path d="M4 17v3h3"></path><path d="M20 17v3h-3"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const closeIcon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;

    if (btnScan) {
        btnScan.addEventListener('click', function() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
                readerDiv.style.display = 'none';
                btnScan.innerHTML = scanIcon + ' Buka Kamera Scanner';
                btnScan.classList.replace('btn-danger', 'btn-primary');
            } else {
                readerDiv.style.display = 'block';
                html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                btnScan.innerHTML = closeIcon + ' Tutup Kamera Scanner';
                btnScan.classList.replace('btn-primary', 'btn-danger');
            }
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        html5QrcodeScanner.clear();
        html5QrcodeScanner = null;
        readerDiv.style.display = 'none';
        btnScan.innerHTML = scanIcon + ' Buka Kamera Scanner';
        btnScan.classList.replace('btn-danger', 'btn-primary');
        
        let text = decodedText.trim();
        
        // Auto-detect based on KAL- prefix for event
        if (text.startsWith('KAL-')) {
            inputEvent.value = text;
            formEvent.submit();
        } else {
            // Assume it's a reward point code
            hiddenQ.value = text;
            hiddenForm.submit();
        }
    }

    function onScanFailure(error) {
        // ignore errors
    }
});
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

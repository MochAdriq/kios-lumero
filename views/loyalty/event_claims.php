<?php include __DIR__ . '/../../../views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-ticket me-2 text-primary"></i>Validasi Hadiah Undian</h3>
                <p class="text-muted mb-0">Gunakan fitur Scan Kamera di bawah untuk memindai QR Code kupon pelanggan, atau masukkan Kode secara manual.</p>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="ti ti-check me-2"></i><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="ti ti-alert-triangle me-2"></i><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 text-center">
                <div id="qr-reader" style="width:100%; display:none; margin-bottom: 20px; border-radius: 12px; overflow: hidden; border: 2px solid var(--bs-primary);"></div>
                
                <div class="mb-4">
                    <button type="button" id="btn-scan" class="btn btn-primary rounded-circle shadow" style="width: 80px; height: 80px; padding: 0;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 7V4h3"></path><path d="M20 7V4h-3"></path><path d="M4 17v3h3"></path><path d="M20 17v3h-3"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                
                <h4 class="fw-bold mb-3">Input Kode Manual</h4>
                <form id="claim-form" action="<?= url('/loyalty/processEventClaim') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <?php $prefillCode = htmlspecialchars($_GET['code'] ?? ''); ?>
                        <input type="text" id="qr_code_input" name="qr_code" value="<?= $prefillCode ?>" class="form-control form-control-lg text-center fw-bold" placeholder="Contoh: KAL-A1B2C3D4" required autofocus style="letter-spacing: 2px; font-size: 1.5rem;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">
                        <i class="ti ti-check me-2"></i> Tandai Sudah Diambil
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold mb-0">Riwayat Validasi Terakhir</h5>
            </div>
            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Kode Klaim</th>
                                <th>Pelanggan</th>
                                <th>Hadiah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentClaims)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada tiket yang divalidasi.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recentClaims as $c): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><code><?= htmlspecialchars($c['qr_code']) ?></code></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($c['member_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['member_phone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($c['prize_name']) ?></td>
                                <td>
                                    <?php if ($c['status'] === 'CLAIMED'): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill">SUDAH DIAMBIL</span>
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
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const btnScan = document.getElementById('btn-scan');
    const readerDiv = document.getElementById('qr-reader');
    const inputCode = document.getElementById('qr_code_input');
    const form = document.getElementById('claim-form');
    let html5QrcodeScanner = null;

    const scanIcon = `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h3"></path><path d="M20 7V4h-3"></path><path d="M4 17v3h3"></path><path d="M20 17v3h-3"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const closeIcon = `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;

    btnScan.addEventListener('click', function() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
            readerDiv.style.display = 'none';
            btnScan.innerHTML = scanIcon;
            btnScan.classList.replace('btn-danger', 'btn-primary');
        } else {
            readerDiv.style.display = 'block';
            html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            btnScan.innerHTML = closeIcon;
            btnScan.classList.replace('btn-primary', 'btn-danger');
        }
    });

    function onScanSuccess(decodedText, decodedResult) {
        html5QrcodeScanner.clear();
        html5QrcodeScanner = null;
        readerDiv.style.display = 'none';
        btnScan.innerHTML = scanIcon;
        btnScan.classList.replace('btn-danger', 'btn-primary');
        
        inputCode.value = decodedText;
        form.submit();
    }

    function onScanFailure(error) {
        // ignore errors while scanning
    }
});
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

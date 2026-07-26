<?php include __DIR__ . '/../../../views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-ticket me-2 text-primary"></i>Validasi Hadiah Undian</h3>
                <p class="text-muted mb-0">Masukkan Kode Klaim pelanggan (dari WhatsApp) untuk menyerahkan hadiah.</p>
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
                <div class="mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="ti ti-scan fs-1"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-3">Input Kode Klaim</h4>
                <form action="<?= url('/loyalty/processEventClaim') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <input type="text" name="qr_code" class="form-control form-control-lg text-center fw-bold" placeholder="Contoh: KAL-A1B2C3D4" required autofocus style="letter-spacing: 2px; font-size: 1.5rem;">
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

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

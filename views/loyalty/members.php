<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-award me-2 text-warning"></i>Data Member & Loyalty Poin</h3>
                <p class="text-muted mb-0">Kelola pelanggan terdaftar, saldo poin, dan aturan perolehan poin.</p>
            </div>
            <a href="<?= url('/member/hook.php', false) ?>" target="_blank" class="btn btn-outline-primary rounded-pill">
                <i class="ti ti-external-link me-1"></i> Buka Portal Member
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Aturan Poin -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="ti ti-settings me-1"></i> Pengaturan Aturan Poin</h6>
            </div>
            <div class="card-body">
                <form action="<?= url('/loyalty/settings/update') ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kelipatan Belanja (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="earn_amount" class="form-control" value="<?= (int)($settings['earn_amount'] ?? 1000) ?>" required>
                        </div>
                        <small class="text-muted" style="font-size:0.75rem;">Setiap kelipatan belanja nominal ini akan menghasilkan poin.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Poin Didapat per Kelipatan</label>
                        <div class="input-group">
                            <input type="number" name="earn_point" class="form-control" value="<?= (int)($settings['earn_point'] ?? 1) ?>" required>
                            <span class="input-group-text">Poin</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nilai Potongan 1 Poin (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="redeem_point_value" class="form-control" value="<?= (int)($settings['redeem_point_value'] ?? 100) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Minimal Poin untuk Redeem</label>
                        <div class="input-group">
                            <input type="number" name="minimum_redeem_points" class="form-control" value="<?= (int)($settings['minimum_redeem_points'] ?? 10) ?>" required>
                            <span class="input-group-text">Poin</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Simpan Aturan Poin</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistik Singkat -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="ti ti-users me-1"></i> Daftar Member Terdaftar (<?= count($members) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Nama & No. HP</th>
                                <th>Total Poin</th>
                                <th>Total Belanja</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada member terdaftar.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold"><?= htmlspecialchars($m['name'] ?: 'Member Baru') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($m['phone']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                                        <?= number_format((int)$m['total_points'],0,',','.') ?> Poin
                                    </span>
                                </td>
                                <td><?= rupiah($m['total_spent'] ?? 0) ?></td>
                                <td>
                                    <span class="badge <?= ($m['status'] ?? 'active') === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary' ?> rounded-pill">
                                        <?= strtoupper($m['status'] ?? 'ACTIVE') ?>
                                    </span>
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

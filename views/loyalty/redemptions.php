<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-checkup-list me-2 text-primary"></i>Validasi Penukaran Poin</h3>
                <p class="text-muted mb-0">Riwayat penukaran hadiah poin yang dilakukan oleh pelanggan.</p>
            </div>
            <a href="<?= url('/loyalty/members') ?>" class="btn btn-outline-secondary rounded-pill">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Data Member
            </a>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <form action="<?= url('/loyalty/redemptions') ?>" method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control rounded-pill" placeholder="Cari Kode Redeem..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Cari</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Kode Redeem</th>
                        <th>Member</th>
                        <th>Hadiah</th>
                        <th>Poin Terpotong</th>
                        <th>Status</th>
                        <th>Waktu</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($redemptions)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat penukaran hadiah.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($redemptions as $rd): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><code><?= htmlspecialchars($rd['redemption_code'] ?? '-' ) ?></code></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($rd['member_name'] ?? 'Member') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($rd['member_phone'] ?? '') ?></small>
                        </td>
                        <td><?= htmlspecialchars($rd['reward_name'] ?? 'Hadiah') ?></td>
                        <td><span class="badge bg-warning text-dark px-3 py-1 rounded-pill"><?= (int)($rd['points_spent'] ?? 0) ?> Poin</span></td>
                        <td>
                            <span class="badge bg-info-subtle text-info rounded-pill"><?= strtoupper($rd['status'] ?? 'REQUESTED') ?></span>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($rd['created_at'] ?? '') ?></td>
                        <td class="text-end pe-4">
                            <?php if (($rd['status'] ?? '') === 'requested'): ?>
                            <form action="<?= url('/loyalty/redemptions/update-status') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin memvalidasi dan menyelesaikan penukaran hadiah ini?');">
                                <input type="hidden" name="id" value="<?= (int)$rd['id'] ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn btn-sm btn-success rounded-pill">Validasi</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small"><i class="ti ti-check"></i> Selesai</span>
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

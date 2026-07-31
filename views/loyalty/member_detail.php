<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-user me-2 text-primary"></i>Detail Member</h3>
                <p class="text-muted mb-0">Informasi profil dan riwayat poin pelanggan.</p>
            </div>
            <a href="<?= url('/loyalty/members') ?>" class="btn btn-outline-secondary rounded-pill">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Profil Member -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="ti ti-id me-1"></i> Profil Member</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 80px; height: 80px;">
                        <i class="ti ti-user fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($member['name'] ?: 'Member Baru') ?></h5>
                    <p class="text-muted mb-0"><?= htmlspecialchars($member['phone']) ?></p>
                    <div class="mt-2">
                        <span class="badge <?= ($member['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                            <?= strtoupper($member['status'] ?? 'ACTIVE') ?>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block fw-bold">Total Poin Aktif</small>
                    <div class="fs-4 fw-bold text-warning"><?= number_format((int)$member['total_points'], 0, ',', '.') ?> Poin</div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block fw-bold">Total Belanja (Sepanjang Waktu)</small>
                    <div class="fw-bold"><?= function_exists('rupiah') ? rupiah($member['total_spent'] ?? 0) : 'Rp ' . number_format($member['total_spent'] ?? 0, 0, ',', '.') ?></div>
                </div>
                
                <div class="mb-0">
                    <small class="text-muted d-block fw-bold">Bergabung Sejak</small>
                    <div><?= date('d M Y, H:i', strtotime($member['joined_at'] ?? $member['created_at'] ?? 'now')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Poin -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="ti ti-history me-1"></i> Riwayat Transaksi Poin</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th>Keterangan / Tipe</th>
                                <th class="text-success text-center">Poin Masuk</th>
                                <th class="text-danger text-center">Poin Keluar</th>
                                <th class="text-end pe-3">Saldo Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pointLogs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat transaksi poin untuk member ini.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($pointLogs as $log): ?>
                            <tr>
                                <td class="ps-3 text-nowrap">
                                    <small class="fw-bold text-dark d-block"><?= date('d/m/Y', strtotime($log['created_at'])) ?></small>
                                    <small class="text-muted"><?= date('H:i', strtotime($log['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $typeLabel = 'Lainnya';
                                        $typeBadge = 'bg-secondary';
                                        switch($log['type']) {
                                            case 'earn': $typeLabel = 'Dapat Poin (Transaksi)'; $typeBadge = 'bg-success'; break;
                                            case 'claim': $typeLabel = 'Klaim Struk'; $typeBadge = 'bg-success'; break;
                                            case 'redeem': $typeLabel = 'Tukar Poin (Belanja)'; $typeBadge = 'bg-danger'; break;
                                            case 'reward': $typeLabel = 'Tukar Hadiah'; $typeBadge = 'bg-danger'; break;
                                            case 'bonus_profile': $typeLabel = 'Bonus Biodata'; $typeBadge = 'bg-info text-dark'; break;
                                        }
                                    ?>
                                    <span class="badge <?= $typeBadge ?> rounded-pill mb-1" style="font-size:0.7rem;"><?= $typeLabel ?></span>
                                    <div class="small text-muted"><?= htmlspecialchars($log['description'] ?: '-') ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$log['points_in'] > 0): ?>
                                        <span class="text-success fw-bold">+<?= number_format((int)$log['points_in'], 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$log['points_out'] > 0): ?>
                                        <span class="text-danger fw-bold">-<?= number_format((int)$log['points_out'], 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3 fw-bold">
                                    <?= number_format((int)$log['balance_after'], 0, ',', '.') ?>
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

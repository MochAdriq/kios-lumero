<?php include __DIR__ . '/../../../views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-gift me-2 text-danger"></i>Katalog Hadiah Poin</h3>
                <p class="text-muted mb-0">Daftar produk atau diskon yang dapat ditukarkan member menggunakan poin mereka.</p>
            </div>
            <a href="<?= url('/loyalty/members') ?>" class="btn btn-outline-secondary rounded-pill">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Data Member
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama Hadiah</th>
                        <th>Deskripsi</th>
                        <th>Poin Dibutuhkan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rewards)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada katalog hadiah poin.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($rewards as $r): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($r['name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($r['description'] ?? '') ?></td>
                        <td>
                            <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold">
                                <?= number_format((int)$r['required_points'],0,',','.') ?> Poin
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= ($r['is_active'] ?? 1) ? 'bg-success-subtle text-success' : 'bg-secondary' ?> rounded-pill">
                                <?= ($r['is_active'] ?? 1) ? 'Aktif' : 'Nonaktif' ?>
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

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

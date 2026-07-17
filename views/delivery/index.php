<?php include __DIR__ . '/../shared-flash.php'; ?>

<div class="sim-hero mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="sim-kicker">Delivery & Kurir Management</span>
            <h2>Monitoring Pesanan Delivery</h2>
            <p>Pantau status pengantaran, kelola kurir, dan verifikasi alamat pengiriman pesanan online secara real-time.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/delivery') ?>" class="btn btn-primary d-flex align-items-center gap-2 active">
                <?= sim_icon('ti-list-details', '', 'width:1.1rem; height:1.1rem;') ?> Monitoring Pesanan
            </a>
            <a href="<?= url('/delivery/settings') ?>" class="btn btn-secondary text-light d-flex align-items-center gap-2">
                <?= sim_icon('ti-settings', '', 'width:1.1rem; height:1.1rem;') ?> Pengaturan Delivery
            </a>
        </div>
    </div>
</div>

<div class="sim-card mb-4">
    <form method="get" action="<?= url('/delivery') ?>" class="row g-3 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0 text-muted"><?= sim_icon('ti-search') ?></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Cari No Pesanan, Nama, HP, atau Alamat..." value="<?= htmlspecialchars($searchQuery ?? '') ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Status Pengantaran --</option>
                <option value="preparing" <?= ($statusFilter ?? '') === 'preparing' ? 'selected' : '' ?>>Menunggu Kurir / Disiapkan</option>
                <option value="on_the_way" <?= ($statusFilter ?? '') === 'on_the_way' ? 'selected' : '' ?>>Dalam Pengantaran (On The Way)</option>
                <option value="delivered" <?= ($statusFilter ?? '') === 'delivered' ? 'selected' : '' ?>>Sudah Diterima (Delivered)</option>
                <option value="cancelled" <?= ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' ?>>Dibatalkan (Cancelled)</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-secondary flex-grow-1">Filter</button>
            <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
            <a href="<?= url('/delivery') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="sim-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 140px;">No Pesanan</th>
                    <th>Pelanggan & Kontak</th>
                    <th>Alamat Pengantaran</th>
                    <th style="width: 130px;">Jarak & Ongkir</th>
                    <th style="width: 150px;">Total Tagihan</th>
                    <th style="width: 160px;">Status Kurir</th>
                    <th style="width: 140px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <div class="mb-2"><?= sim_icon('ti-package-off', '', 'width:3rem; height:3rem; opacity:0.4;') ?></div>
                        <h5>Belum Ada Pesanan Delivery</h5>
                        <p class="small mb-0">Pesanan dari pelanggan yang memilih opsi "Delivery" akan muncul di sini.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): 
                        $st = $o['delivery_status'] ?? 'preparing';
                        $badgeClass = 'bg-warning text-dark';
                        $badgeLabel = 'Disiapkan';
                        if ($st === 'on_the_way') { $badgeClass = 'bg-primary'; $badgeLabel = 'Dalam Pengantaran'; }
                        elseif ($st === 'delivered') { $badgeClass = 'bg-success'; $badgeLabel = 'Diterima'; }
                        elseif ($st === 'cancelled') { $badgeClass = 'bg-danger'; $badgeLabel = 'Dibatalkan'; }

                        $items = $itemsMap[$o['id']] ?? [];
                    ?>
                    <tr>
                        <td>
                            <strong class="d-block text-warning"><?= htmlspecialchars($o['pre_order_no']) ?></strong>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></small>
                        </td>
                        <td>
                            <strong class="d-block"><?= htmlspecialchars($o['customer_name'] ?? 'Pelanggan') ?></strong>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', fo_normalize_phone($o['customer_phone'] ?? '081234567890')) ?>" target="_blank" class="small text-info text-decoration-none d-flex align-items-center gap-1 mt-1">
                                <?= sim_icon('ti-brand-whatsapp', '', 'width:1rem; height:1rem;') ?> <?= htmlspecialchars($o['customer_phone'] ?? '') ?>
                            </a>
                        </td>
                        <td>
                            <div class="small" style="max-width: 260px; line-height: 1.4;">
                                <?= htmlspecialchars($o['delivery_address'] ?? '-') ?>
                            </div>
                            <?php if ((float)$o['delivery_lat'] != 0 && (float)$o['delivery_lng'] != 0): ?>
                            <a href="https://www.google.com/maps?q=<?= (float)$o['delivery_lat'] ?>,<?= (float)$o['delivery_lng'] ?>" target="_blank" class="badge bg-secondary text-light mt-1 text-decoration-none">
                                📍 Buka di Google Maps
                            </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-dark border border-secondary text-light mb-1"><?= number_format((float)$o['delivery_distance_km'], 2) ?> km</span>
                            <div class="small fw-bold text-success">
                                <?= ((int)$o['delivery_fee'] === 0) ? 'GRATIS' : 'Rp ' . number_format((int)$o['delivery_fee'], 0, ',', '.') ?>
                            </div>
                        </td>
                        <td>
                            <strong class="fs-6 d-block">Rp <?= number_format((int)$o['total'], 0, ',', '.') ?></strong>
                            <small class="text-muted"><?= count($items) ?> item menu</small>
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?> px-3 py-2 w-100 mb-1"><?= $badgeLabel ?></span>
                            <small class="d-block text-muted text-center" style="font-size: 11px;">Kurir: <?= htmlspecialchars($o['delivery_courier_name'] ?: 'Kurir Internal') ?></small>
                        </td>
                        <td class="text-end">
                            <?php if (($o['payment_status'] ?? '') !== 'paid'): ?>
                            <a href="<?= url('/payments') ?>" class="btn btn-sm btn-warning mb-1 w-100 fw-bold">
                                <?= sim_icon('ti-credit-card') ?> Verifikasi Payment
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-info w-100" data-bs-toggle="modal" data-bs-target="#modalStatus<?= $o['id'] ?>">
                                <?= sim_icon('ti-edit') ?> Update Status
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Update Status Placed Safely Outside Table/DOM -->
<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $o): 
        $st = $o['delivery_status'] ?? 'preparing';
        $items = $itemsMap[$o['id']] ?? [];
    ?>
    <div class="modal fade" id="modalStatus<?= $o['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="<?= url('/delivery/update-status') ?>" class="modal-content bg-dark text-light border border-secondary shadow-lg">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <div class="modal-header border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fs-6 fw-bold mb-0">Update Status Pengantaran — <?= htmlspecialchars($o['pre_order_no']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1">Nama Kurir yang Mengantar</label>
                        <input type="text" name="delivery_courier_name" class="form-control bg-secondary text-light border-0 py-2" value="<?= htmlspecialchars($o['delivery_courier_name'] ?: 'Kurir Internal') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1">Status Pengantaran</label>
                        <select name="delivery_status" class="form-select bg-secondary text-light border-0 py-2">
                            <option value="preparing" <?= $st === 'preparing' ? 'selected' : '' ?>>Menunggu Kurir / Disiapkan</option>
                            <option value="on_the_way" <?= $st === 'on_the_way' ? 'selected' : '' ?>>Dalam Pengantaran (On The Way)</option>
                            <option value="delivered" <?= $st === 'delivered' ? 'selected' : '' ?>>Sudah Diterima Pelanggan (Delivered)</option>
                            <option value="cancelled" <?= $st === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="bg-black bg-opacity-50 p-3 rounded mt-3 border border-secondary border-opacity-25">
                        <h6 class="small text-muted text-uppercase fw-bold mb-2">Rincian Item Pesanan:</h6>
                        <ul class="list-unstyled small mb-0">
                            <?php foreach ($items as $it): ?>
                            <li class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                                <span><?= (int)$it['qty'] ?>x <?= htmlspecialchars($it['item_name']) ?></span>
                                <span class="text-warning fw-bold">Rp <?= number_format((int)$it['line_total'], 0, ',', '.') ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

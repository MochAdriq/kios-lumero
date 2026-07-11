<?php
    include __DIR__.'/../shared-flash.php';
    $activeTab = $activeTab ?? ($_GET['tab'] ?? 'void');
?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <span class="sim-kicker text-primary"><?= sim_icon('ti-adjustments') ?> Corrections</span>
        <h2 class="mb-1">Koreksi & Void</h2>
        <p class="mb-0 text-muted">Void order, koreksi stok bahan baku, dan riwayat perubahan. Semua perubahan tercatat di audit log.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/costing-diagnostics') ?>" class="btn btn-outline-secondary btn-sm"><?= sim_icon('ti-stethoscope', 'me-1') ?>Diagnostik HPP</a>
        <a href="<?= url('/audit-logs') ?>" class="btn btn-outline-secondary btn-sm"><?= sim_icon('ti-history', 'me-1') ?>Audit Log</a>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'void' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-void" type="button">
            <?= sim_icon('ti-receipt-off', 'me-1') ?>Void Order
            <?php if (!empty($orders)): ?><span class="badge bg-primary ms-1"><?= count($orders) ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'stock' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-stock" type="button">
            <?= sim_icon('ti-package', 'me-1') ?>Koreksi Stok
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'history' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-history" type="button">
            <?= sim_icon('ti-history', 'me-1') ?>Riwayat
            <?php if (!empty($corrections)): ?><span class="badge bg-secondary ms-1"><?= count($corrections) ?></span><?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Tab: Void Order -->
    <div class="tab-pane fade <?= $activeTab === 'void' ? 'show active' : '' ?>" id="tab-void">
        <?php if (empty($orders)): ?>
            <div class="sim-card shadow-sm border-0 bg-white text-center py-5">
                <?= sim_icon('ti-circle-check', 'fs-1 text-success d-block mb-2') ?>
                <h5 class="text-muted">Tidak ada order yang bisa di-void saat ini.</h5>
            </div>
        <?php else: ?>
            <div class="sim-card shadow-sm border-0 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><?= sim_icon('ti-receipt', 'me-1 text-primary') ?>Order Terbaru (Bisa Di-Void)</h6>
                    <small class="text-muted"><?= count($orders) ?> order</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="tbl-orders">
                        <thead class="table-light">
                            <tr>
                                <th>No Order</th>
                                <th>Tanggal</th>
                                <th>Kasir</th>
                                <th>Payment</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">HPP</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($o['order_number'] ?? '-') ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td><?= htmlspecialchars($o['cashier_name'] ?? '-') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($o['payment_method'] ?? '-') ?></span></td>
                                <td class="text-end fw-bold"><?= rupiah($o['grand_total'] ?? 0) ?></td>
                                <td class="text-end text-muted"><?= rupiah($o['total_hpp'] ?? 0) ?></td>
                                <td class="text-center"><span class="badge bg-success"><?= htmlspecialchars($o['order_status'] ?? '-') ?></span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            data-bs-toggle="modal" data-bs-target="#voidModal"
                                            data-order-id="<?= (int)$o['id'] ?>"
                                            data-order-no="<?= htmlspecialchars($o['order_number'] ?? '') ?>"
                                            data-order-total="<?= rupiah($o['grand_total'] ?? 0) ?>">
                                        <?= sim_icon('ti-x', 'me-1') ?>Void
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Stock Correction -->
    <div class="tab-pane fade <?= $activeTab === 'stock' ? 'show active' : '' ?>" id="tab-stock">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="sim-card shadow-sm border-0 bg-white">
                    <h6 class="fw-bold border-bottom pb-2 mb-3"><?= sim_icon('ti-edit', 'me-1 text-primary') ?>Form Koreksi Stok</h6>
                    <form method="post" action="<?= url('/corrections/stock') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-medium">Bahan Baku <span class="text-danger">*</span></label>
                            <select name="raw_material_id" class="form-select form-select-sm" required id="sel-material">
                                <option value="">— Pilih bahan —</option>
                                <?php foreach ($materials as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>" 
                                            data-stock="<?= number_format((float)$m['stock_qty'], 2) ?>"
                                            data-unit="<?= htmlspecialchars($m['unit_symbol'] ?? '') ?>">
                                        <?= htmlspecialchars($m['name']) ?> 
                                        (<?= htmlspecialchars($m['category_name'] ?? '') ?>) 
                                        — Stok: <?= number_format((float)$m['stock_qty'], 2) ?> <?= htmlspecialchars($m['unit_symbol'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-medium">Tipe Koreksi <span class="text-danger">*</span></label>
                            <select name="correction_type" class="form-select form-select-sm" required>
                                <option value="stock_addition">➕ Tambah Stok (Stock In)</option>
                                <option value="stock_reduction">➖ Kurangi Stok (Stock Out)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-medium">Jumlah <span class="text-danger">*</span></label>
                            <input name="qty" type="number" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="Contoh: 2.5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-medium">Alasan Koreksi <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="Contoh: Bahan basi/expired, stok fisik tidak cocok, penerimaan kurang..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium shadow-sm">
                            <?= sim_icon('ti-check', 'me-1') ?>Simpan Koreksi
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="sim-card shadow-sm border-0 bg-white">
                    <h6 class="fw-bold border-bottom pb-2 mb-3"><?= sim_icon('ti-info-circle', 'me-1 text-info') ?>Panduan Koreksi Stok</h6>
                    <div class="alert alert-info border-0 small mb-3">
                        <strong>Kapan pakai Koreksi Stok?</strong>
                        <ul class="mb-0 mt-1">
                            <li><strong>Tambah Stok</strong> — Barang datang tapi belum di-input via Pembelian, koreksi stock opname</li>
                            <li><strong>Kurangi Stok</strong> — Bahan basi/expired, rusak, hilang, selisih stock opname</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning border-0 small mb-0">
                        <strong>Penting:</strong> Setiap koreksi akan tercatat di <strong>Audit Log</strong> dan <strong>Riwayat Koreksi</strong>. 
                        Stok bahan baku langsung berubah di <strong>Gudang Bahan</strong>.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: History -->
    <div class="tab-pane fade <?= $activeTab === 'history' ? 'show active' : '' ?>" id="tab-history">
        <?php if (empty($corrections)): ?>
            <div class="sim-card shadow-sm border-0 bg-white text-center py-5">
                <?= sim_icon('ti-history-off', 'fs-1 text-muted d-block mb-2') ?>
                <h5 class="text-muted">Belum ada riwayat koreksi.</h5>
            </div>
        <?php else: ?>
            <div class="sim-card shadow-sm border-0 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><?= sim_icon('ti-history', 'me-1 text-primary') ?>Riwayat Koreksi Terakhir</h6>
                    <small class="text-muted"><?= count($corrections) ?> entri</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th>Referensi</th>
                                <th>Bahan</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Sebelum</th>
                                <th class="text-end">Sesudah</th>
                                <th>Alasan</th>
                                <th>Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($corrections as $c): ?>
                            <?php
                                $typeBadge = match($c['correction_type'] ?? '') {
                                    'order_void'      => '<span class="badge bg-danger">Void Order</span>',
                                    'stock_addition'  => '<span class="badge bg-success">Tambah Stok</span>',
                                    'stock_reduction' => '<span class="badge bg-warning text-dark">Kurangi Stok</span>',
                                    default           => '<span class="badge bg-secondary">' . htmlspecialchars($c['correction_type'] ?? '-') . '</span>',
                                };
                            ?>
                            <tr>
                                <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                <td><?= $typeBadge ?></td>
                                <td>
                                    <?php if (!empty($c['order_number'])): ?>
                                        <strong><?= htmlspecialchars($c['order_number']) ?></strong>
                                    <?php elseif ($c['reference_type'] === 'raw_material'): ?>
                                        <small class="text-muted">Stok Manual</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($c['material_name'] ?? '-') ?></td>
                                <td class="text-end"><?= number_format((float)$c['qty'], 2) ?></td>
                                <td class="text-end text-muted"><?= $c['old_value'] !== null ? number_format((float)$c['old_value'], 2) : '-' ?></td>
                                <td class="text-end fw-bold"><?= $c['new_value'] !== null ? number_format((float)$c['new_value'], 2) : '-' ?></td>
                                <td><small><?= htmlspecialchars(mb_strimwidth($c['reason'] ?? '', 0, 60, '...')) ?></small></td>
                                <td><?= htmlspecialchars($c['user_name'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Void Confirmation Modal -->
<div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= url('/corrections/void-order') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" id="void-order-id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title"><?= sim_icon('ti-alert-triangle', 'me-1') ?>Konfirmasi Void Order</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger border-0 mb-3">
                        <strong>Perhatian!</strong> Void order bersifat permanen. Stok bahan baku akan dikembalikan ke gudang.
                    </div>
                    <p class="mb-2">Order: <strong id="void-order-no">-</strong></p>
                    <p class="mb-3">Total: <strong id="void-order-total">-</strong></p>
                    <div class="mb-0">
                        <label class="form-label small fw-medium">Alasan Void <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="Jelaskan alasan void order ini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm"><?= sim_icon('ti-x', 'me-1') ?>Void Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Populate void modal with order data
    var voidModal = document.getElementById('voidModal');
    if(voidModal){
        voidModal.addEventListener('show.bs.modal', function(e){
            var btn = e.relatedTarget;
            document.getElementById('void-order-id').value = btn.getAttribute('data-order-id');
            document.getElementById('void-order-no').textContent = btn.getAttribute('data-order-no');
            document.getElementById('void-order-total').textContent = btn.getAttribute('data-order-total');
        });
    }
});
</script>

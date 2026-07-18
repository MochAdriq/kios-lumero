<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <span class="sim-kicker text-primary"><?= sim_icon('ti-packages') ?> Inventory Management</span>
        <h2 class="mb-1">Gudang Bahan Baku</h2>
        <p class="mb-0 text-muted">Pantau ketersediaan stok bahan. Data berubah otomatis melalui modul <strong>Pembelian</strong> dan pemotongan <strong>Resep POS</strong>.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3">
            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-box', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$stats['total_materials'] ?></div>
                <small class="text-muted fw-medium">Total Bahan</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3">
            <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-category', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$stats['total_categories'] ?></div>
                <small class="text-muted fw-medium">Kategori</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3 border-bottom border-warning border-3">
            <div class="bg-warning-subtle text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-alert-triangle', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$stats['low_stock'] ?></div>
                <small class="text-muted fw-medium">Stok Menipis</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3 border-bottom border-danger border-3">
            <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-x', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$stats['out_of_stock'] ?></div>
                <small class="text-muted fw-medium">Stok Habis</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-3">
        
        <div class="sim-card shadow-sm border-0 mb-4 bg-white">
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-dark"><?= sim_icon('ti-folder-plus', 'me-1 text-primary') ?> Kategori Baru</h6>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1 fw-medium">Nama Kategori <span class="text-danger">*</span></label>
                    <input name="name" class="form-control form-control-sm" placeholder="Misal: Daging, Sayur..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1 fw-medium">Urutan Tampil (Opsional)</label>
                    <input name="sort_order" type="number" class="form-control form-control-sm" placeholder="0" value="0" min="0">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium shadow-sm">
                    <?= sim_icon('ti-device-floppy', 'me-1') ?> Simpan Kategori
                </button>
            </form>
        </div>

        <div class="sim-card shadow-sm border-0 mb-4 bg-white">
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-dark"><?= sim_icon('ti-list-check', 'me-1 text-info') ?> Filter Kategori</h6>
            <div class="list-group list-group-flush gap-1">
                <?php 
                    $activeCatId = $activeCat ?? 0; 
                    $isAllActive = ($activeCatId == 0);
                ?>
                <a href="<?= url('/inventory') ?>" 
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-2 rounded border-0 <?= $isAllActive ? 'bg-primary text-white shadow-sm' : 'bg-light text-dark' ?>">
                    <span class="small fw-medium">Semua Kategori</span>
                </a>
                
                <?php if (empty($categories)): ?>
                    <div class="text-center py-3 text-muted small">Belum ada kategori.</div>
                <?php else: ?>
                    <?php foreach ($categories as $cat): 
                        $isActive = ($activeCatId == $cat['id']);
                    ?>
                    <a href="<?= url('/inventory?cat='.$cat['id']) ?>" 
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-2 rounded border-0 <?= $isActive ? 'bg-primary text-white shadow-sm' : 'bg-light text-dark' ?>">
                        <span class="small fw-medium <?= $isActive ? 'text-white' : 'text-dark' ?>"><?= htmlspecialchars($cat['name']) ?></span>
                        <span class="badge <?= $isActive ? 'bg-white text-primary' : 'bg-white text-secondary' ?> border <?= $isActive ? 'border-white' : 'border-secondary-subtle' ?> px-2 py-1 rounded-pill">
                            <?= (int)$cat['material_count'] ?> item
                        </span>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis shadow-sm d-flex gap-3 p-3 mb-0">
            <div><?= sim_icon('ti-info-circle', 'fs-4') ?></div>
            <div class="small lh-sm">
                <strong class="d-block mb-1">Mode Read-Only</strong>
                Anda tidak bisa mengedit stok atau harga modal di halaman ini. Keduanya ter-<em>update</em> otomatis berdasarkan riwayat belanja (Procurement).
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="sim-card shadow-sm border-0 bg-white h-100">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 pb-3 border-bottom gap-3">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="mb-0 fw-bold"><?= sim_icon('ti-table', 'me-1 text-dark') ?> Rincian Bahan Baku</h5>
                </div>
                
                <form method="get" class="d-flex w-100 mt-2 mt-md-0" style="max-width:300px;">
                    <?php if (isset($_GET['cat'])): ?>
                        <input type="hidden" name="cat" value="<?= htmlspecialchars($_GET['cat']) ?>">
                    <?php endif; ?>
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text bg-light border-end-0"><?= sim_icon('ti-search', 'text-muted') ?></span>
                        <input type="text" name="q" class="form-control border-start-0 bg-light" 
                               placeholder="Cari nama bahan atau SKU..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Info Bahan</th>
                            <th style="width: 15%;">Kategori</th>
                            <th class="text-end" style="width: 15%;">Stok Gudang</th>
                            <th class="text-end" style="width: 20%;">HPP / Satuan</th>
                            <th class="text-center" style="width: 15%;">Status</th>
                            <th class="text-end" style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <?= sim_icon('ti-box-off', 'fs-1 text-light mb-2 d-block') ?>
                                <p class="mb-0">Belum ada data bahan baku.<br><small>Gunakan tombol "Tambah Bahan" untuk memulai.</small></p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item):
                                $stockQty = (float)$item['stock_qty'];
                                $minStock = (float)$item['min_stock_qty'];
                                
                                $isOut = $stockQty <= 0;
                                $isLow = !$isOut && $stockQty <= $minStock;
                                
                                $rowClass = $isOut ? 'bg-danger-subtle' : ($isLow ? 'bg-warning-subtle' : '');
                                $stockTextClass = $isOut ? 'text-danger' : ($isLow ? 'text-warning-emphasis' : 'text-dark');
                            ?>
                            <tr class="<?= $isOut || $isLow ? 'border-bottom border-white' : '' ?>">
                                <td class="<?= $rowClass ?>">
                                    <div class="d-flex flex-column">
                                        <strong class="text-dark fs-6"><?= htmlspecialchars($item['name']) ?></strong>
                                        <code class="text-secondary small mt-1 bg-transparent p-0"><?= htmlspecialchars($item['sku']) ?></code>
                                    </div>
                                </td>
                                
                                <td class="<?= $rowClass ?>">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <?= htmlspecialchars($item['category_name']) ?>
                                    </span>
                                </td>
                                
                                <td class="text-end <?= $rowClass ?>">
                                    <div class="d-flex flex-column align-items-end">
                                        <div class="fs-6 fw-bold <?= $stockTextClass ?>">
                                            <?= number_format($stockQty, 2) ?> <span class="fs-6 fw-normal ms-1"><?= htmlspecialchars($item['unit_symbol']) ?></span>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Min: <?= number_format($minStock, 0) ?></small>
                                    </div>
                                </td>
                                
                                <td class="text-end <?= $rowClass ?>">
                                    <strong class="text-dark d-block"><?= rupiah($item['average_cost']) ?></strong>
                                    <small class="text-muted" style="font-size: 0.7rem;">per <?= htmlspecialchars($item['unit_symbol']) ?></small>
                                </td>
                                
                                <td class="text-center <?= $rowClass ?>">
                                    <?php if ($isOut): ?>
                                        <span class="badge bg-danger text-white px-2 py-1 shadow-sm">Habis</span>
                                    <?php elseif ($isLow): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1 shadow-sm">Menipis</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aman</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end <?= $rowClass ?>">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-raw"
                                            data-id="<?= $item['id'] ?>"
                                            data-name="<?= htmlspecialchars($item['name']) ?>"
                                            data-sku="<?= htmlspecialchars($item['sku']) ?>"
                                            data-category-id="<?= $item['category_id'] ?>"
                                            data-unit-id="<?= $item['unit_id'] ?>"
                                            data-min-stock="<?= (float)$item['min_stock_qty'] ?>"
                                            title="Edit Info Bahan">
                                        <?= sim_icon('ti-edit') ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($items)): ?>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                <small class="text-muted">Menampilkan <strong class="text-dark"><?= count($items) ?></strong> bahan baku.</small>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<!-- Modal Edit Info Bahan Baku -->
<div class="modal fade" id="modalEditRaw" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= url('/inventory/raw/update') ?>" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editRawId">
            
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><?= sim_icon('ti-edit', 'me-2 text-primary') ?>Edit Info Bahan Baku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-medium text-dark mb-1">Nama Bahan Baku <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editRawName" class="form-control" required placeholder="Misal: Bubuk Coklat Premium">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium text-dark mb-1">Kode SKU</label>
                        <input type="text" name="sku" id="editRawSku" class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium text-dark mb-1">Stok Minimum Peringatan</label>
                        <input type="number" step="0.01" name="min_stock_qty" id="editRawMinStock" class="form-control form-control-sm" value="0">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-medium text-dark mb-1">Kategori</label>
                        <select name="category_id" id="editRawCategory" class="form-select form-select-sm">
                            <option value="">-- Tanpa Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-medium text-dark mb-1">Satuan Dasar</label>
                        <select name="unit_id" id="editRawUnit" class="form-select form-select-sm">
                            <option value="">-- Tanpa Satuan --</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['symbol']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis small mb-0 p-2">
                    <?= sim_icon('ti-alert-circle', 'me-1') ?> Stok & HPP tidak dapat diubah di sini (dihitung otomatis oleh modul Procurement / Belanja PO).
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm fw-medium shadow-sm"><?= sim_icon('ti-device-floppy', 'me-1') ?>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.btn-edit-raw');
    const modalEl = document.getElementById('modalEditRaw');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('editRawId').value = this.dataset.id || '';
                document.getElementById('editRawName').value = this.dataset.name || '';
                document.getElementById('editRawSku').value = this.dataset.sku || '';
                document.getElementById('editRawMinStock').value = this.dataset.minStock || '0';
                document.getElementById('editRawCategory').value = this.dataset.categoryId || '';
                document.getElementById('editRawUnit').value = this.dataset.unitId || '';
                modal.show();
            });
        });
    }
});
</script>

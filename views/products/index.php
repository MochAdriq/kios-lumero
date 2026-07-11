<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="sim-kicker"><?= sim_icon('ti-box') ?> Master Data</span>
            <h2 class="mb-1">Produk Jual</h2>
            <p class="mb-0 text-muted">Daftar produk final yang tampil di kasir (POS).</p>
        </div>
        <div>
            <a href="<?= url('/recipes') ?>" class="btn btn-outline-primary bg-white">
                <?= sim_icon('ti-flask', 'me-1') ?> Atur HPP via Resep
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- List Produk -->
    <div class="col-lg-12">
        <div class="sim-card shadow-sm border-0 mb-4 p-4 bg-white">
            <form method="get" class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><?= sim_icon('ti-search', 'text-muted') ?></span>
                    <input name="q" class="form-control border-start-0 ps-0 bg-light" placeholder="Cari produk, varian, SKU..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary px-4">Cari</button>
            </form>
        </div>

        <div class="sim-card shadow-sm border-0 p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">SKU / Produk</th>
                            <th>Kategori</th>
                            <th class="text-end">HPP</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Margin</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $it): 
                            $marginPercent = (float)$it['margin_percent'];
                            $marginClass = $marginPercent >= 40 ? 'success' : ($marginPercent >= 20 ? 'warning text-dark' : 'danger');
                        ?>
                        <tr>
                            <td class="ps-4">
                                <small class="text-muted d-block"><?= htmlspecialchars($it['sku']) ?></small>
                                <strong class="text-dark d-block"><?= htmlspecialchars($it['product_name']) ?></strong>
                                <span class="text-muted small"><?= htmlspecialchars($it['variant_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($it['category_name']) ?></span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted"><?= rupiah($it['hpp']) ?></span>
                            </td>
                            <td class="text-end">
                                <strong class="text-primary"><?= rupiah($it['selling_price']) ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $marginClass ?>">
                                    <?= number_format($marginPercent, 1) ?>%
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-light border shadow-sm rounded-circle me-1" 
                                    data-bs-toggle="modal" data-bs-target="#editModal<?= $it['id'] ?>" title="Edit">
                                    <?= sim_icon('ti-pencil') ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm rounded-circle" 
                                    data-bs-toggle="modal" data-bs-target="#deleteModal<?= $it['id'] ?>" title="Hapus">
                                    <?= sim_icon('ti-trash') ?>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="editModal<?= $it['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form class="modal-content" method="POST" action="<?= url('/products/update') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Edit Produk</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">SKU</label>
                                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($it['sku']) ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Produk Induk</label>
                                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($it['product_name']) ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Nama Varian (Tampil di Kasir)</label>
                                            <input type="text" class="form-control" name="variant_name" value="<?= htmlspecialchars($it['variant_name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Harga Jual (Rp)</label>
                                            <input type="number" class="form-control" name="selling_price" value="<?= (int)$it['selling_price'] ?>" required min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Modal Dasar / HPP Langsung (Rp) <span class="badge bg-light text-secondary border">HPP Cepat</span></label>
                                            <input type="number" class="form-control" name="cost_price" value="<?= (int)$it['hpp'] ?>" min="0">
                                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Isi modal langsung atau gunakan resep bahan baku untuk perhitungan otomatis.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal Hapus -->
                        <div class="modal fade" id="deleteModal<?= $it['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                <form class="modal-content text-center p-4" method="POST" action="<?= url('/products/delete') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                    <div class="mb-3 text-danger">
                                        <?= sim_icon('ti-alert-circle', 'fs-1') ?>
                                    </div>
                                    <h5 class="fw-bold mb-3">Hapus Produk?</h5>
                                    <p class="text-muted small mb-4">Anda yakin ingin menghapus <strong><?= htmlspecialchars($it['variant_name']) ?></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($items)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada produk.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <span class="sim-kicker text-primary"><?= sim_icon('ti-database') ?> Master Data</span>
        <h2 class="mb-1">Kategori & Variant</h2>
        <p class="mb-0 text-muted">Kelola kategori produk, kategori bahan baku, dan pantau seluruh variant menu yang aktif maupun nonaktif.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="sim-card shadow-sm border-0 h-100">
            <h5 class="fw-bold border-bottom pb-3 mb-3"><?= sim_icon('ti-tags', 'me-1 text-primary') ?> Kategori Produk</h5>
            
            <form method="post" action="<?= url('/categories/product') ?>" class="d-flex gap-2 mb-3">
                <?= csrf_field() ?>
                <input name="name" class="form-control form-control-sm" placeholder="Nama kategori produk..." required>
                <input name="sort_order" type="number" class="form-control form-control-sm" style="max-width: 90px;" placeholder="Urutan">
                <button type="submit" class="btn btn-primary btn-sm fw-medium px-3">Tambah</button>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        <?php if (empty($productCategories)): ?>
                            <tr><td class="text-center text-muted small py-3">Belum ada kategori produk.</td></tr>
                        <?php else: ?>
                            <?php foreach($productCategories as $c): ?>
                            <tr>
                                <td>
                                    <form method="post" action="<?= url('/categories/product/update') ?>" class="d-flex align-items-center gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input name="name" value="<?= htmlspecialchars($c['name']) ?>" class="form-control form-control-sm" required>
                                        <input name="sort_order" type="number" value="<?= htmlspecialchars($c['sort_order']) ?>" class="form-control form-control-sm" style="max-width: 70px;">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Simpan Perubahan"><?= sim_icon('ti-check') ?></button>
                                    </form>
                                </td>
                                <td class="text-end" style="width: 150px;">
                                    <span class="badge bg-light text-dark border px-2 py-1 me-2" title="Jumlah Produk">
                                        <?= (int)$c['product_count'] ?> produk
                                    </span>
                                    <form method="post" action="<?= url('/categories/product/delete') ?>" class="d-inline" data-confirm="Hapus kategori ini?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><?= sim_icon('ti-trash') ?></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="sim-card shadow-sm border-0 h-100">
            <h5 class="fw-bold border-bottom pb-3 mb-3"><?= sim_icon('ti-box', 'me-1 text-dark') ?> Kategori Bahan</h5>
            
            <form method="post" action="<?= url('/categories/raw') ?>" class="d-flex gap-2 mb-3">
                <?= csrf_field() ?>
                <input name="name" class="form-control form-control-sm" placeholder="Nama kategori bahan..." required>
                <input name="sort_order" type="number" class="form-control form-control-sm" style="max-width: 90px;" placeholder="Urutan">
                <button type="submit" class="btn btn-dark btn-sm fw-medium px-3">Tambah</button>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        <?php if (empty($rawCategories)): ?>
                            <tr><td class="text-center text-muted small py-3">Belum ada kategori bahan baku.</td></tr>
                        <?php else: ?>
                            <?php foreach($rawCategories as $c): ?>
                            <tr>
                                <td>
                                    <form method="post" action="<?= url('/categories/raw/update') ?>" class="d-flex align-items-center gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input name="name" value="<?= htmlspecialchars($c['name']) ?>" class="form-control form-control-sm" required>
                                        <input name="sort_order" type="number" value="<?= htmlspecialchars($c['sort_order']) ?>" class="form-control form-control-sm" style="max-width: 70px;">
                                        <button type="submit" class="btn btn-sm btn-outline-dark" title="Simpan Perubahan"><?= sim_icon('ti-check') ?></button>
                                    </form>
                                </td>
                                <td class="text-end" style="width: 150px;">
                                    <span class="badge bg-light text-dark border px-2 py-1 me-2" title="Jumlah Bahan">
                                        <?= (int)$c['material_count'] ?> bahan
                                    </span>
                                    <form method="post" action="<?= url('/categories/raw/delete') ?>" class="d-inline" data-confirm="Hapus kategori ini?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><?= sim_icon('ti-trash') ?></button>
                                    </form>
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

<div class="sim-card shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
        <h5 class="mb-0 fw-bold"><?= sim_icon('ti-list', 'me-1') ?> Daftar Variant Produk</h5>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kategori</th>
                    <th>Produk Induk</th>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th class="text-end">HPP Pusat</th>
                    <th class="text-end">Harga Jual</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($variants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada variant produk yang ditambahkan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($variants as $v): ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                <?= htmlspecialchars($v['category_name']) ?>
                            </span>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($v['product_name']) ?></td>
                        <td><strong class="text-dark"><?= htmlspecialchars($v['variant_name']) ?></strong></td>
                        <td><code class="text-secondary bg-light px-2 py-1 rounded"><?= htmlspecialchars($v['sku']) ?></code></td>
                        <td class="text-end text-muted"><?= rupiah($v['hpp']) ?></td>
                        <td class="text-end fw-medium text-dark"><?= rupiah($v['selling_price']) ?></td>
                        <td class="text-center">
                            <?php if ($v['is_active']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
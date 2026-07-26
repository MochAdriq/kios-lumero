<?php include __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-settings me-2 text-primary"></i>Pengaturan Hadiah Undian</h3>
                <p class="text-muted mb-0">Atur hadiah yang tampil di roda undian, stok ketersediaan, dan persentase kemenangannya.</p>
            </div>
            <button class="btn btn-primary rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddPrize">
                <i class="ti ti-plus me-2"></i> Tambah Hadiah Baru
            </button>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="ti ti-check me-2"></i><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="ti ti-alert-triangle me-2"></i><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<!-- Bagian 1: Manajemen Persentase Kemenangan (Strict 100%) -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
        <h5 class="fw-bold mb-0">Distribusi Peluang Menang (Persentase)</h5>
        <small class="text-muted">Atur persentase peluang menang untuk setiap hadiah. Total HARUS 100%.</small>
    </div>
    <div class="card-body p-4">
        <form action="<?= url('/loyalty/eventSettings/savePercentages') ?>" method="POST" id="formPercentages">
            <?= csrf_field() ?>
            <div class="table-responsive mb-3">
                <table class="table table-borderless align-middle mb-0">
                    <thead class="bg-light rounded-top">
                        <tr>
                            <th class="ps-3 rounded-start">Nama Hadiah</th>
                            <th width="150" class="text-center">Status</th>
                            <th width="200" class="rounded-end">Peluang (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $totalCurrent = 0; ?>
                        <?php foreach ($prizes as $p): ?>
                        <?php if ($p['is_active']) $totalCurrent += (float)$p['chance_percentage']; ?>
                        <tr class="border-bottom">
                            <td class="ps-3 fw-bold">
                                <?= htmlspecialchars($p['name']) ?>
                                <?php if ($p['is_default_fallback']): ?>
                                    <span class="badge bg-secondary ms-2" title="Hadiah Hiburan jika stok hadiah utama habis">Fallback</span>
                                <?php endif; ?>
                                <?php if ($p['stock'] <= 0): ?>
                                    <span class="badge bg-danger ms-2">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100" class="form-control chance-input text-end <?= !$p['is_active'] ? 'bg-light' : '' ?>" name="chances[<?= $p['id'] ?>]" value="<?= (float)$p['chance_percentage'] ?>" <?= !$p['is_active'] ? 'readonly title="Nonaktif"' : '' ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                <div class="flex-grow-1 me-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold">Total Peluang Aktif:</span>
                        <span class="fw-bold fs-5" id="totalDisplay"><?= $totalCurrent ?>%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="totalProgress" class="progress-bar <?= $totalCurrent == 100 ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= min($totalCurrent, 100) ?>%"></div>
                    </div>
                    <small id="totalWarning" class="text-danger fw-bold mt-1 <?= $totalCurrent == 100 ? 'd-none' : 'd-block' ?>">Total wajib tepat 100% untuk dapat disimpan.</small>
                </div>
                <button type="submit" class="btn btn-dark rounded-pill px-4" id="btnSavePercentages" <?= $totalCurrent == 100 ? '' : 'disabled' ?>>
                    Simpan Persentase
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bagian 2: Manajemen Data Hadiah -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
        <h5 class="fw-bold mb-0">Daftar Hadiah</h5>
    </div>
    <div class="card-body p-0 mt-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Gambar</th>
                        <th>Nama Hadiah</th>
                        <th>Stok Fisik</th>
                        <th>Fallback</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prizes)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada daftar hadiah.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($prizes as $p): ?>
                    <tr>
                        <td class="ps-4">
                            <?php if (!empty($p['image_url'])): ?>
                                <img src="<?= url('/' . $p['image_url']) ?>" class="rounded-3 object-fit-cover shadow-sm" style="width: 48px; height: 48px;">
                            <?php else: ?>
                                <div class="rounded-3 bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                                    <i class="ti ti-photo fs-4"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold">
                            <?= htmlspecialchars($p['name']) ?>
                            <?php if (!$p['is_active']): ?>
                                <small class="d-block text-danger">Nonaktif</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['stock'] <= 0): ?>
                                <span class="badge bg-danger">HABIS (0)</span>
                            <?php else: ?>
                                <span class="fw-bold fs-6"><?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['is_default_fallback']): ?>
                                <i class="ti ti-check text-success fs-4"></i>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary rounded-pill me-1" onclick="editPrize(<?= htmlspecialchars(json_encode($p)) ?>)">
                                <i class="ti ti-edit"></i> Edit
                            </button>
                            <form action="<?= url('/loyalty/eventSettings/deletePrize') ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus hadiah ini? Pastikan tidak ada data yang masih terhubung.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                                    <i class="ti ti-trash"></i>
                                </button>
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

<!-- Modal Add/Edit Prize -->
<div class="modal fade" id="modalAddPrize" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= url('/loyalty/eventSettings/savePrize') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="prize_id" value="0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Tambah Hadiah Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Hadiah</label>
                        <input type="text" name="name" id="prize_name" class="form-control rounded-3" required placeholder="Contoh: Tiket Umroh">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Stok Fisik</label>
                        <input type="number" name="stock" id="prize_stock" class="form-control rounded-3" required min="0" value="0">
                        <div class="form-text">Jika 0, otomatis tidak akan pernah menang, tapi tetap dipajang di roda.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Gambar Hadiah</label>
                        <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                        <div class="form-text">Biarkan kosong jika tidak ingin mengubah gambar.</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_default_fallback" id="prize_fallback" value="1">
                        <label class="form-check-label fw-bold" for="prize_fallback">Jadikan Hadiah Hiburan (Fallback)</label>
                        <div class="form-text small">Jika algoritma menunjuk ke hadiah yang stoknya habis, hadiah ini akan jadi pengganti otomatis.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="prize_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="prize_active">Aktif di Roda Undian</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Hadiah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editPrize(p) {
        document.getElementById('modalTitle').innerText = 'Edit Hadiah';
        document.getElementById('prize_id').value = p.id;
        document.getElementById('prize_name').value = p.name;
        document.getElementById('prize_stock').value = p.stock;
        document.getElementById('prize_fallback').checked = (p.is_default_fallback == 1);
        document.getElementById('prize_active').checked = (p.is_active == 1);
        new bootstrap.Modal(document.getElementById('modalAddPrize')).show();
    }

    // Logic 100% calculation
    const inputs = document.querySelectorAll('.chance-input:not([readonly])');
    const totalDisplay = document.getElementById('totalDisplay');
    const totalProgress = document.getElementById('totalProgress');
    const totalWarning = document.getElementById('totalWarning');
    const btnSave = document.getElementById('btnSavePercentages');

    function calculateTotal() {
        let total = 0;
        inputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        
        // Round to 2 decimal places to avoid floating point issues
        total = Math.round(total * 100) / 100;
        
        totalDisplay.innerText = total + '%';
        totalProgress.style.width = Math.min(total, 100) + '%';
        
        if (total === 100) {
            totalProgress.classList.remove('bg-danger');
            totalProgress.classList.add('bg-success');
            totalDisplay.classList.remove('text-danger');
            totalDisplay.classList.add('text-success');
            totalWarning.classList.add('d-none');
            btnSave.disabled = false;
        } else {
            totalProgress.classList.remove('bg-success');
            totalProgress.classList.add('bg-danger');
            totalDisplay.classList.remove('text-success');
            totalDisplay.classList.add('text-danger');
            totalWarning.classList.remove('d-none');
            btnSave.disabled = true;
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    // Initial check on load
    calculateTotal();
</script>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>

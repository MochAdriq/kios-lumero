<?php include __DIR__.'/../shared-flash.php'; ?>
<?php
$isHQ = Auth::isHQ();
?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <span class="sim-kicker"><?= sim_icon('ti-building') ?> Manajemen Cabang</span>
        <h2 class="mb-1">Kelola Cabang / Outlet</h2>
        <p class="mb-0 text-muted">Tambah, edit, dan kelola semua cabang beserta pengaturan operasionalnya.</p>
    </div>
    <div>
        <button class="btn btn-primary shadow-sm" onclick="openAddModal()">
            <?= sim_icon('ti-plus', 'me-1') ?> Tambah Cabang Baru
        </button>
    </div>
</div>

<div class="sim-card shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><?= sim_icon('ti-list') ?> Daftar Cabang</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Cabang</th>
                    <th>Slug / URL</th>
                    <th>Closing</th>
                    <th class="text-center">User</th>
                    <th class="text-center">Toko</th>
                    <th class="text-end">Omset Hari Ini</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($branches as $b): ?>
                <?php
                    $slug = $b['slug'] ?? '';
                    $branchUrl = branch_url($slug, '/dashboard');
                    $statusColor = $b['is_active'] ? 'success' : 'secondary';
                    $storeStatusBadge = match($b['store_status'] ?? null) {
                        'open' => '<span class="badge bg-success-subtle text-success border border-success-subtle">Buka</span>',
                        'closed' => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Tutup</span>',
                        default => '<span class="badge bg-warning-subtle text-dark border border-warning-subtle">-</span>',
                    };
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($b['is_hq']): ?>
                                <span class="badge bg-danger text-white rounded-circle p-1" title="Headquarters"><?= sim_icon('ti-star', 'm-0') ?></span>
                            <?php endif; ?>
                            <div>
                                <strong class="text-dark"><?= htmlspecialchars($b['name']) ?></strong>
                                <?php if ($b['code']): ?>
                                    <br><span class="text-muted small">Kode: <?= htmlspecialchars($b['code']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($slug === '' || $slug === null): ?>
                            <code class="text-dark bg-light px-2 py-1 rounded">/ <span class="text-muted">(root)</span></code>
                        <?php else: ?>
                            <code class="text-primary bg-primary-subtle px-2 py-1 rounded">/<?= htmlspecialchars($slug) ?></code>
                        <?php endif; ?>
                        <br>
                        <a href="<?= htmlspecialchars($branchUrl) ?>" class="text-decoration-none small mt-1 d-inline-block" target="_blank">
                            <?= sim_icon('ti-external-link') ?> Buka
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-dark px-2 py-1"><?= htmlspecialchars(substr($b['closing_hour'] ?? '21:00:00', 0, 5)) ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info-subtle text-info border border-info-subtle"><?= (int)($b['user_count'] ?? 0) ?></span>
                    </td>
                    <td class="text-center"><?= $storeStatusBadge ?></td>
                    <td class="text-end">
                        <strong class="text-dark"><?= rupiah($b['today_revenue'] ?? 0) ?></strong>
                        <br><small class="text-muted"><?= (int)($b['today_trx'] ?? 0) ?> trx</small>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusColor ?>"><?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <button class="btn btn-sm btn-light border" onclick="editBranch(<?= htmlspecialchars(json_encode($b)) ?>)" title="Edit">
                                <?= sim_icon('ti-pencil', 'text-primary') ?>
                            </button>
                            <?php if (!$b['is_hq']): ?>
                            <button class="btn btn-sm btn-light border" title="<?= $b['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                onclick="confirmToggle(<?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['name']) ?>', <?= $b['is_active'] ? 'true' : 'false' ?>)">
                                <?= sim_icon($b['is_active'] ? 'ti-player-pause' : 'ti-player-play', $b['is_active'] ? 'text-warning' : 'text-success') ?>
                            </button>
                            <button class="btn btn-sm btn-light border" title="Hapus"
                                onclick="confirmDelete(<?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['name']) ?>')">
                                <?= sim_icon('ti-trash', 'text-danger') ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($branches)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <?= sim_icon('ti-building-store', 'fs-1 text-light mb-2 d-block') ?>
                        Belum ada data outlet. Klik "Tambah Cabang Baru" untuk memulai.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalBranch" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBranchTitle"><?= sim_icon('ti-building-store') ?> Tambah Cabang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= url('/branches') ?>" id="formBranch">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="branchId" value="">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Nama Cabang <span class="text-danger">*</span></label>
                            <input name="name" id="branchName" class="form-control" required placeholder="Contoh: Kalibunder">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Kode Outlet <span class="text-danger">*</span></label>
                            <input name="code" id="branchCode" class="form-control" placeholder="KB" maxlength="10" style="text-transform:uppercase" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Slug URL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted" style="font-size:.85rem"><?= htmlspecialchars(rtrim(app_base_url(), '/')) ?>/</span>
                                <input name="slug" id="branchSlug" class="form-control" placeholder="kb" pattern="[a-z0-9][a-z0-9\-]*">
                            </div>
                            <small class="text-muted d-block mt-1">Kosongkan untuk outlet pusat. Hanya huruf kecil, angka, strip.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tipe Outlet</label>
                            <select name="type" id="branchType" class="form-select">
                                <option value="owned">Owned (Milik Sendiri)</option>
                                <option value="partnership">Partnership (Kemitraan)</option>
                                <option value="franchise">Franchise (Waralaba)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Alamat</label>
                            <textarea name="address" id="branchAddress" class="form-control" rows="2" placeholder="Alamat lengkap cabang"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Telepon</label>
                            <input name="phone" id="branchPhone" class="form-control" placeholder="08xx-xxxx-xxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Jam Closing (Hari Bisnis)</label>
                            <input type="time" name="closing_hour" id="branchClosingHour" class="form-control" value="21:00">
                            <small class="text-muted">Transaksi setelah jam ini masuk hari berikutnya.</small>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-4 p-3 bg-light rounded border">
                                <div class="form-check">
                                    <input type="hidden" name="is_hq" value="0">
                                    <input type="checkbox" name="is_hq" id="branchIsHQ" class="form-check-input" value="1">
                                    <label class="form-check-label" for="branchIsHQ">Tandai sebagai Pusat (HQ)</label>
                                </div>
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" id="branchIsActive" class="form-check-input" value="1" checked>
                                    <label class="form-check-label text-success fw-medium" for="branchIsActive">Cabang Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mt-3 bg-primary-subtle border-primary-subtle" id="adminSection">
                        <div class="form-check">
                            <input type="checkbox" name="create_admin" id="createAdminCheck" class="form-check-input" value="1">
                            <label class="form-check-label fw-bold text-primary" for="createAdminCheck">
                                <?= sim_icon('ti-user-plus', 'me-1') ?>Buat Akun Admin untuk Outlet Ini
                            </label>
                        </div>
                        <div id="adminFields" class="mt-3" style="display:none">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Nama Admin</label>
                                    <input name="admin_name" id="adminName" class="form-control form-control-sm" placeholder="Auto: Admin [Nama Outlet]">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Role</label>
                                    <select name="admin_role_id" id="adminRoleId" class="form-select form-select-sm">
                                        <option value="">Default: Administrator</option>
                                        <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Username <span class="text-danger">*</span></label>
                                    <input name="admin_username" id="adminUsername" class="form-control form-control-sm" placeholder="Auto: admin-[slug]">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Password</label>
                                    <input name="admin_password" id="adminPassword" type="text" class="form-control form-control-sm" placeholder="Default: admin123">
                                </div>
                            </div>
                            <div class="alert alert-info mt-2 mb-0 py-2 px-3 small border-0">
                                <?= sim_icon('ti-info-circle', 'me-1') ?> Jika username/password dikosongkan, otomatis: <strong>admin-[slug]</strong> / <strong>admin123</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitBranch">
                        <?= sim_icon('ti-device-floppy', 'me-1') ?> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalToggle" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><?= sim_icon('ti-alert-circle', 'me-1 text-warning') ?> Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4" id="toggleMessage"></div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <form method="post" action="<?= url('/branches/toggle') ?>" id="formToggle" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="toggleId">
                    <button class="btn btn-warning px-4" id="btnToggleConfirm">Ya, Lanjutkan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><?= sim_icon('ti-trash', 'me-1') ?> Hapus Outlet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4" id="deleteMessage"></div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <form method="post" action="<?= url('/branches/delete') ?>" id="formDelete" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="deleteId">
                    <button class="btn btn-danger px-4">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let branchModal;

document.addEventListener("DOMContentLoaded", function() {
    branchModal = new bootstrap.Modal(document.getElementById('modalBranch'));
});

// Toggle admin fields visibility
document.getElementById('createAdminCheck').addEventListener('change', function() {
    document.getElementById('adminFields').style.display = this.checked ? 'block' : 'none';
});

function openAddModal() {
    document.getElementById('formBranch').reset();
    document.getElementById('branchId').value = '';
    document.getElementById('branchIsActive').checked = true;
    
    document.getElementById('modalBranchTitle').innerHTML = '<?= sim_icon('ti-building-store') ?> Tambah Cabang Baru';
    document.getElementById('adminSection').style.display = 'block';
    document.getElementById('adminFields').style.display = 'none';
    document.getElementById('createAdminCheck').checked = false;
    
    branchModal.show();
}

function editBranch(b) {
    document.getElementById('branchId').value = b.id || '';
    document.getElementById('branchName').value = b.name || '';
    document.getElementById('branchSlug').value = b.slug || '';
    document.getElementById('branchCode').value = b.code || '';
    document.getElementById('branchType').value = b.type || 'owned';
    document.getElementById('branchAddress').value = b.address || '';
    document.getElementById('branchPhone').value = b.phone || '';
    document.getElementById('branchClosingHour').value = (b.closing_hour || '21:00:00').substring(0, 5);
    document.getElementById('branchIsHQ').checked = !!parseInt(b.is_hq);
    document.getElementById('branchIsActive').checked = !!parseInt(b.is_active);
    
    document.getElementById('modalBranchTitle').innerHTML = '<?= sim_icon('ti-pencil') ?> Edit Cabang';
    
    // Sembunyikan bagian create admin saat mode edit
    document.getElementById('adminSection').style.display = 'none';
    
    branchModal.show();
}

function confirmToggle(id, name, isActive) {
    document.getElementById('toggleId').value = id;
    const action = isActive ? 'menonaktifkan' : 'mengaktifkan';
    document.getElementById('toggleMessage').innerHTML = 
        '<p class="mb-0">Apakah Anda yakin ingin <strong>' + action + '</strong> outlet <br><strong class="fs-5">' + name + '</strong>?</p>';
    new bootstrap.Modal(document.getElementById('modalToggle')).show();
}

function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMessage').innerHTML = 
        '<p class="mb-2">Apakah Anda yakin ingin menghapus outlet <br><strong class="fs-5">' + name + '</strong>?</p><small class="text-muted">Outlet akan dinonaktifkan dan slug-nya dibebaskan.</small>';
    new bootstrap.Modal(document.getElementById('modalDelete')).show();
}
</script>
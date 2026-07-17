<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4">
    <div>
        <span class="sim-kicker"><?= sim_icon('ti-users') ?> RBAC & Payroll</span>
        <h2>User & HR</h2>
        <p>Kelola user, role, akses outlet, dan nominal gaji harian untuk auto-payroll saat toko dibuka.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Form Tambah/Edit User -->
    <div class="col-lg-4">
        <div class="sim-card">
            <h5 id="formTitle"><?= sim_icon('ti-user-plus') ?> Tambah User</h5>
            <form method="post" action="<?= url('/users') ?>" id="formUser">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="userId" value="">

                <label class="form-label mt-2">Nama <span class="text-danger">*</span></label>
                <input name="name" id="userName" class="form-control" required placeholder="Nama lengkap">

                <label class="form-label mt-2">Username <span class="text-danger">*</span></label>
                <input name="username" id="userUsername" class="form-control" required placeholder="Username untuk login">
                <small class="text-muted" id="usernameHint">Harus unik. Tidak bisa diubah setelah dibuat.</small>

                <label class="form-label mt-2">Email</label>
                <input name="email" id="userEmail" type="email" class="form-control" placeholder="email@contoh.com">

                <label class="form-label mt-2">Telepon</label>
                <input name="phone" id="userPhone" class="form-control" placeholder="08xx-xxxx-xxxx">

                <label class="form-label mt-2">Password</label>
                <input name="password" id="userPassword" type="password" class="form-control" placeholder="Default admin123 jika kosong">
                <small class="text-muted" id="passwordHint">Kosongkan saat edit jika tidak ingin mengubah password.</small>

                <label class="form-label mt-2">Role <span class="text-danger">*</span></label>
                <select name="role_id" id="userRoleId" class="form-select">
                    <?php foreach($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" data-code="<?= htmlspecialchars($r['code']) ?>">
                        <?= htmlspecialchars($r['name']) ?>
                    </option>
                    <?php endforeach ?>
                </select>

                <label class="form-label mt-2">Outlet / Cabang</label>
                <select name="outlet_id" id="userOutletId" class="form-select" <?= Auth::role() !== 'super_admin' ? 'readonly style="pointer-events:none;background:#e9ecef;"' : '' ?>>
                    <?php if (Auth::role() === 'super_admin'): ?>
                    <option value="">Global (semua cabang)</option>
                    <?php endif; ?>
                    <?php foreach($outlets as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= Auth::role() !== 'super_admin' ? 'selected' : '' ?>><?= htmlspecialchars($o['name']) ?></option>
                    <?php endforeach ?>
                </select>
                <?php if (Auth::role() === 'super_admin'): ?>
                <small class="text-muted">Wajib untuk role selain Super Admin.</small>
                <?php endif; ?>

                <label class="form-label mt-2">Gaji Harian</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="daily_salary" id="userDailySalary" class="form-control" value="0" min="0">
                </div>

                <div class="form-check mt-3" id="activeCheckWrapper">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="userIsActive" class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="userIsActive">Aktif</label>
                </div>

                <button class="btn btn-danger rounded-pill w-100 mt-3" id="btnSubmitUser">
                    <?= sim_icon('ti-device-floppy', 'me-1') ?>Simpan User
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill w-100 mt-2 d-none" id="btnCancelEdit" onclick="resetUserForm()">
                    Batal Edit
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar User -->
    <div class="col-lg-8">
        <div class="sim-card mb-4">
            <h5><?= sim_icon('ti-list') ?> Daftar User</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Outlet</th>
                            <th class="text-end">Gaji/Hari</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $u): ?>
                        <tr class="<?= $u['is_active'] ? '' : 'table-secondary' ?>">
                            <td>
                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($u['username']) ?></small>
                                <?php if (!empty($u['email'])): ?>
                                    <br><small class="text-muted"><?= sim_icon('ti-mail', '', 'width:12px;height:12px;') ?> <?= htmlspecialchars($u['email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $roleBadge = match($u['role_code'] ?? '') {
                                        'super_admin' => 'bg-danger',
                                        'administrator' => 'bg-primary',
                                        'cashier' => 'bg-info text-dark',
                                        default => 'bg-secondary',
                                    };
                                ?>
                                <span class="badge <?= $roleBadge ?>"><?= htmlspecialchars($u['role_name']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($u['outlet_name'] ?? 'Global') ?></td>
                            <td class="text-end"><?= rupiah($u['daily_salary']) ?></td>
                            <td>
                                <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary" title="Edit"
                                        onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                                        <?= sim_icon('ti-pencil') ?>
                                    </button>
                                    <button class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?>" title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                        onclick="confirmToggleUser(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', <?= $u['is_active'] ? 'true' : 'false' ?>)">
                                        <?= sim_icon($u['is_active'] ? 'ti-player-pause' : 'ti-player-play') ?>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" title="Reset Password"
                                        onclick="confirmResetPassword(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                                        <?= sim_icon('ti-key') ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach ?>
                        <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data user.</td></tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payroll Terakhir -->
        <div class="sim-card">
            <h5><?= sim_icon('ti-cash') ?> Payroll Terakhir</h5>
            <?php if (empty($payroll)): ?>
                <p class="text-muted">Belum ada data payroll.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Sumber</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payroll as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['business_date']) ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><small class="badge bg-light text-dark"><?= htmlspecialchars($p['source']) ?></small></td>
                            <td class="text-end fw-bold"><?= rupiah($p['amount']) ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Toggle User -->
<div class="modal fade" id="modalToggleUser" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= sim_icon('ti-alert-circle', 'me-1') ?> Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="toggleUserMessage"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form method="post" action="<?= url('/users/toggle') ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="toggleUserId">
                    <button class="btn btn-warning btn-sm">Ya, Lanjutkan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><?= sim_icon('ti-key', 'me-1') ?> Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= url('/users/reset-password') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="resetPasswordUserId">
                <div class="modal-body">
                    <p id="resetPasswordMessage"></p>
                    <label class="form-label">Password Baru</label>
                    <input type="text" name="new_password" class="form-control" value="admin123" placeholder="Default: admin123">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-warning btn-sm">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const userSaveIcon = <?= json_encode(sim_icon('ti-device-floppy', 'me-1')) ?>;
const userAddIcon = <?= json_encode(sim_icon('ti-user-plus')) ?>;
const userEditIcon = <?= json_encode(sim_icon('ti-user-edit')) ?>;

function editUser(u) {
    document.getElementById('userId').value = u.id || '';
    document.getElementById('userName').value = u.name || '';
    document.getElementById('userUsername').value = u.username || '';
    document.getElementById('userEmail').value = u.email || '';
    document.getElementById('userPhone').value = u.phone || '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRoleId').value = u.role_id || '';
    document.getElementById('userOutletId').value = u.outlet_id || '';
    document.getElementById('userDailySalary').value = u.daily_salary || 0;
    document.getElementById('userIsActive').checked = !!parseInt(u.is_active);

    // Disable username editing for existing users
    document.getElementById('userUsername').readOnly = true;
    document.getElementById('usernameHint').textContent = 'Username tidak bisa diubah.';
    document.getElementById('passwordHint').textContent = 'Kosongkan jika tidak ingin mengubah password.';

    document.getElementById('formTitle').innerHTML = userEditIcon + ' Edit User: ' + (u.name || '');
    document.getElementById('btnSubmitBranch') || null;
    document.getElementById('btnSubmitUser').innerHTML = userSaveIcon + 'Update User';
    document.getElementById('btnCancelEdit').classList.remove('d-none');
    document.getElementById('activeCheckWrapper').style.display = 'block';

    document.getElementById('formUser').scrollIntoView({ behavior: 'smooth' });
}

function resetUserForm() {
    document.getElementById('formUser').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userIsActive').checked = true;
    document.getElementById('userUsername').readOnly = false;
    document.getElementById('usernameHint').textContent = 'Harus unik. Tidak bisa diubah setelah dibuat.';
    document.getElementById('passwordHint').textContent = 'Default admin123 jika kosong.';
    document.getElementById('formTitle').innerHTML = userAddIcon + ' Tambah User';
    document.getElementById('btnSubmitUser').innerHTML = userSaveIcon + 'Simpan User';
    document.getElementById('btnCancelEdit').classList.add('d-none');
    document.getElementById('activeCheckWrapper').style.display = 'none';
}

function confirmToggleUser(id, name, isActive) {
    document.getElementById('toggleUserId').value = id;
    const action = isActive ? 'menonaktifkan' : 'mengaktifkan';
    document.getElementById('toggleUserMessage').innerHTML =
        'Apakah Anda yakin ingin <strong>' + action + '</strong> user <strong>' + name + '</strong>?';
    new bootstrap.Modal(document.getElementById('modalToggleUser')).show();
}

function confirmResetPassword(id, name) {
    document.getElementById('resetPasswordUserId').value = id;
    document.getElementById('resetPasswordMessage').innerHTML =
        'Reset password untuk user <strong>' + name + '</strong>:';
    new bootstrap.Modal(document.getElementById('modalResetPassword')).show();
}

// Hide active checkbox when creating new user (always active by default)
document.getElementById('activeCheckWrapper').style.display =
    document.getElementById('userId').value ? 'block' : 'none';
</script>

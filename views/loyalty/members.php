<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="url(#award-grad)" stroke="none" class="me-2">
                        <defs>
                            <linearGradient id="award-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#FFD700" />
                                <stop offset="100%" stop-color="#FF8C00" />
                            </linearGradient>
                        </defs>
                        <path d="M12 15l-4.224 2.816a1 1 0 0 1 -1.542 -.754l-.454 -4.896l-3.87 -3.344a1 1 0 0 1 .581 -1.74l4.98 -.384l1.916 -4.685a1 1 0 0 1 1.867 0l1.915 4.685l4.98 .384a1 1 0 0 1 .582 1.74l-3.87 3.344l-.454 4.896a1 1 0 0 1 -1.542 .754L12 15z" />
                    </svg>
                    Data Member & Loyalty Poin
                </h3>
                <p class="text-muted mb-0">Kelola pelanggan terdaftar, saldo poin, dan aturan perolehan poin.</p>
            </div>
            <a href="<?= url('/user/index.php', false) ?>" target="_blank" class="btn btn-outline-primary rounded-pill">
                <i class="ti ti-external-link me-1"></i> Buka Portal Member
            </a>
        </div>
    </div>
</div>

<!-- Navigasi Tab -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm d-inline-flex" id="memberTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4 fw-bold" id="tab-member-btn" data-bs-toggle="pill" data-bs-target="#tab-member" type="button" role="tab" aria-selected="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path></svg>
            Daftar Member
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 fw-bold" id="tab-pengaturan-btn" data-bs-toggle="pill" data-bs-target="#tab-pengaturan" type="button" role="tab" aria-selected="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94-1.543.826-3.31 2.37-2.37c1 .608 2.296.07 2.572-1.065z"></path><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path></svg>
            Pengaturan Poin
        </button>
    </li>
</ul>

<!-- Konten Tab -->
<div class="tab-content" id="memberTabContent">

    <!-- TAB 1: Daftar Member -->
    <div class="tab-pane fade show active" id="tab-member" role="tabpanel" aria-labelledby="tab-member-btn">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h6 class="fw-bold mb-0 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path></svg>
                    Daftar Member Terdaftar (<?= count($members) ?>)
                </h6>
                <div class="d-flex gap-2 align-items-center">
                    <!-- Tambahan UI: Search Bar -->
                    <div class="input-group input-group-sm shadow-none" style="width: 250px;">
                        <span class="input-group-text bg-light border-0 text-muted"><i class="ti ti-search"></i></span>
                        <input type="search" class="form-control bg-light border-0 shadow-none" placeholder="Cari nama atau no HP...">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalWaTemplates">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="me-1"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Kelola Template WA
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nama & No. HP</th>
                                <th>Total Poin</th>
                                <th>Total Belanja</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="mb-3 d-flex justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.953 14.993a4 4 0 1 0 -5.918 -5.922"></path><path d="M3 21v-2a4 4 0 0 1 4 -4h4c.915 0 1.758 .308 2.42 1"></path><path d="M16 3.13a4 4 0 0 1 3.236 7.828"></path><path d="M21 21v-2a4 4 0 0 0 -2.316 -3.738"></path><path d="M3 3l18 18"></path></svg>
                                    </div>
                                    Belum ada member terdaftar saat ini.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['name'] ?: 'Member Baru') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($m['phone']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#F59E0B" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><circle cx="12" cy="12" r="10"></circle><path d="M12 8l2.5 5.5l-5 0z"></path></svg>
                                        <?= number_format((int)$m['total_points'],0,',','.') ?> Poin
                                    </span>
                                </td>
                                <td class="text-muted fw-medium"><?= rupiah($m['total_spent'] ?? 0) ?></td>
                                <td>
                                    <span class="badge <?= ($m['status'] ?? 'active') === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary' ?> rounded-pill px-3">
                                        <?= strtoupper($m['status'] ?? 'ACTIVE') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-success rounded-pill btn-send-wa me-1" 
                                            data-name="<?= htmlspecialchars($m['name'] ?: 'Pelanggan Setia') ?>" 
                                            data-phone="<?= htmlspecialchars($m['phone']) ?>" 
                                            data-points="<?= number_format((int)$m['total_points'],0,',','.') ?>"
                                            data-kupon="<?= (int)($m['total_kupon'] ?? 0) ?>">
                                        <i class="ti ti-brand-whatsapp"></i> WA
                                    </button>
                                    <a href="<?= url('/loyalty/members/detail?id=' . $m['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Detail
                                    </a>
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

    <!-- TAB 2: Pengaturan Aturan Poin -->
    <div class="tab-pane fade" id="tab-pengaturan" role="tabpanel" aria-labelledby="tab-pengaturan-btn">
        <div class="row justify-content-center">
            <!-- Dibuat col-lg-7 agar form tidak terlalu lebar di layar besar -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0"><i class="ti ti-settings me-1"></i> Pengaturan Aturan Poin</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="<?= url('/loyalty/settings/update') ?>" method="POST">
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kelipatan Belanja (Rp)</label>
                                    <div class="input-group input-group-lg shadow-sm rounded-3">
                                        <span class="input-group-text bg-light border-0">Rp</span>
                                        <input type="number" name="earn_amount" class="form-control border-0 bg-light" value="<?= (int)($settings['earn_amount'] ?? 1000) ?>" required>
                                    </div>
                                    <small class="text-muted d-block mt-2" style="font-size:0.75rem;">Setiap kelipatan belanja nominal ini akan menghasilkan poin.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Poin Didapat per Kelipatan</label>
                                    <div class="input-group input-group-lg shadow-sm rounded-3">
                                        <input type="number" name="earn_point" class="form-control border-0 bg-light" value="<?= (int)($settings['earn_point'] ?? 1) ?>" required>
                                        <span class="input-group-text bg-light border-0">Poin</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nilai Potongan 1 Poin (Rp)</label>
                                    <div class="input-group input-group-lg shadow-sm rounded-3">
                                        <span class="input-group-text bg-light border-0">Rp</span>
                                        <input type="number" name="redeem_point_value" class="form-control border-0 bg-light" value="<?= (int)($settings['redeem_point_value'] ?? 100) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Minimal Poin untuk Redeem</label>
                                    <div class="input-group input-group-lg shadow-sm rounded-3">
                                        <input type="number" name="minimum_redeem_points" class="form-control border-0 bg-light" value="<?= (int)($settings['minimum_redeem_points'] ?? 10) ?>" required>
                                        <span class="input-group-text bg-light border-0">Poin</span>
                                    </div>
                                </div>
                            </div>
                            <hr class="border-light mb-4">
                            <div class="text-end">
                                <button type="submit" class="btn btn-dark px-5 py-2 rounded-pill fw-bold shadow-sm">Simpan Aturan Poin</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Kelola Template WA -->
<div class="modal fade" id="modalWaTemplates" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-success text-white rounded-top-4">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    Kelola Template Pesan WA
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-5 border-end">
                        <h6 class="fw-bold mb-3">Tambah Template Baru</h6>
                        <form action="<?= url('/loyalty/wa-templates/save') ?>" method="POST">
                            <input type="hidden" name="id" id="form-template-id" value="0">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Judul Template</label>
                                <input type="text" name="title" id="form-template-title" class="form-control bg-light border-0" placeholder="Misal: Promo Ulang Tahun" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Isi Pesan</label>
                                <textarea name="message" id="form-template-message" class="form-control bg-light border-0" rows="5" required placeholder="Halo {nama}, poin kamu ada {poin} loh!"></textarea>
                                <small class="text-muted" style="font-size:0.75rem;">Variabel tersedia: <code>{nama}</code>, <code>{poin}</code>, <code>{kupon}</code></small>
                            </div>
                            <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold">Simpan Template</button>
                            <button type="button" class="btn btn-light w-100 rounded-pill mt-2 d-none" id="btn-cancel-edit-template" onclick="resetTemplateForm()">Batal Edit</button>
                        </form>
                    </div>
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-3">Daftar Template</h6>
                        <?php if(empty($waTemplates)): ?>
                            <div class="alert alert-light text-center border-0 text-muted">Belum ada template pesan WA.</div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach($waTemplates as $tpl): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom border-light">
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($tpl['title']) ?></div>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width:250px;"><?= htmlspecialchars($tpl['message']) ?></small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary rounded-pill d-flex align-items-center justify-content-center" style="width:32px; height:32px;"
                                            onclick="editTemplate(<?= $tpl['id'] ?>, <?= htmlspecialchars(json_encode($tpl['title']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($tpl['message']), ENT_QUOTES, 'UTF-8') ?>)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4"></path><path d="M13.5 6.5l4 4"></path></svg>
                                        </button>
                                        <form action="<?= url('/loyalty/wa-templates/delete') ?>" method="POST" onsubmit="return confirm('Hapus template ini?');">
                                            <input type="hidden" name="id" value="<?= $tpl['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7l16 0"></path><path d="M10 11l0 6"></path><path d="M14 11l0 6"></path><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kirim Pesan WA -->
<div class="modal fade" id="modalSendWa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-success text-white rounded-top-4">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    Kirim Pesan ke Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4 text-center p-3 bg-light rounded-4">
                    <h5 class="fw-bold mb-1 text-dark" id="send-wa-name">Nama Member</h5>
                    <p class="text-muted mb-2" id="send-wa-phone">08xxxx</p>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm" id="send-wa-points">0 Poin</span>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih Template Pesan</label>
                    <select id="select-wa-template" class="form-select bg-light border-0 rounded-pill">
                        <option value="">-- Pilih Template --</option>
                        <option value="custom">Tulis Manual...</option>
                        <?php foreach($waTemplates as $tpl): ?>
                        <option value="<?= htmlspecialchars($tpl['message']) ?>"><?= htmlspecialchars($tpl['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Isi Pesan (Edit Teks)</label>
                    <textarea id="wa-message-text" class="form-control bg-light border-0" rows="5" placeholder="Ketik pesan disini..."></textarea>
                </div>
                
                <button type="button" id="btn-do-send-wa" class="btn btn-success w-100 rounded-pill fw-bold py-2 shadow-sm d-flex justify-content-center align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M10 14l11 -11"></path><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5"></path></svg>
                    Buka WhatsApp & Kirim
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentWaPhone = '';
let currentWaName = '';
let currentWaPoints = '';
let currentWaKupon = '';

document.querySelectorAll('.btn-send-wa').forEach(btn => {
    btn.addEventListener('click', function() {
        currentWaName = this.getAttribute('data-name');
        currentWaPhone = this.getAttribute('data-phone');
        currentWaPoints = this.getAttribute('data-points');
        currentWaKupon = this.getAttribute('data-kupon');
        
        document.getElementById('send-wa-name').textContent = currentWaName;
        document.getElementById('send-wa-phone').textContent = currentWaPhone;
        document.getElementById('send-wa-points').textContent = currentWaPoints + ' Poin | ' + currentWaKupon + ' Kupon';
        
        document.getElementById('select-wa-template').value = '';
        document.getElementById('wa-message-text').value = '';
        
        new bootstrap.Modal(document.getElementById('modalSendWa')).show();
    });
});

document.getElementById('select-wa-template').addEventListener('change', function() {
    let val = this.value;
    if (val && val !== 'custom') {
        let msg = val.replace(/{nama}/g, currentWaName).replace(/{poin}/g, currentWaPoints).replace(/{kupon}/g, currentWaKupon);
        document.getElementById('wa-message-text').value = msg;
    } else {
        document.getElementById('wa-message-text').value = '';
    }
});

document.getElementById('btn-do-send-wa').addEventListener('click', function() {
    let msg = document.getElementById('wa-message-text').value.trim();
    if (!msg) {
        alert('Isi pesan tidak boleh kosong!');
        return;
    }
    
    // Format phone to 628...
    let phone = currentWaPhone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    
    let url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
    window.open(url, '_blank');
    bootstrap.Modal.getInstance(document.getElementById('modalSendWa')).hide();
});

function editTemplate(id, title, message) {
    document.getElementById('form-template-id').value = id;
    document.getElementById('form-template-title').value = title;
    document.getElementById('form-template-message').value = message;
    document.getElementById('btn-cancel-edit-template').classList.remove('d-none');
}

function resetTemplateForm() {
    document.getElementById('form-template-id').value = 0;
    document.getElementById('form-template-title').value = '';
    document.getElementById('form-template-message').value = '';
    document.getElementById('btn-cancel-edit-template').classList.add('d-none');
}
</script>
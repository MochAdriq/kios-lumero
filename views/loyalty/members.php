<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-award me-2 text-warning"></i>Data Member & Loyalty Poin</h3>
                <p class="text-muted mb-0">Kelola pelanggan terdaftar, saldo poin, dan aturan perolehan poin.</p>
            </div>
            <a href="<?= url('/user/index.php', false) ?>" target="_blank" class="btn btn-outline-primary rounded-pill">
                <i class="ti ti-external-link me-1"></i> Buka Portal Member
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Aturan Poin -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="ti ti-settings me-1"></i> Pengaturan Aturan Poin</h6>
            </div>
            <div class="card-body">
                <form action="<?= url('/loyalty/settings/update') ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kelipatan Belanja (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="earn_amount" class="form-control" value="<?= (int)($settings['earn_amount'] ?? 1000) ?>" required>
                        </div>
                        <small class="text-muted" style="font-size:0.75rem;">Setiap kelipatan belanja nominal ini akan menghasilkan poin.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Poin Didapat per Kelipatan</label>
                        <div class="input-group">
                            <input type="number" name="earn_point" class="form-control" value="<?= (int)($settings['earn_point'] ?? 1) ?>" required>
                            <span class="input-group-text">Poin</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nilai Potongan 1 Poin (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="redeem_point_value" class="form-control" value="<?= (int)($settings['redeem_point_value'] ?? 100) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Minimal Poin untuk Redeem</label>
                        <div class="input-group">
                            <input type="number" name="minimum_redeem_points" class="form-control" value="<?= (int)($settings['minimum_redeem_points'] ?? 10) ?>" required>
                            <span class="input-group-text">Poin</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Simpan Aturan Poin</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistik Singkat -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-users me-1"></i> Daftar Member Terdaftar (<?= count($members) ?>)</h6>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-bs-toggle="modal" data-bs-target="#modalWaTemplates">
                    <i class="ti ti-brand-whatsapp me-1"></i> Kelola Template WA
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Nama & No. HP</th>
                                <th>Total Poin</th>
                                <th>Total Belanja</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada member terdaftar.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold"><?= htmlspecialchars($m['name'] ?: 'Member Baru') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($m['phone']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                                        <?= number_format((int)$m['total_points'],0,',','.') ?> Poin
                                    </span>
                                </td>
                                <td><?= rupiah($m['total_spent'] ?? 0) ?></td>
                                <td>
                                    <span class="badge <?= ($m['status'] ?? 'active') === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary' ?> rounded-pill">
                                        <?= strtoupper($m['status'] ?? 'ACTIVE') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-success rounded-pill btn-send-wa me-1" 
                                            data-name="<?= htmlspecialchars($m['name'] ?: 'Pelanggan Setia') ?>" 
                                            data-phone="<?= htmlspecialchars($m['phone']) ?>" 
                                            data-points="<?= number_format((int)$m['total_points'],0,',','.') ?>">
                                        <i class="ti ti-brand-whatsapp"></i> WA
                                    </button>
                                    <a href="<?= url('/loyalty/members/detail?id=' . $m['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill">
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
</div>

<!-- Modal Kelola Template WA -->
<div class="modal fade" id="modalWaTemplates" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-success text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="ti ti-brand-whatsapp me-2"></i>Kelola Template Pesan WA</h5>
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
                                <input type="text" name="title" id="form-template-title" class="form-control" placeholder="Misal: Promo Ulang Tahun" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Isi Pesan</label>
                                <textarea name="message" id="form-template-message" class="form-control" rows="5" required placeholder="Halo {nama}, poin kamu ada {poin} loh!"></textarea>
                                <small class="text-muted" style="font-size:0.75rem;">Variabel tersedia: <code>{nama}</code>, <code>{poin}</code></small>
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
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($tpl['title']) ?></div>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width:250px;"><?= htmlspecialchars($tpl['message']) ?></small>
                                    </div>
                                    <div class="d-flex">
                                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                                            onclick="editTemplate(<?= $tpl['id'] ?>, '<?= htmlspecialchars(addslashes($tpl['title'])) ?>', '<?= htmlspecialchars(addslashes($tpl['message'])) ?>')">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <form action="<?= url('/loyalty/wa-templates/delete') ?>" method="POST" onsubmit="return confirm('Hapus template ini?');">
                                            <input type="hidden" name="id" value="<?= $tpl['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="ti ti-trash"></i></button>
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
                <h5 class="modal-title fw-bold"><i class="ti ti-brand-whatsapp me-2"></i>Kirim Pesan ke Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 text-center">
                    <h5 class="fw-bold mb-0" id="send-wa-name">Nama Member</h5>
                    <p class="text-muted mb-0" id="send-wa-phone">08xxxx</p>
                    <span class="badge bg-warning text-dark rounded-pill mt-1" id="send-wa-points">0 Poin</span>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih Template Pesan</label>
                    <select id="select-wa-template" class="form-select rounded-pill">
                        <option value="">-- Pilih Template --</option>
                        <option value="custom">Tulis Manual...</option>
                        <?php foreach($waTemplates as $tpl): ?>
                        <option value="<?= htmlspecialchars($tpl['message']) ?>"><?= htmlspecialchars($tpl['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Isi Pesan (Edit Teks)</label>
                    <textarea id="wa-message-text" class="form-control" rows="5" placeholder="Ketik pesan disini..."></textarea>
                </div>
                
                <button type="button" id="btn-do-send-wa" class="btn btn-success w-100 rounded-pill fw-bold py-2">
                    <i class="ti ti-send me-1"></i> Buka WhatsApp & Kirim
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentWaPhone = '';
let currentWaName = '';
let currentWaPoints = '';

document.querySelectorAll('.btn-send-wa').forEach(btn => {
    btn.addEventListener('click', function() {
        currentWaName = this.getAttribute('data-name');
        currentWaPhone = this.getAttribute('data-phone');
        currentWaPoints = this.getAttribute('data-points');
        
        document.getElementById('send-wa-name').textContent = currentWaName;
        document.getElementById('send-wa-phone').textContent = currentWaPhone;
        document.getElementById('send-wa-points').textContent = currentWaPoints + ' Poin';
        
        document.getElementById('select-wa-template').value = '';
        document.getElementById('wa-message-text').value = '';
        
        new bootstrap.Modal(document.getElementById('modalSendWa')).show();
    });
});

document.getElementById('select-wa-template').addEventListener('change', function() {
    let val = this.value;
    if (val && val !== 'custom') {
        let msg = val.replace(/{nama}/g, currentWaName).replace(/{poin}/g, currentWaPoints);
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

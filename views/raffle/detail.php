<div class="mb-3">
    <a href="<?= url('/raffle') ?>" class="text-decoration-none">&larr; Kembali ke Daftar Undian</a>
</div>

<div class="header-section">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($batch['name']) ?></h1>
        <p class="text-muted">Periode: <?= date('d M Y', strtotime($batch['start_date'])) ?> s/d <?= date('d M Y', strtotime($batch['end_date'])) ?></p>
    </div>
    <div>
        <?php if ($batch['status'] === 'completed'): ?>
            <span class="badge bg-secondary fs-6">Selesai (Siap Undi)</span>
        <?php elseif ($batch['status'] === 'active'): ?>
            <span class="badge bg-success fs-6">Sedang Berlangsung</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark fs-6">Draft</span>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Total Tiket Terjual</h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($stats['total_tickets']) ?> <small class="fs-5 fw-normal">Tiket</small></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Total Peserta</h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($stats['total_participants']) ?> <small class="fs-5 fw-normal">Member</small></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Poin Terbakar</h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($stats['total_tickets'] * 10) ?> <small class="fs-5 fw-normal">Poin</small></h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Katalog Hadiah</h5>
        <button class="btn btn-sm btn-primary" onclick="showAddPrizeModal()">+ Tambah Hadiah</button>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th width="80">Gambar</th>
                    <th>Nama Hadiah</th>
                    <th>Status Pemenang</th>
                    <th width="200" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prizes as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['image_url']): ?>
                            <img src="<?= url('/public/assets/' . $p['image_url']) ?>" alt="img" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center text-muted img-thumbnail" style="width: 60px; height: 60px; font-size: 10px;">No Img</div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-bold fs-5"><?= htmlspecialchars($p['name']) ?></td>
                    <td>
                        <?php if ($p['winner_ticket_id']): ?>
                            <div class="alert alert-success py-2 mb-0 d-inline-block">
                                <strong>Pemenang:</strong> <?= htmlspecialchars($p['winner_name']) ?> <br>
                                <small>No HP: <?= htmlspecialchars($p['winner_phone'] ?: '-') ?> | Tiket: <?= htmlspecialchars($p['ticket_code']) ?></small>
                            </div>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Belum diundi</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if (!$p['winner_ticket_id']): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick='editPrize(<?= json_encode($p) ?>)'>Edit</button>
                            <form method="POST" action="<?= url('/raffle/delete-prize') ?>" class="d-inline" onsubmit="return confirm('Hapus hadiah ini?')">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                            
                            <?php if ($batch['status'] === 'completed'): ?>
                            <form method="POST" action="<?= url('/raffle/draw-winner') ?>" class="mt-2" onsubmit="return confirm('Apakah Anda yakin ingin Mengocok Undian untuk hadiah ini? Pastikan event live/perekaman sudah siap!')">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="prize_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                                <button type="submit" class="btn btn-warning fw-bold text-dark w-100">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                    Kocok Undian
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($prizes)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Belum ada hadiah. Silakan tambah hadiah untuk periode ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Prize -->
<div class="modal fade" id="modalPrize" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/raffle/save-prize') ?>" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPrizeTitle">Tambah Hadiah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="prize_id">
                <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Nama Hadiah</label>
                    <input type="text" name="name" id="prize_name" class="form-control" required placeholder="Contoh: Tablet S9">
                </div>
                <div class="mb-3">
                    <label class="form-label">Gambar Hadiah (Opsional)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Hadiah</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddPrizeModal() {
    document.getElementById('prize_id').value = '';
    document.getElementById('prize_name').value = '';
    document.getElementById('modalPrizeTitle').innerText = 'Tambah Hadiah Baru';
    new bootstrap.Modal(document.getElementById('modalPrize')).show();
}

function editPrize(p) {
    document.getElementById('prize_id').value = p.id;
    document.getElementById('prize_name').value = p.name;
    document.getElementById('modalPrizeTitle').innerText = 'Edit Hadiah';
    new bootstrap.Modal(document.getElementById('modalPrize')).show();
}
</script>

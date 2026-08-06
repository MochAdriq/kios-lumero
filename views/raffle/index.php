<div class="header-section">
    <div>
        <h1 class="page-title">Manajemen Undian (Raffle)</h1>
        <p class="text-muted">Kelola batch/periode undian dan katalog hadiah.</p>
    </div>
    <button class="btn btn-primary" onclick="showAddBatchModal()">+ Buat Batch Baru</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Periode</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($b['name']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($b['start_date'])) ?></td>
                    <td><?= date('d M Y H:i', strtotime($b['end_date'])) ?></td>
                    <td>
                        <?php if ($b['status'] === 'active'): ?>
                            <span class="badge bg-success">Berlangsung</span>
                        <?php elseif ($b['status'] === 'completed'): ?>
                            <span class="badge bg-secondary">Selesai</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= url('/raffle/' . $b['id']) ?>" class="btn btn-sm btn-info text-white">Detail & Hadiah</a>
                        <button class="btn btn-sm btn-outline-secondary" onclick='editBatch(<?= json_encode($b) ?>)'>Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($batches)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada periode undian.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Batch -->
<div class="modal fade" id="modalBatch" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/raffle/save-batch') ?>" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBatchTitle">Buat Batch Undian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="batch_id">
                <div class="mb-3">
                    <label class="form-label">Nama Event/Periode</label>
                    <input type="text" name="name" id="batch_name" class="form-control" required placeholder="Contoh: Undian Kemerdekaan">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="datetime-local" name="start_date" id="batch_start" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="datetime-local" name="end_date" id="batch_end" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="batch_status" class="form-select">
                        <option value="draft">Draft (Belum Tampil)</option>
                        <option value="active">Active (Sedang Berlangsung)</option>
                        <option value="completed">Completed (Selesai & Siap Undi)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Batch</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddBatchModal() {
    document.getElementById('batch_id').value = '';
    document.getElementById('batch_name').value = '';
    document.getElementById('batch_start').value = '';
    document.getElementById('batch_end').value = '';
    document.getElementById('batch_status').value = 'draft';
    document.getElementById('modalBatchTitle').innerText = 'Buat Batch Baru';
    new bootstrap.Modal(document.getElementById('modalBatch')).show();
}

function editBatch(b) {
    document.getElementById('batch_id').value = b.id;
    document.getElementById('batch_name').value = b.name;
    document.getElementById('batch_start').value = b.start_date.replace(' ', 'T').substring(0, 16);
    document.getElementById('batch_end').value = b.end_date.replace(' ', 'T').substring(0, 16);
    document.getElementById('batch_status').value = b.status;
    document.getElementById('modalBatchTitle').innerText = 'Edit Batch';
    new bootstrap.Modal(document.getElementById('modalBatch')).show();
}
</script>

<?php include __DIR__.'/../shared-flash.php'; ?>
<div class="sim-hero mb-4">
    <div>
        <span class="sim-kicker">Edit Expense</span>
        <h2>Edit Pengeluaran Operasional</h2>
        <p>Perbarui rincian pengeluaran operasional.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6 mx-auto">
        <div class="sim-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="m-0">Form Edit</h5>
                <a href="<?= url('/expenses') ?>" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
            
            <form method="post" action="<?= url('/expenses/update/' . $item['id']) ?>">
                <?= csrf_field() ?>
                
                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="business_date" value="<?= htmlspecialchars($item['business_date']) ?>" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $item['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> — <?= htmlspecialchars($c['type']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nominal</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">Rp</span>
                        <input type="number" name="amount" value="<?= (float)$item['amount'] ?>" class="form-control" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Metode</label>
                    <select name="payment_method" class="form-select">
                        <option value="cash" <?= $item['payment_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="bank_transfer" <?= $item['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Transfer</option>
                        <option value="qris" <?= $item['payment_method'] === 'qris' ? 'selected' : '' ?>>QRIS</option>
                        <option value="ewallet" <?= $item['payment_method'] === 'ewallet' ? 'selected' : '' ?>>E-Wallet</option>
                        <option value="other" <?= $item['payment_method'] === 'other' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Keterangan</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-bold">Update Pengeluaran</button>
            </form>
        </div>
    </div>
</div>

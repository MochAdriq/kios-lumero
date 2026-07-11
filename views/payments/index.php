<?php $flashSuccess = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']); $flashError = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']); ?>
<?php if ($flashSuccess): ?><div class="alert alert-success rounded-4 border-0 shadow-sm"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger rounded-4 border-0 shadow-sm"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>
<div class="soft-card">
    <h5 class="card-title-sim mb-1">Verifikasi Payment</h5>
    <p class="text-muted">Daftar payment yang masih pending/waiting verification.</p>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>No Order</th><th>Metode</th><th>Status</th><th class="text-end">Amount</th><th></th></tr></thead>
        <tbody><?php foreach ($payments as $p): ?><tr>
            <td><strong><?= htmlspecialchars($p['order_number']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($p['created_at']) ?></small></td>
            <td><?= htmlspecialchars(strtoupper($p['payment_method'])) ?></td>
            <td><span class="badge-soft"><?= htmlspecialchars($p['payment_real_status']) ?></span></td>
            <td class="text-end fw-bold"><?= rupiah($p['amount']) ?></td>
            <td class="text-end"><form method="post" action="<?= url('/payments/verify') ?>"><?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>"><button class="btn btn-sim btn-sm">Verifikasi</button></form></td>
        </tr><?php endforeach; if (!$payments): ?><tr><td colspan="5" class="text-center text-muted py-4">Tidak ada payment pending.</td></tr><?php endif; ?></tbody>
    </table></div>
</div>

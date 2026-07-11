<?php $order = $receipt['order']; $items = $receipt['items']; ?>
<div class="soft-card receipt-page">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h3 class="fw-black mb-1">Struk Transaksi</h3>
            <div class="text-muted"><?= htmlspecialchars($order['order_number']) ?> · <?= htmlspecialchars($order['created_at']) ?></div>
        </div>
        <div class="d-flex gap-2">
            <?php if (class_exists('MidtransService') && MidtransService::getClientKey() !== ''): ?>
                <button type="button" id="btnPayMidtrans" class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-1">
                    <?= sim_icon('ti-credit-card') ?> Bayar via Midtrans
                </button>
            <?php endif; ?>
            <a href="<?= url('/pos') ?>" class="btn btn-sim">Transaksi Baru</a>
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill">Print</button>
        </div>
    </div>
    <div class="receipt-box" id="receiptBox">
        <div class="text-center mb-2"><strong><?= htmlspecialchars($order['outlet_name'] ?? 'SIM Resto') ?></strong><br><small><?= htmlspecialchars($order['outlet_address'] ?? '') ?></small></div>
        <hr>
        <div>No: <strong><?= htmlspecialchars($order['order_number']) ?></strong></div>
        <div>Tanggal: <?= htmlspecialchars($order['created_at']) ?></div>
        <div>Payment: <?= htmlspecialchars(strtoupper($order['payment_method'] ?? '-')) ?></div>
        <hr>
        <?php foreach ($items as $it): ?>
        <div class="receipt-line"><span><?= htmlspecialchars($it['variant_name_snapshot'] ?: $it['product_name_snapshot']) ?><br><small><?= number_format($it['qty'],0,',','.') ?> x <?= rupiah($it['selling_price']) ?></small></span><b><?= rupiah($it['subtotal']) ?></b></div>
        <?php endforeach; ?>
        <hr>
        <div class="receipt-line"><span>Subtotal</span><b><?= rupiah($order['subtotal']) ?></b></div>
        <div class="receipt-line"><span>Diskon</span><b><?= rupiah($order['discount_amount']) ?></b></div>
        <div class="receipt-line"><span>Pajak</span><b><?= rupiah($order['tax_amount']) ?></b></div>
        <div class="receipt-line grand"><span>Total</span><b><?= rupiah($order['grand_total']) ?></b></div>
        <div class="text-center mt-3"><small>Terima kasih. Selamat menikmati.</small></div>
    </div>
</div>

<?php if (class_exists('MidtransService') && MidtransService::getClientKey() !== ''): ?>
<script src="<?= MidtransService::snapJsUrl() ?>" data-client-key="<?= htmlspecialchars(MidtransService::getClientKey()) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnPay = document.getElementById('btnPayMidtrans');
    if (!btnPay) return;

    btnPay.addEventListener('click', function() {
        btnPay.disabled = true;
        btnPay.innerHTML = 'Memproses Snap Token...';

        const formData = new FormData();
        formData.append('order_id', '<?= (int)$order['id'] ?>');
        formData.append('csrf_token', '<?= csrf_token() ?>');

        fetch('<?= url('/api/midtrans/token') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btnPay.disabled = false;
            btnPay.innerHTML = '<?= sim_icon('ti-credit-card') ?> Bayar via Midtrans';

            if (data.success && data.token) {
                window.snap.pay(data.token, {
                    onSuccess: function(result){
                        alert('Pembayaran berhasil!');
                        location.reload();
                    },
                    onPending: function(result){
                        alert('Menunggu pembayaran!');
                        location.reload();
                    },
                    onError: function(result){
                        alert('Pembayaran gagal!');
                    },
                    onClose: function(){
                        console.log('Popup ditutup sebelum pembayaran selesai');
                    }
                });
            } else {
                alert('Gagal memuat token: ' + (data.message || 'Error tidak diketahui'));
            }
        })
        .catch(err => {
            btnPay.disabled = false;
            btnPay.innerHTML = '<?= sim_icon('ti-credit-card') ?> Bayar via Midtrans';
            alert('Terjadi kesalahan koneksi ke server.');
        });
    });
});
</script>
<?php endif; ?>

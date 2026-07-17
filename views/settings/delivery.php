<?php include __DIR__.'/../shared-flash.php'; ?>
<div class="sim-hero mb-4">
    <div>
        <span class="sim-kicker">Delivery Configuration</span>
        <h2>Pengaturan Delivery Order</h2>
        <p>Atur radius pengantaran, ongkos kirim, dan titik koordinat outlet untuk fitur delivery maps.</p>
    </div>
</div>

<form method="post">
    <?= csrf_field() ?>
    <div class="row g-4">

        <!-- LEFT COLUMN: Settings -->
        <div class="col-lg-6">
            <div class="sim-card mb-4">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-truck-delivery', 'me-2 text-primary') ?>Aktifkan Fitur Delivery</h5>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="delivery_enabled" value="1" id="deliveryEnabledSwitch"
                        <?= ($settings['delivery_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-medium" for="deliveryEnabledSwitch">
                        Delivery Order Aktif
                    </label>
                </div>
                <small class="text-muted">Jika diaktifkan, pelanggan dapat memilih opsi "Delivery" saat checkout di halaman Online Order.</small>
            </div>

            <div class="sim-card mb-4">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-map-pin-filled', 'me-2 text-danger') ?>Radius & Jarak</h5>
                <label class="form-label mt-2 fw-medium">Radius Delivery Maksimal (km)</label>
                <div class="input-group mb-3">
                    <input type="number" step="0.1" min="0.5" max="50" name="delivery_max_radius_km"
                        value="<?= htmlspecialchars($settings['delivery_max_radius_km'] ?? '5') ?>"
                        class="form-control" placeholder="5">
                    <span class="input-group-text">km</span>
                </div>
                <small class="text-muted mt-1 d-block mb-3">Pelanggan yang berada di luar radius ini tidak bisa melakukan delivery.</small>

                <label class="form-label fw-medium">Batas Jarak Gratis Ongkir (km)</label>
                <div class="input-group mb-1">
                    <input type="number" step="0.1" min="0" name="delivery_free_km_limit"
                        value="<?= htmlspecialchars($settings['delivery_free_km_limit'] ?? '2') ?>"
                        class="form-control" placeholder="2">
                    <span class="input-group-text">km</span>
                </div>
                <small class="text-muted d-block">Pelanggan di bawah jarak ini gratis ongkir. Jika melebihi batas, tarif per-KM dihitung dari selisih kelebihan jaraknya saja.</small>
            </div>

            <div class="sim-card mb-4">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-currency-dollar', 'me-2 text-success') ?>Model Tarif Ongkir</h5>
                <label class="form-label fw-medium">Tipe Kalkulasi</label>
                <select name="delivery_fee_model" class="form-select mb-3" id="feeModelSelect">
                    <option value="per_km" <?= ($settings['delivery_fee_model'] ?? 'per_km') === 'per_km' ? 'selected' : '' ?>>Per Kilometer (Rp/km)</option>
                    <option value="flat" <?= ($settings['delivery_fee_model'] ?? '') === 'flat' ? 'selected' : '' ?>>Tarif Tetap (Flat)</option>
                </select>

                <div id="perKmFields" style="<?= ($settings['delivery_fee_model'] ?? 'per_km') === 'flat' ? 'display:none' : '' ?>">
                    <label class="form-label fw-medium">Tarif Per-KM</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">Rp</span>
                        <input type="number" min="0" name="delivery_per_km_fee"
                            value="<?= htmlspecialchars($settings['delivery_per_km_fee'] ?? '3000') ?>"
                            class="form-control" placeholder="3000">
                        <span class="input-group-text">/ km</span>
                    </div>
                </div>

                <div id="flatFields" style="<?= ($settings['delivery_fee_model'] ?? 'per_km') !== 'flat' ? 'display:none' : '' ?>">
                    <label class="form-label fw-medium">Tarif Flat (Tetap)</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">Rp</span>
                        <input type="number" min="0" name="delivery_flat_fee"
                            value="<?= htmlspecialchars($settings['delivery_flat_fee'] ?? '5000') ?>"
                            class="form-control" placeholder="5000">
                    </div>
                </div>

                <label class="form-label fw-medium">Minimum Ongkir</label>
                <div class="input-group mb-3">
                    <span class="input-group-text">Rp</span>
                    <input type="number" min="0" name="delivery_min_fee"
                        value="<?= htmlspecialchars($settings['delivery_min_fee'] ?? '5000') ?>"
                        class="form-control" placeholder="5000">
                </div>

                <label class="form-label fw-medium">Gratis Ongkir Jika Belanja Di Atas</label>
                <div class="input-group mb-2">
                    <span class="input-group-text">Rp</span>
                    <input type="number" min="0" name="delivery_free_above"
                        value="<?= htmlspecialchars($settings['delivery_free_above'] ?? '0') ?>"
                        class="form-control" placeholder="0">
                </div>
                <small class="text-muted">Isi <code>0</code> untuk menonaktifkan fitur gratis ongkir.</small>
            </div>
        </div>

        <!-- RIGHT COLUMN: Map -->
        <div class="col-lg-6">
            <div class="sim-card mb-4">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-map-2', 'me-2 text-warning') ?>Lokasi Outlet (Pin di Peta)</h5>
                <p class="text-muted small mb-3">Klik atau geser pin di peta untuk menentukan titik koordinat outlet. Koordinat ini digunakan sebagai titik awal kalkulasi jarak delivery.</p>

                <div id="adminOutletMap" style="height: 360px; border-radius: 12px; border: 2px solid #dee2e6; z-index: 1;"></div>

                <div class="row g-3 mt-3">
                    <div class="col-6">
                        <label class="form-label fw-medium small">Latitude</label>
                        <input type="text" name="outlet_lat" id="outletLat" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($outletCoords['lat'] ?? '') ?>" placeholder="-6.xxxxxx" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium small">Longitude</label>
                        <input type="text" name="outlet_lng" id="outletLng" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($outletCoords['lng'] ?? '') ?>" placeholder="106.xxxxxx" readonly>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block"><?= sim_icon('ti-info-circle', 'me-1') ?>Koordinat akan otomatis terisi saat Boss menggeser pin di peta.</small>
            </div>

            <!-- Preview Card -->
            <div class="sim-card border-primary border-opacity-25">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-eye', 'me-2 text-info') ?>Preview Kalkulasi</h5>
                <div class="alert alert-light border small mb-0">
                    <div id="previewCalc">
                        <?php
                            $model = $settings['delivery_fee_model'] ?? 'per_km';
                            $perKm = (int)($settings['delivery_per_km_fee'] ?? 3000);
                            $flat  = (int)($settings['delivery_flat_fee'] ?? 5000);
                            $minF  = (int)($settings['delivery_min_fee'] ?? 5000);
                            $freeA = (int)($settings['delivery_free_above'] ?? 0);
                            $freeLimit = (float)($settings['delivery_free_km_limit'] ?? 2);
                            
                            $jarakContoh = 5; // contoh simulasi
                            $excess = max(0, $jarakContoh - $freeLimit);
                            if ($jarakContoh <= $freeLimit) {
                                $example = 0;
                            } else {
                                $example = $model === 'flat' ? max($minF, $flat) : max($minF, (int)ceil($excess * $perKm));
                            }
                        ?>
                        <div class="d-flex justify-content-between"><span>Model Tarif:</span> <strong><?= $model === 'flat' ? 'Tarif Tetap' : 'Per-KM' ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Gratis Jarak S/d:</span> <strong><?= $freeLimit ?> km</strong></div>
                        <div class="d-flex justify-content-between mt-2 pt-2 border-top"><span>Simulasi Jarak:</span> <strong><?= $jarakContoh ?> km</strong></div>
                        <div class="d-flex justify-content-between"><span>Ongkir (<?= $jarakContoh ?> km):</span> <strong class="text-success">Rp <?= number_format($example, 0, ',', '.') ?></strong></div>
                        <?php if ($freeA > 0): ?>
                        <div class="d-flex justify-content-between mt-1 text-muted"><span>Gratis jika total belanja &ge;:</span> <strong>Rp <?= number_format($freeA, 0, ',', '.') ?></strong></div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mt-1 text-muted"><span>Maks radius pengantaran:</span> <strong><?= htmlspecialchars($settings['delivery_max_radius_km'] ?? '5') ?> km</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-danger rounded-pill px-5 mt-4 shadow-sm">
        <?= sim_icon('ti-device-floppy', 'me-2') ?>Simpan Pengaturan Delivery
    </button>
</form>

<!-- Leaflet CSS + JS (CDN) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fee model toggle
    const feeSelect = document.getElementById('feeModelSelect');
    const perKmFields = document.getElementById('perKmFields');
    const flatFields = document.getElementById('flatFields');
    if (feeSelect) {
        feeSelect.addEventListener('change', function() {
            perKmFields.style.display = this.value === 'per_km' ? '' : 'none';
            flatFields.style.display = this.value === 'flat' ? '' : 'none';
        });
    }

    // Admin Outlet Map
    const defaultLat = <?= (float)($outletCoords['lat'] ?? -6.9175) ?>;
    const defaultLng = <?= (float)($outletCoords['lng'] ?? 106.9275) ?>;
    const hasCoords  = <?= (!empty($outletCoords['lat']) && !empty($outletCoords['lng'])) ? 'true' : 'false' ?>;
    const initZoom   = hasCoords ? 16 : 13;

    const map = L.map('adminOutletMap').setView([defaultLat, defaultLng], initZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
    marker.bindPopup('<b>Lokasi Outlet</b><br>Geser pin ini ke posisi outlet.').openPopup();

    function updateCoordInputs(lat, lng) {
        document.getElementById('outletLat').value = lat.toFixed(8);
        document.getElementById('outletLng').value = lng.toFixed(8);
    }

    if (hasCoords) {
        updateCoordInputs(defaultLat, defaultLng);
    }

    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        updateCoordInputs(pos.lat, pos.lng);
        map.panTo(pos);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoordInputs(e.latlng.lat, e.latlng.lng);
    });

    // Fix map rendering in tabs/cards (Leaflet quirk)
    setTimeout(() => map.invalidateSize(), 300);
});
</script>

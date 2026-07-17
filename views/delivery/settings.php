<?php include __DIR__ . '/../shared-flash.php'; ?>

<div class="sim-hero mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="sim-kicker">Delivery & Kurir Management</span>
            <h2>Konfigurasi & Peta Outlet</h2>
            <p>Atur radius maksimal pengantaran, model ongkos kirim, serta koordinat akurat dapur/outlet Lumero.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/delivery') ?>" class="btn btn-secondary text-light d-flex align-items-center gap-2">
                <?= sim_icon('ti-list-details', '', 'width:1.1rem; height:1.1rem;') ?> Monitoring Pesanan
            </a>
            <a href="<?= url('/delivery/settings') ?>" class="btn btn-primary d-flex align-items-center gap-2 active">
                <?= sim_icon('ti-settings', '', 'width:1.1rem; height:1.1rem;') ?> Pengaturan Delivery
            </a>
        </div>
    </div>
</div>

<form method="post" action="<?= url('/delivery/settings') ?>">
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
                <small class="text-muted">Jika diaktifkan, pelanggan dapat memilih opsi "Delivery (Diantar Kurir)" saat checkout di halaman Online Order.</small>
            </div>

            <div class="sim-card mb-4">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-map-pin-filled', 'me-2 text-danger') ?>Radius & Jarak</h5>
                <label class="form-label mt-2 fw-medium">Radius Delivery Maksimal (km)</label>
                <div class="input-group">
                    <input type="number" step="0.1" min="0.5" max="50" name="delivery_max_radius_km"
                        value="<?= htmlspecialchars($settings['delivery_max_radius_km'] ?? '5') ?>"
                        class="form-control" placeholder="5">
                    <span class="input-group-text">km</span>
                </div>
                <small class="text-muted mt-1 d-block">Pelanggan yang berada di luar radius ini tidak bisa memesan dengan opsi delivery.</small>
            </div>

            <div class="sim-card mb-4">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-currency-dollar', 'me-2 text-success') ?>Model Tarif Ongkos Kirim</h5>
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

                <label class="form-label fw-medium mt-2">Minimal Biaya Ongkir (Minimum Fee)</label>
                <div class="input-group mb-3">
                    <span class="input-group-text">Rp</span>
                    <input type="number" min="0" name="delivery_min_fee"
                        value="<?= htmlspecialchars($settings['delivery_min_fee'] ?? '5000') ?>"
                        class="form-control" placeholder="5000">
                </div>
                <small class="text-muted">Bila hasil kalkulasi per-km lebih rendah dari nilai ini, maka ongkir yang dikenakan adalah minimal biaya ongkir.</small>

                <hr class="my-3">

                <label class="form-label fw-medium">Gratis Ongkir Di Atas Belanja (Free Above)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" min="0" name="delivery_free_above"
                        value="<?= htmlspecialchars($settings['delivery_free_above'] ?? '0') ?>"
                        class="form-control" placeholder="0">
                </div>
                <small class="text-muted mt-1 d-block">Isi 0 jika tidak ada promo gratis ongkir.</small>
            </div>
        </div>

        <!-- RIGHT COLUMN: Outlet Coordinates & Map -->
        <div class="col-lg-6">
            <div class="sim-card h-100 d-flex flex-column">
                <h5 class="fw-bold mb-3"><?= sim_icon('ti-map-pin', 'me-2 text-warning') ?>Koordinat & Peta Lokasi Outlet Lumero</h5>
                <p class="text-muted small">Titik koordinat ini digunakan sebagai titik asal (0 km) untuk menghitung jarak pengantaran ke alamat pelanggan via Haversine Formula.</p>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Latitude (Garis Lintang)</label>
                        <input type="text" id="outletLatInput" name="delivery_outlet_lat"
                            value="<?= htmlspecialchars((string)($outletCoords['lat'] ?? '-6.9175')) ?>"
                            class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Longitude (Garis Bujur)</label>
                        <input type="text" id="outletLngInput" name="delivery_outlet_lng"
                            value="<?= htmlspecialchars((string)($outletCoords['lng'] ?? '106.9275')) ?>"
                            class="form-control form-control-sm" required>
                    </div>
                </div>

                <div class="mb-3 d-flex gap-2">
                    <input type="text" id="searchMapInput" class="form-control form-control-sm" placeholder="Cari nama jalan/kota outlet untuk geser peta (cth: Bandung)...">
                    <button type="button" id="btnSearchMap" class="btn btn-sm btn-secondary text-nowrap">Cari Lokasi</button>
                </div>

                <!-- Map Container -->
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <div id="outletMap" style="height: 380px; width: 100%; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: #161926;" class="flex-grow-1"></div>
                <small class="text-muted mt-2">💡 <b>Tips:</b> Klik atau geser pin merah pada peta di atas untuk menentukan posisi tepat outlet Anda.</small>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-2">
                        <?= sim_icon('ti-device-floppy', '', 'width:1.2rem; height:1.2rem;') ?> Simpan Konfigurasi Delivery
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Fee Model Fields
    const feeModelSelect = document.getElementById('feeModelSelect');
    const perKmFields = document.getElementById('perKmFields');
    const flatFields = document.getElementById('flatFields');

    if (feeModelSelect) {
        feeModelSelect.addEventListener('change', function() {
            if (this.value === 'flat') {
                perKmFields.style.display = 'none';
                flatFields.style.display = 'block';
            } else {
                perKmFields.style.display = 'block';
                flatFields.style.display = 'none';
            }
        });
    }

    // Initialize Leaflet Map
    const latInput = document.getElementById('outletLatInput');
    const lngInput = document.getElementById('outletLngInput');
    let initLat = parseFloat(latInput.value) || -6.9175;
    let initLng = parseFloat(lngInput.value) || 106.9275;

    const map = L.map('outletMap').setView([initLat, initLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    const outletIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="background:#ff2d55; border:3px solid #fff; border-radius:50%; width:32px; height:32px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px; box-shadow:0 3px 10px rgba(0,0,0,0.5);">🏠</div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    let marker = L.marker([initLat, initLng], {
        icon: outletIcon,
        draggable: true
    }).addTo(map);

    marker.bindPopup('<b>Lokasi Outlet D\'Celup</b><br>Geser pin untuk mengubah koordinat.').openPopup();

    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        latInput.value = pos.lat.toFixed(7);
        lngInput.value = pos.lng.toFixed(7);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        latInput.value = e.latlng.lat.toFixed(7);
        lngInput.value = e.latlng.lng.toFixed(7);
    });

    latInput.addEventListener('change', function() {
        const lat = parseFloat(this.value);
        const lng = parseFloat(lngInput.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            marker.setLatLng([lat, lng]);
            map.panTo([lat, lng]);
        }
    });

    lngInput.addEventListener('change', function() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(this.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            marker.setLatLng([lat, lng]);
            map.panTo([lat, lng]);
        }
    });

    // Geocoding Search
    const btnSearchMap = document.getElementById('btnSearchMap');
    const searchMapInput = document.getElementById('searchMapInput');

    function doSearch() {
        const q = searchMapInput.value.trim();
        if (!q) return;
        btnSearchMap.disabled = true;
        btnSearchMap.textContent = 'Mencari...';

        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&limit=1')
            .then(res => res.json())
            .then(data => {
                btnSearchMap.disabled = false;
                btnSearchMap.textContent = 'Cari Lokasi';
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    marker.setLatLng([lat, lon]);
                    map.setView([lat, lon], 16);
                    latInput.value = lat.toFixed(7);
                    lngInput.value = lon.toFixed(7);
                } else {
                    alert('Lokasi tidak ditemukan. Coba gunakan nama kota atau jalan yang lebih spesifik.');
                }
            })
            .catch(err => {
                btnSearchMap.disabled = false;
                btnSearchMap.textContent = 'Cari Lokasi';
                alert('Gagal mencari lokasi. Pastikan koneksi internet aktif.');
            });
    }

    btnSearchMap.addEventListener('click', doSearch);
    searchMapInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            doSearch();
        }
    });
});
</script>

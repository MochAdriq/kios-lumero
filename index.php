<?php
/**
 * SIM Resto Sempurna - Root Index Launcher
 * Upload file ini langsung ke folder: public_html/dcc/index.php
 */

$publicIndex = __DIR__ . '/public/index.php';

if (!file_exists($publicIndex)) {
    http_response_code(500);
    echo '<h1>SIM Resto belum terpasang sempurna</h1>';
    echo '<p>File <code>public/index.php</code> tidak ditemukan.</p>';
    echo '<p>Pastikan isi paket aplikasi berada langsung di folder <code>public_html/dcc/</code>, bukan masih di dalam folder hasil extract.</p>';
    exit;
}

require $publicIndex;

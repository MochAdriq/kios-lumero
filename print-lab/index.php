<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Bluetooth Print</title>
    <style>
        body { font-family: sans-serif; padding: 20px; text-align: center; background: #f0f2f5; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto; }
        .btn { display: block; width: 100%; padding: 15px; margin-bottom: 15px; border-radius: 8px; border: none; font-size: 16px; font-weight: bold; cursor: pointer; color: white; text-decoration: none; }
        .btn-primary { background: #0d6efd; }
        .btn-success { background: #198754; }
        .btn-dark { background: #212529; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Printer Test Lab</h2>
        <p>Gunakan tombol di bawah ini dari HP Android yang sudah terhubung dengan printer Bluetooth.</p>

        <!-- Link JSON langsung (Thermer Scheme) -->
        <button onclick="window.location.href = 'my.bluetoothprint.scheme://https://lokapedia.id/lumero/print-lab/test-print.php'" class="btn btn-primary">
            🖨️ Cetak via Thermer (Scheme)
        </button>

        <!-- Link RawBT -->
        <button onclick="testRawBT()" class="btn btn-success">
            🖨️ Cetak via RawBT
        </button>

        <script>
            function testRawBT() {
                // Fetch the JSON from test-print.php
                fetch('test-print.php')
                .then(res => res.text())
                .then(text => {
                    // RawBT accepts base64 encoded JSON
                    let b64 = btoa(unescape(encodeURIComponent(text)));
                    window.location.href = 'rawbt:data:application/json;base64,' + b64;
                })
                .catch(e => alert("Gagal mengambil data: " + e));
            }
        </script>

        <!-- Link JSON mentah (RawBT / Browser) -->
        <a href="test-print.php" target="_blank" class="btn btn-dark">
            📄 Lihat Output JSON
        </a>
    </div>

</body>
</html>

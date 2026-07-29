<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { 
    http_response_code(400); 
    echo 'ID order tidak valid.'; 
    exit; 
}

$pdo = Database::connection();
$st = $pdo->prepare("SELECT * FROM orders WHERE id=? LIMIT 1"); 
$st->execute([$id]); 
$order = $st->fetch(PDO::FETCH_ASSOC);

if (!$order) { 
    http_response_code(404); 
    echo 'Order tidak ditemukan.'; 
    exit; 
}

$isCashPayment = strtolower(trim((string)($order['payment_method'] ?? ''))) === 'cash';
$openDrawer = $isCashPayment;

// Construct URLs
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$scheme = $https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$jsonUrl = $scheme . '://' . $host . $dir . '/btapp-json.php?id=' . $id . '&t=' . time();
$schemeUrl = 'my.bluetoothprint.scheme://' . $jsonUrl;

// Assets base
$assetsUrl = $scheme . '://' . $host . rtrim(dirname($dir, 2), '/') . '/public/assets';

?>
<!doctype html>
<html lang="id">
<head>
<link rel="icon" type="image/png" href="<?=$assetsUrl?>/img/icon-192.png">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kirim Struk ke Printer</title>
<style>
  :root{--primary:#0d6efd;--cream:#f8f9fa;--ink:#212529;--line:#dee2e6;--muted:#6c757d}
  *{box-sizing:border-box} body{margin:0;min-height:100vh;display:grid;place-items:center;padding:16px;background:linear-gradient(180deg,var(--cream),#e9ecef);font-family:system-ui,-apple-system,sans-serif;color:var(--ink)}
  .box{width:min(620px,94vw);background:#fff;border:1px solid var(--line);border-radius:28px;padding:32px 22px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.08)}
  .logo{width:80px;height:80px;object-fit:contain;border-radius:22px;background:#fff;border:1px solid var(--line);padding:8px;margin:0 auto 16px}
  .spin{width:44px;height:44px;border-radius:50%;border:5px solid #e9ecef;border-top-color:var(--primary);margin:16px auto;animation:spin .8s linear infinite}
  @keyframes spin{to{transform:rotate(360deg)}}
  h1{margin:0 0 8px;color:var(--ink);font-size:22px} 
  p{margin:0 auto 24px;color:var(--muted);font-weight:500;line-height:1.5;max-width:470px;font-size:15px}
  .actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:12px}
  a,button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:11px 20px;border-radius:999px;border:1px solid var(--line);background:var(--primary);color:#fff;text-decoration:none;font-weight:600;cursor:pointer;font-size:14px;transition:0.2s}
  .ghost{background:#fff;color:var(--primary)}
  a:active{transform:scale(0.97)}
  .ok{margin-top:16px;color:#198754;font-weight:600;display:none;background:#d1e7dd;padding:8px 16px;border-radius:99px;display:inline-block;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <img class="logo" src="<?=$assetsUrl?>/img/icon-192.png" alt="Lumero" onerror="this.src='https://placehold.co/192x192?text=L'">
  <div class="spin"></div>
  <h1>Mengirim struk ke printer...</h1>
  <p>Sistem otomatis mengirim perintah cetak ke aplikasi Printer Bluetooth.<?php if($openDrawer): ?><br><br>👉 Laci uang akan otomatis terbuka untuk pembayaran Tunai.<?php endif; ?></p>
  <div class="actions">
    <a id="printLink" href="<?=htmlspecialchars($schemeUrl, ENT_QUOTES, 'UTF-8')?>">Kirim Ulang ke Printer</a>
    <a class="ghost" href="#" onclick="window.close(); return false;">Tutup Halaman Ini</a>
  </div>
  <div style="margin-top:20px;">
    <div class="ok" id="okText" style="display:none;">Perintah cetak terkirim.</div>
  </div>
</div>
<script>
const schemeUrl = <?=json_encode($schemeUrl, JSON_UNESCAPED_SLASHES)?>;
function sendPrint(){
  window.location.href = schemeUrl;
  setTimeout(function(){ 
      var ok = document.getElementById('okText'); 
      if(ok) ok.style.display = 'inline-block'; 
  }, 2500);
}
window.addEventListener('load', function(){ setTimeout(sendPrint, 450); });
document.getElementById('printLink').addEventListener('click', function(){ 
    setTimeout(function(){
        var ok = document.getElementById('okText'); 
        if(ok) ok.style.display = 'inline-block'; 
    }, 2500); 
});
</script>
</body>
</html>

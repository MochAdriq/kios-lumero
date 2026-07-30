<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../helpers/functions.php';
require_once __DIR__.'/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__.'/../helpers/free_order_helper.php';
require_once __DIR__.'/../config/loyalty.php';

date_default_timezone_set('Asia/Jakarta');
try{ if(isset($pdo) && $pdo instanceof PDO) $pdo->exec("SET time_zone = '+07:00'"); }catch(Throwable $e){}

$memberOnline = null;
if(!empty($_SESSION['member_id'])) {
    $memberOnline = loyalty_member_by_id($pdo,(int)$_SESSION['member_id']);
}

// Set session flag that customer passed through welcome page
$_SESSION['welcome_passed'] = time();

// If coming from idle timeout, optionally clear cart or selected outlet
if (isset($_GET['idle'])) {
    unset($_SESSION['lumero_selected_outlet_id']);
    // You can also clear cart here if needed, e.g., unset($_SESSION['cart']);
}

$freeOrderVideo = 'public/assets/video/lumero-promo.mp4';
$freeOrderPoster = 'public/assets/images/pos-products/lumero-pasekon.png';
$freeOrderVoiceBase = '../public/assets/audio/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - Lumero Self-Order</title>
    <link rel="manifest" href="../manifest-user.json">
    <link rel="apple-touch-icon" href="../public/assets/images/icon-512x512.png">
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('../sw.js').catch(err => console.log('SW registration failed: ', err));
        });
      }
    </script>
    <link rel="stylesheet" href="../public/assets/css/self-order-ui.css">
    <style>
        :root {
            --dp-gradient: linear-gradient(135deg, #FF2D55 0%, #FF6B00 100%);
            --dp-radius: 16px;
            --dp-radius-sm: 10px;
            --dp-surface: #1e1e24;
            --dp-text: #ffffff;
        }
        body {
            margin: 0; padding: 0; background: #050505; font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif; overflow: hidden;
        }
        .fo-video-overlay {
            position: fixed; inset: 0; background: #050505; z-index: 9999; display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .fo-video-overlay video {
            position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; background: #000;
        }
        .fo-video-overlay:after {
            content: ""; position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,.28), rgba(0,0,0,.16) 44%, rgba(0,0,0,.78)), radial-gradient(circle at center, rgba(255,199,44,.10), rgba(0,0,0,.32));
            pointer-events: none;
        }
        .fo-video-content {
            position: relative; z-index: 2; width: min(92vw, 680px); min-height: 74vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; text-align: center; color: #fff; padding: 22px 18px 58px;
        }
        .fo-video-badge {
            width: 76px; height: 76px; border-radius: 24px; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.24); display: grid; place-items: center; backdrop-filter: blur(10px); margin-bottom: 12px;
        }
        .fo-video-badge img { width: 64px; height: 64px; object-fit: contain; }
        .fo-video-title {
            margin: 0 0 10px; font-size: clamp(32px, 8vw, 64px); line-height: .95; font-weight: 950; text-shadow: 0 10px 30px rgba(0,0,0,.55); letter-spacing: -.05em;
        }
        .fo-video-subtitle {
            margin: 0 auto 18px; max-width: 560px; color: #F3F4F6; font-weight: 750; line-height: 1.55; font-size: 16px; text-shadow: 0 2px 10px rgba(0,0,0,0.8);
        }
        .fo-start-btn {
            border: 0; border-radius: 999px; background: var(--dp-gradient); color: #fff; font-size: 20px; font-weight: 900; min-width: 240px; padding: 16px 26px; box-shadow: 0 20px 44px rgba(255,45,85,.35); cursor: pointer; transition: transform .2s ease, box-shadow .2s ease;
        }
        .fo-start-btn:hover {
            transform: translateY(-2px); box-shadow: 0 24px 50px rgba(255,45,85,.45);
        }
        .fo-video-phone-box {
            width: min(92vw, 430px); display: grid; gap: 9px; margin: 2px auto 14px; background: rgba(24,24,34,.88); border: 1.5px solid rgba(255,255,255,.28); backdrop-filter: blur(14px); border-radius: var(--dp-radius); padding: 14px; box-shadow: 0 12px 32px rgba(0,0,0,0.7);
        }
        .fo-video-phone-box label {
            font-size: 12px; text-transform: uppercase; letter-spacing: .07em; font-weight: 900; color: #FFFFFF; text-align: left;
        }
        .fo-video-phone-row { display: grid; grid-template-columns: 1fr; gap: 8px; }
        .fo-video-phone-row input {
            height: 48px; border: 0; border-radius: var(--dp-radius-sm); padding: 0 14px; font-weight: 800; color: #fff; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>

<div class="fo-video-overlay" id="freeOrderVideoOverlay">
  <video id="freeOrderVideoPlayer" autoplay muted loop playsinline preload="auto" poster="../<?=fo_e($freeOrderPoster)?>">
    <source src="../<?=fo_e($freeOrderVideo)?>" type="video/mp4">
  </video>
  <div class="fo-video-content">
    <div class="fo-video-badge"><img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero"></div>
    <h2 class="fo-video-title">Online Order Lumero</h2>
    <p class="fo-video-subtitle">Pesan dulu dari HP, pilih jam ambil atau delivery yang pas, lalu bayar mudah via QRIS, transfer, atau cash di outlet.</p>
    
    <div class="fo-video-phone-box" style="display: none;">
      <label>Nomor WhatsApp Pelanggan</label>
      <div class="fo-video-phone-row">
        <input id="videoPhoneInput" inputmode="tel" placeholder="08xxxxxxxxxx" value="<?=fo_e($memberOnline['phone'] ?? '')?>">
      </div>
    </div>

    <button type="button" class="fo-start-btn" id="startFreeOrderBtn" onclick="goToBranchSelection()">Mulai Online Order &rarr;</button>
  </div>
</div>

<audio id="foBgm" src="<?=fo_e($freeOrderVoiceBase)?>slow-cafe.mp3?v=2" preload="auto" loop></audio>
<audio id="foVoiceWelcome" src="<?=fo_e($freeOrderVoiceBase)?>welcome.mp3?v=2" preload="auto"></audio>

<script>
function startBgm(){
  const bgm = document.getElementById('foBgm');
  if(bgm && bgm.paused){
    bgm.volume = 0.35;
    bgm.play().catch(()=>{});
  }
}

function goToBranchSelection() {
  startBgm();
  const welcomeAudio = document.getElementById('foVoiceWelcome');
  if (welcomeAudio) {
    welcomeAudio.play().catch(()=>{});
  }
  const phoneInput = document.getElementById('videoPhoneInput');
  if (phoneInput && phoneInput.value) {
    localStorage.setItem('lumero_customer_phone', phoneInput.value);
  }
  // Redirect to Page 2 (Branch Selection)
  setTimeout(() => {
    window.location.href = 'select-branch.php';
  }, 250);
}

document.addEventListener('DOMContentLoaded', () => {
  const player = document.getElementById('freeOrderVideoPlayer');
  if (player) {
    player.play().catch(()=>{});
  }
});
</script>
</body>
</html>

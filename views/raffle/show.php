<?php
function svgTrophy(): string {
    return '<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8M12 17v4"/><path d="M7 4H4a2 2 0 0 0-2 2v1c0 2.76 2.24 5 5 5h.5"/><path d="M17 4h3a2 2 0 0 1 2 2v1c0 2.76-2.24 5-5 5h-.5"/><path d="M7 4h10v8a5 5 0 0 1-10 0V4z"/></svg>';
}
function svgCheck(): string {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= asset('pos-template/bootstrap.min.css') ?>">
    <style>
        body {
            background-color: #0b0f19;
            color: #fff;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }
        .show-header {
            text-align: center;
            padding: 50px 20px;
            background: radial-gradient(circle at top, rgba(22, 163, 74, 0.15), transparent 60%);
            margin-bottom: 20px;
        }
        .show-header h1 {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 0 4px 15px rgba(0,0,0,0.5);
            margin-bottom: 10px;
        }
        
        .prize-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .prize-card.not-drawn:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .prize-card.not-drawn:hover .prize-img {
            transform: scale(1.05);
            border-color: rgba(22, 163, 74, 0.5);
        }
        .prize-card.drawn {
            background: linear-gradient(145deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.02));
            border-color: rgba(245, 158, 11, 0.3);
            cursor: default;
        }
        .prize-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 4px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .pulse-badge {
            animation: pulse-soft 2s infinite;
        }
        @keyframes pulse-soft {
            0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(22, 163, 74, 0); }
            100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
        }

        /* ── Roll Overlay CSS copied from detail.php ── */
        .roll-overlay {
            position: fixed; inset: 0; background: #04040c; z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .roll-panel {
            width: 90%; max-width: 600px; text-align: center;
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);
            border-radius: 32px; padding: 48px 32px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
        }
        .roll-eyebrow {
            font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em;
            color: rgba(255,255,255,0.5); margin-bottom: 16px;
        }
        .roll-heading {
            font-size: 24px; font-weight: 900; color: #fff;
            margin-bottom: 28px; letter-spacing: -0.02em;
        }
        .roll-machine {
            position: relative; height: 120px; border-radius: 20px;
            overflow: hidden; margin: 0 auto 12px;
            background: rgba(255,255,255,0.04);
            border: 2px solid rgba(255,255,255,0.1);
        }
        .spotlight {
            position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 0deg at 50% 50%, rgba(255,255,255,0) 0%, rgba(245,158,11,0.06) 10%, rgba(255,255,255,0) 20%);
            animation: rotateSpotlight 10s linear infinite; pointer-events: none; z-index: -1;
        }
        @keyframes rotateSpotlight { 100% { transform: rotate(360deg); } }
        .giant-countdown {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            font-size: 220px; font-weight: 900; color: #fff; z-index: 9999;
            text-shadow: 0 0 80px rgba(245,158,11,0.8); opacity: 0; pointer-events: none;
        }
        @keyframes popIn { 
            0% { transform: scale(0.3); opacity: 0; } 
            30% { transform: scale(1.1); opacity: 1; } 
            80% { transform: scale(1); opacity: 1; } 
            100% { transform: scale(0.9); opacity: 0; } 
        }
        .roll-strip { display: flex; flex-direction: column; will-change: transform; }
        .roll-strip.spinning { animation: spinSlot 0.25s linear infinite; filter: blur(1px); }
        @keyframes spinSlot { 0% { transform: translateY(0); } 100% { transform: translateY(calc(-100% + 120px)); } }
        .roll-strip.stopping { transition: transform 3.8s cubic-bezier(0.12, 0.85, 0.2, 1); }
        .roll-item {
            height: 120px; display: flex; align-items: center; justify-content: center;
            font-family: 'Courier New', monospace; font-size: 40px; font-weight: 900;
            color: #fff; letter-spacing: 0.05em; flex-shrink: 0;
        }
        .roll-item.winner-item { color: #f59e0b; font-size: 46px; text-shadow: 0 0 20px rgba(245,158,11,0.5); }
        .roll-mask { position: absolute; left: 0; right: 0; height: 40px; pointer-events: none; z-index: 2; }
        .roll-mask-top    { top: 0;    background: linear-gradient(to bottom, rgba(4,4,12,0.9), transparent); }
        .roll-mask-bottom { bottom: 0; background: linear-gradient(to top,   rgba(4,4,12,0.9), transparent); }
        
        .btn-start-roll {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 16px 48px; border-radius: 16px;
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff; font-size: 16px; font-weight: 900; letter-spacing: 0.08em;
            border: none; cursor: pointer;
            box-shadow: 0 8px 32px rgba(22, 163, 74, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            text-transform: uppercase; margin-bottom: 20px;
        }
        .btn-start-roll:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(22, 163, 74, 0.45); }
        
        .roll-status {
            font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.45); 
            margin-top: 12px; margin-bottom: 32px; min-height: 20px;
        }
        
        .winner-reveal { display: none; }
        .winner-reveal.visible { display: block; animation: revealIn 0.65s cubic-bezier(0.16,1,0.3,1) forwards; }
        @keyframes revealIn { 0% { opacity: 0; transform: scale(0.9) translateY(20px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
        .winner-trophy-wrap { display: flex; justify-content: center; margin-bottom: 16px; animation: trophyBounce 2s infinite; }
        @keyframes trophyBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .winner-tag { font-size: 14px; font-weight: 800; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
        .winner-big-name { font-size: 42px; font-weight: 900; color: #fff; letter-spacing: -0.02em; line-height: 1.1; margin-bottom: 16px; text-shadow: 0 0 20px rgba(255,255,255,0.2); }
        .winner-ticket-num { font-size: 24px; font-weight: 900; color: #4ade80; font-family: 'Courier New', monospace; letter-spacing: 0.05em; margin-bottom: 8px; }
        .winner-phone-num { font-size: 16px; font-weight: 600; color: rgba(255,255,255,0.6); margin-bottom: 32px; }
        .btn-close-roll {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 32px; border-radius: 12px; background: rgba(255,255,255,0.1); color: #fff;
            font-size: 15px; font-weight: 800; border: none; cursor: pointer; transition: background 0.2s;
        }
        .btn-close-roll:hover { background: rgba(255,255,255,0.15); }

        #confettiCanvas { position: fixed; inset: 0; pointer-events: none; z-index: 9998; display: none; }
    </style>
</head>
<body>

    <div class="show-header">
        <h1><?= htmlspecialchars($batch['name']) ?></h1>
        <p class="text-white-50 fs-5 mb-0">Silakan pilih salah satu hadiah di bawah ini untuk memulai pengundian acak.</p>
    </div>

    <div class="container pb-5">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4 justify-content-center">
            <?php foreach ($prizes as $p): ?>
                <div class="col">
                    <div class="prize-card <?= $p['winner_ticket_id'] ? 'drawn' : 'not-drawn' ?>" 
                         <?php if (!$p['winner_ticket_id']): ?>
                         onclick="prepareRoll(<?= (int)$p['id'] ?>, <?= (int)$batch['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= $p['image_url'] ? asset($p['image_url']) : '' ?>')"
                         <?php endif; ?>>
                        
                        <?php if ($p['image_url']): ?>
                            <img src="<?= asset($p['image_url']) ?>" class="prize-img" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <div class="prize-img d-flex align-items-center justify-content-center bg-dark fs-1">🎁</div>
                        <?php endif; ?>
                        
                        <h5 class="fw-bold mb-4 px-2" style="font-size: 1.1rem; line-height: 1.4;"><?= htmlspecialchars($p['name']) ?></h5>

                        <div class="mt-auto">
                            <?php if ($p['winner_ticket_id']): ?>
                                <div class="text-warning mb-2">
                                    <span class="fs-4">🏆</span> <span class="fw-bold fs-5 text-white"><?= htmlspecialchars($p['winner_name'] ?? '-') ?></span>
                                </div>
                                <div class="badge bg-dark border border-secondary font-monospace fs-6 px-3 py-2 text-warning mb-2">
                                    <?= htmlspecialchars($p['ticket_code']) ?>
                                </div>
                                <?php if (!empty($p['winner_phone'])): ?>
                                    <div class="small text-white-50">
                                        <?= htmlspecialchars($p['winner_phone']) ?>
                                    </div>
                                <?php endif ?>
                            <?php else: ?>
                                <div class="badge bg-success pulse-badge px-4 py-2 rounded-pill fs-6 fw-bold text-uppercase">
                                    Siap Diundi
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="javascript:window.close()" class="btn btn-outline-secondary rounded-pill px-4">Tutup Layar</a>
        </div>
    </div>
    
    <!-- ─── Rolling Overlay ───────────────────────────────────────── -->
    <div id="rollOverlay" class="roll-overlay" hidden>
      <div id="giantCountdown" class="giant-countdown"></div>
      <div class="roll-panel" id="rollPanel">
        <div class="spotlight"></div>
        <div class="spotlight" style="animation-direction: reverse; animation-duration: 15s; opacity: 0.5;"></div>
        
        <!-- Phase 1: Idle & Rolling -->
        <div id="rollPhase" style="position:relative; z-index:1; transition: opacity 0.5s;">
          <div class="roll-eyebrow text-warning">Pengundian Berlangsung</div>
          
          <div id="rollPrizeImgWrap" style="text-align:center; margin-bottom: 24px; display:none;">
            <img id="rollPrizeImg" src="" alt="Prize" style="width: 130px; height: 130px; object-fit: cover; border-radius: 20px; border: 4px solid rgba(255,255,255,0.15); box-shadow: 0 15px 35px rgba(0,0,0,0.4);">
          </div>

          <div class="roll-heading" id="rollPrizeName"></div>
          
          <div class="roll-machine">
            <div class="roll-mask roll-mask-top"></div>
            <div class="roll-strip" id="rollStrip">
              <div class="roll-item">[ SIAP DIUNDI ]</div>
            </div>
            <div class="roll-mask roll-mask-bottom"></div>
          </div>
          
          <div class="roll-status" id="rollStatus">Tekan tombol di bawah atau tombol Spasi</div>
          
          <div id="rollParticipantCount" style="color:#4ade80; font-weight:800; margin-bottom: 15px; font-size:15px; letter-spacing:0.05em; text-transform:uppercase; text-shadow: 0 0 10px rgba(74,222,128,0.3);">
             <?= number_format($stats['total_tickets'] ?? 0, 0, ',', '.') ?> Tiket Siap Diundi
          </div>
          
          <div class="d-flex flex-column align-items-center">
            <button id="btnStartRoll" class="btn-start-roll mb-3">Mulai Pengundian</button>
            <button id="btnCancelRoll" class="btn btn-outline-light rounded-pill px-4" onclick="cancelRoll()" style="border-color: rgba(255,255,255,0.2); color: rgba(255,255,255,0.6);">Batal & Pilih Ulang</button>
          </div>
        </div>

        <!-- Phase 2: Winner Reveal -->
        <div class="winner-reveal" id="winnerReveal">
          <div class="winner-trophy-wrap"><?= svgTrophy() ?></div>
          <div class="winner-tag">Selamat Kepada Pemenang!</div>
          <div class="winner-big-name" id="winnerName"></div>
          <div class="winner-ticket-num" id="winnerTicketCode"></div>
          <div class="winner-phone-num" id="winnerPhone"></div>
          <button class="btn-close-roll" onclick="closeRoll()">
            <?= svgCheck() ?> Tutup &amp; Lihat Daftar Hadiah
          </button>
        </div>
      </div>
    </div>

    <!-- Confetti canvas (behind overlay text, above overlay bg) -->
    <canvas id="confettiCanvas"></canvas>

    <script>
    /* ── Ticket pool for animation ─────────────────────────────── */
    const TICKET_POOL = ['UND-982133', 'UND-458129', 'UND-102934', 'UND-847192', 'UND-382910', 'UND-582914', 'UND-784192'];

    let currentPrizeId, currentBatchId;
    let rollDone = false;
    let isRollingStarted = false;
    let teaserInterval, heartbeatInterval, rollMachineSound;

    // Synth Audio Helper for sound effects
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    function playBeep(freq, type, duration, vol=0.1) {
        if(audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = type; osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
        gain.gain.setValueAtTime(vol, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
        osc.connect(gain); gain.connect(audioCtx.destination);
        osc.start(); osc.stop(audioCtx.currentTime + duration);
    }
    function playWinnerSound() {
        playBeep(440, 'triangle', 0.2, 0.2); 
        setTimeout(() => playBeep(554, 'triangle', 0.2, 0.2), 150);
        setTimeout(() => playBeep(659, 'triangle', 0.8, 0.2), 300);
    }

    function startTeaser() {
        clearInterval(teaserInterval);
        const strip = document.getElementById('rollStrip');
        teaserInterval = setInterval(() => {
            if (!isRollingStarted && Math.random() > 0.6) {
                strip.innerHTML = `<div class="roll-item" style="color:rgba(255,255,255,0.15); font-size:30px; font-weight:normal;">${TICKET_POOL[Math.floor(Math.random()*TICKET_POOL.length)]}</div>`;
                setTimeout(() => {
                    if(!isRollingStarted) strip.innerHTML = '<div class="roll-item" style="color:rgba(255,255,255,0.4)">[ SIAP DIUNDI ]</div>';
                }, 80);
            }
        }, 1500);
    }

    function startHeartbeat() {
        clearInterval(heartbeatInterval);
        heartbeatInterval = setInterval(() => {
            if (!isRollingStarted) {
                playBeep(60, 'sine', 0.4, 0.2);
                setTimeout(() => playBeep(60, 'sine', 0.4, 0.1), 250);
            }
        }, 1200);
    }

    function prepareRoll(prizeId, batchId, prizeName, prizeImgUrl) {
        currentPrizeId = prizeId;
        currentBatchId = batchId;
        rollDone = false;
        isRollingStarted = false;
        
        document.getElementById('rollPhase').style.display   = '';
        document.getElementById('rollPhase').style.opacity   = '1';
        document.getElementById('winnerReveal').classList.remove('visible');
        document.getElementById('rollPrizeName').textContent = prizeName;
        document.getElementById('rollStatus').innerHTML      = '<span style="animation: pulse 1.5s infinite;">Tekan tombol di bawah atau tombol SPASI di keyboard</span>';
        
        if (prizeImgUrl) {
            document.getElementById('rollPrizeImg').src = prizeImgUrl;
            document.getElementById('rollPrizeImgWrap').style.display = 'block';
        } else {
            document.getElementById('rollPrizeImgWrap').style.display = 'none';
        }

        const strip = document.getElementById('rollStrip');
        strip.className = 'roll-strip'; 
        strip.style.transform = '';
        strip.innerHTML = '<div class="roll-item" style="color:rgba(255,255,255,0.4)">[ SIAP DIUNDI ]</div>';
        
        document.getElementById('btnStartRoll').style.display = 'inline-flex';
        document.getElementById('btnCancelRoll').style.display = 'inline-block';
        
        document.getElementById('rollOverlay').removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        
        if(audioCtx.state === 'suspended') audioCtx.resume();
        startTeaser();
        startHeartbeat();
    }

    document.getElementById('btnStartRoll').addEventListener('click', function() {
        this.style.display = 'none';
        document.getElementById('btnCancelRoll').style.display = 'none';
        isRollingStarted = true;
        clearInterval(teaserInterval);
        clearInterval(heartbeatInterval);
        
        document.getElementById('rollPhase').style.opacity = '0.15';
        
        const cdEl = document.getElementById('giantCountdown');
        let count = 3;
        
        function tickCountdown() {
            if (count > 0) {
                cdEl.textContent = count;
                cdEl.style.animation = 'none';
                void cdEl.offsetWidth;
                cdEl.style.animation = 'popIn 1s ease-out forwards';
                playBeep(400, 'square', 0.2, 0.1);
                count--;
                setTimeout(tickCountdown, 1000);
            } else {
                cdEl.textContent = 'GO!';
                cdEl.style.animation = 'none';
                void cdEl.offsetWidth;
                cdEl.style.animation = 'popIn 1s ease-out forwards';
                playBeep(800, 'square', 0.4, 0.15);
                
                setTimeout(() => {
                    document.getElementById('rollPhase').style.opacity = '1';
                    document.getElementById('rollStatus').textContent = 'Mengumpulkan seluruh tiket peserta...';
                    
                    setTimeout(() => { document.getElementById('rollStatus').textContent = 'Memasukkan tiket ke dalam mesin acak...'; }, 1800);
                    setTimeout(() => { document.getElementById('rollStatus').textContent = 'Mengacak secara acak! Siap-siap...'; }, 3800);
                    setTimeout(() => { document.getElementById('rollStatus').textContent = 'Mencari kandidat pemenang...'; }, 5500);
                    
                    startActualRoll();
                }, 600);
            }
        }
        tickCountdown();
    });

    document.addEventListener('keydown', function(e) {
        if (e.code === 'Space' && !document.getElementById('rollOverlay').hasAttribute('hidden')) {
            const btn = document.getElementById('btnStartRoll');
            if (btn.style.display !== 'none') {
                e.preventDefault();
                btn.click();
            }
        }
    });

    function startActualRoll() {
        rollMachineSound = setInterval(() => {
            playBeep(150 + Math.random()*200, 'sawtooth', 0.05, 0.03);
        }, 60);

        const strip = document.getElementById('rollStrip');
        const pool = TICKET_POOL;
        
        let html = '';
        for (let i = 0; i < 30; i++) {
            html += `<div class="roll-item">${pool[Math.floor(Math.random() * pool.length)]}</div>`;
        }
        strip.innerHTML = html;
        strip.className = 'roll-strip spinning';
        
        const startTime = Date.now();
        const formData  = new FormData();
        formData.append('prize_id', currentPrizeId);
        formData.append('batch_id', currentBatchId);
        formData.append('_ajax',    '1');

        fetch('<?= url('/raffle/draw-winner') ?>', {
            method: 'POST',
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            const elapsed = Date.now() - startTime;
            const delay   = Math.max(0, 7000 - elapsed); 

            setTimeout(() => {
                if (!data.success) {
                    strip.className = 'roll-strip';
                    strip.innerHTML = '<div class="roll-item" style="color:#ef4444;">[ ERROR ]</div>';
                    document.getElementById('rollStatus').textContent = data.message || 'Gagal mengundi.';
                    setTimeout(closeRoll, 3000);
                    return;
                }
                revealWinnerSmooth(data, pool);
            }, delay);
        })
        .catch(() => {
            strip.className = 'roll-strip';
            document.getElementById('rollStatus').textContent = 'Terjadi kesalahan jaringan.';
            setTimeout(closeRoll, 3000);
        });
    }

    function revealWinnerSmooth(data, pool) {
        clearInterval(rollMachineSound);
        
        let slowInterval = 60;
        let slowTimer;
        function playSlowDown() {
            playBeep(200, 'sine', 0.1, 0.04);
            slowInterval *= 1.35;
            if(slowInterval < 800) slowTimer = setTimeout(playSlowDown, slowInterval);
        }
        playSlowDown();

        const strip = document.getElementById('rollStrip');
        const finalCode = data.ticket_code || pool[Math.floor(Math.random() * pool.length)];
        
        strip.className = 'roll-strip';
        
        let html = '';
        const itemsCount = 40;
        for (let i = 0; i < itemsCount; i++) {
            html += `<div class="roll-item" style="color:rgba(255,255,255,0.7)">${pool[Math.floor(Math.random() * pool.length)]}</div>`;
        }
        html += `<div class="roll-item winner-item">${finalCode}</div>`;
        strip.innerHTML = html;
        
        strip.style.transform = `translateY(0px)`;
        document.getElementById('rollStatus').textContent = 'Menemukan pemenang...';
        
        void strip.offsetWidth; 
        
        strip.className = 'roll-strip stopping';
        strip.style.transform = `translateY(-${itemsCount * 120}px)`; 
        
        setTimeout(() => {
            playWinnerSound();
            showWinnerCard(data);
        }, 4100); 
    }

    function showWinnerCard(data) {
        document.getElementById('rollPhase').style.display = 'none';
        document.getElementById('winnerName').textContent      = data.winner_name  || 'Pemenang';
        document.getElementById('winnerTicketCode').textContent = data.ticket_code  || '';
        document.getElementById('winnerPhone').textContent     = data.winner_phone || '';
        document.getElementById('winnerReveal').classList.add('visible');
        startConfetti();
    }

    function cancelRoll() {
        if (isRollingStarted) return;
        clearInterval(teaserInterval);
        clearInterval(heartbeatInterval);
        document.getElementById('rollOverlay').setAttribute('hidden', '');
        document.body.style.overflow = '';
    }

    function closeRoll() {
        clearInterval(teaserInterval);
        clearInterval(heartbeatInterval);
        clearInterval(rollMachineSound);
        document.getElementById('rollOverlay').setAttribute('hidden', '');
        document.body.style.overflow = '';
        stopConfetti();
        location.reload();
    }

    /* ── Confetti ──────────────────────────────────────────────── */
    let confettiRaf = null;
    const COLORS = ['#c41230', '#ffc72c', '#16a34a', '#3b82f6', '#f59e0b', '#fff'];

    function startConfetti() {
        const canvas = document.getElementById('confettiCanvas');
        canvas.style.display = 'block';
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
        const ctx = canvas.getContext('2d');

        const particles = Array.from({ length: 160 }, () => ({
            x:     Math.random() * canvas.width,
            y:     -10 - Math.random() * 120,
            vx:    (Math.random() - 0.5) * 5,
            vy:    Math.random() * 4 + 2,
            w:     Math.random() * 10 + 4,
            h:     Math.random() * 6  + 3,
            color: COLORS[Math.floor(Math.random() * COLORS.length)],
            angle: Math.random() * 360,
            spin:  (Math.random() - 0.5) * 5,
            alpha: 1,
        }));

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let alive = 0;
            particles.forEach(p => {
                if (p.y > canvas.height + 20) { p.alpha = 0; return; }
                p.x     += p.vx;
                p.y     += p.vy;
                p.vy    += 0.06;
                p.angle += p.spin;
                if (p.y > canvas.height * 0.7) p.alpha = Math.max(0, p.alpha - 0.012);

                ctx.save();
                ctx.globalAlpha = p.alpha;
                ctx.translate(p.x, p.y);
                ctx.rotate(p.angle * Math.PI / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
                if (p.alpha > 0) alive++;
            });
            if (alive > 0) confettiRaf = requestAnimationFrame(draw);
            else stopConfetti();
        }
        draw();
    }

    function stopConfetti() {
        if (confettiRaf) cancelAnimationFrame(confettiRaf);
        document.getElementById('confettiCanvas').style.display = 'none';
    }
    </script>
</body>
</html>

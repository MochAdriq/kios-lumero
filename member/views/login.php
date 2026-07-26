<style>
/* CSS for split screen */
body { margin:0; padding:0; min-height: 100vh; font-family: Inter, system-ui, -apple-system, sans-serif; background: #fff; }
.split-screen { display: flex; min-height: 100vh; width: 100vw; }
.split-left { 
    flex: 5.5; 
    position: relative; 
    background: linear-gradient(-45deg, #7a001b, #c41230, #ffc72c, #7a001b); 
    background-size: 400% 400%; 
    animation: gradientBG 15s ease infinite;
    display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; color: #fff; text-align: center; overflow: hidden;
}
@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
/* Glassmorphism elements */
.glass-obj {
    position: absolute; background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 50%; pointer-events: none;
}
.glass-1 { width: 350px; height: 350px; top: -100px; left: -100px; animation: floatObj 8s infinite alternate ease-in-out; }
.glass-2 { width: 250px; height: 250px; bottom: 5%; right: 5%; animation: floatObj 10s infinite alternate-reverse ease-in-out; }
@keyframes floatObj {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(40px) rotate(15deg); }
}

.left-content { position: relative; z-index: 10; max-width: 480px; }
.left-content img.logo { width: 85px; height: 85px; border-radius: 24px; box-shadow: 0 24px 48px rgba(0,0,0,0.3); border: 2px solid rgba(255,255,255,0.4); margin-bottom: 28px; background: #fff; padding: 8px; }
.left-content h1 { font-size: 46px; font-weight: 1000; letter-spacing: -0.05em; line-height: 1.1; margin: 0 0 16px; text-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.left-content p { font-size: 16px; font-weight: 500; color: rgba(255,255,255,0.9); line-height: 1.6; }

.split-right {
    flex: 4.5;
    background: #fff;
    display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; position: relative;
}
.login-box { width: 100%; max-width: 380px; z-index: 10; }
.login-box h2 { font-size: 28px; font-weight: 900; color: var(--ink); margin: 0 0 8px; letter-spacing: -0.03em; }
.login-box p.subtitle { font-size: 14px; font-weight: 600; color: var(--muted); margin: 0 0 32px; line-height: 1.5; }

/* Custom Form Styles for Redesign */
.floating-form { display: grid; gap: 18px; }
.input-group { position: relative; }
.input-group label { display: block; font-size: 11px; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
.input-group input { width: 100%; border: 2px solid #eef0f4; border-radius: 16px; padding: 16px 18px; font-size: 16px; font-weight: 800; color: var(--ink); background: #f8fafc; transition: all 0.3s ease; }
.input-group input:focus { outline: none; border-color: var(--red); background: #fff; box-shadow: 0 8px 24px rgba(196, 18, 48, 0.12); }

.btn-primary { width: 100%; border: none; border-radius: 16px; padding: 18px; background: linear-gradient(135deg, var(--red), var(--red2)); color: #fff; font-size: 16px; font-weight: 900; cursor: pointer; transition: all 0.3s; box-shadow: 0 12px 28px rgba(196, 18, 48, 0.28); display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 12px; }
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 16px 36px rgba(196, 18, 48, 0.38); }
.btn-secondary { width: 100%; border: 2px solid #eef0f4; border-radius: 16px; padding: 16px; background: #fff; color: var(--muted); font-size: 14px; font-weight: 800; cursor: pointer; transition: all 0.3s; text-align: center; display: block; text-decoration: none; margin-top: 12px; }
.btn-secondary:hover { border-color: var(--line); color: var(--ink); background: #f8fafc; }

/* Glass Ticket */
.glass-ticket { position: absolute; bottom: 40px; right: 40px; background: rgba(255, 255, 255, 0.65); backdrop-filter: blur(28px); -webkit-backdrop-filter: blur(28px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 24px; padding: 20px; width: 340px; box-shadow: 0 24px 48px rgba(0,0,0,0.08); display: flex; gap: 16px; align-items: center; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); z-index: 20; }
.glass-ticket:hover { transform: translateY(-8px); border-color: var(--gold); }
.glass-ticket::before { content: ''; position: absolute; left: -12px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: #fff; border-radius: 50%; box-shadow: inset -2px 0 6px rgba(0,0,0,0.06); }

/* Mobile adjustments */
@media(max-width: 900px) {
    .split-screen { flex-direction: column; overflow-y: auto; height: auto; min-height: 100vh; }
    .split-left { flex: none; padding: 60px 24px; border-radius: 0 0 30px 30px; z-index: 2; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
    .left-content h1 { font-size: 38px; }
    .split-right { flex: none; padding: 50px 24px; margin-top: 0; }
    .glass-ticket { position: relative; bottom: auto; right: auto; margin-top: 40px; width: 100%; max-width: 380px; background: #fffaf0; border: 1px dashed var(--gold); box-shadow: 0 12px 24px rgba(180, 120, 20, 0.08); }
    .glass-ticket::before { display: none; }
}
</style>

<div class="split-screen">
    <!-- Left Side: Branding & Glassmorphism -->
    <div class="split-left">
        <div class="glass-obj glass-1"></div>
        <div class="glass-obj glass-2"></div>
        
        <div class="left-content">
            <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero Logo" class="logo">
            <?php if (isset($_GET['source']) && $_GET['source'] === 'event_kalibunder' && !empty($_SESSION['pending_event_reward'])): ?>
                <h1>Klaim Kupon</h1>
                <p>Amankan hadiah kejutan Anda di dompet digital Lumero sebelum orang lain yang mengklaimnya.</p>
            <?php else: ?>
                <h1>Member Loyalty Club</h1>
                <p>Rasakan pengalaman bersantap yang lebih menguntungkan. Kumpulkan poin dari setiap pesanan dan tukarkan dengan menu rahasia gratis dari Lumero.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Side: Login Form -->
    <div class="split-right">
        <!-- Flash Messages -->
        <?php if($msg || $err): ?>
        <div style="margin-bottom: 24px;">
            <?php if($msg): ?><div class="alert ok" style="box-shadow: 0 12px 30px rgba(22, 101, 52, 0.15); border: 1px solid #a7f3d0; margin-top:0;">✨ <?=mem_e($msg)?></div><?php endif; ?>
            <?php if($err): ?><div class="alert err" style="box-shadow: 0 12px 30px rgba(185, 28, 28, 0.15); border: 1px solid #fecaca; margin-top:0;">⚠️ <?=mem_e($err)?></div><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="login-box">
            <?php if(!$pendingPhone): ?>
                <?php if (isset($_GET['source']) && $_GET['source'] === 'event_kalibunder' && !empty($_SESSION['pending_event_reward'])): ?>
                    <h2 style="color:var(--red);">Amankan Hadiah Anda! 🚨</h2>
                    <p class="subtitle">Jangan biarkan kupon <b><?= mem_e($_SESSION['pending_event_reward']['name']) ?></b> Anda hangus. Masukkan nomor WhatsApp sekarang untuk mengamankannya.</p>
                <?php else: ?>
                    <h2>Selamat Datang</h2>
                    <p class="subtitle">Masukkan nomor WhatsApp Anda untuk memulai sesi atau mendaftar member baru secara gratis.</p>
                <?php endif; ?>
                <form method="post" class="floating-form" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
                    <input type="hidden" name="action" value="check_phone">
                    <div class="input-group">
                        <label>Nomor WhatsApp</label>
                        <input name="phone" inputmode="tel" required placeholder="08xxxxxxxxxx" autofocus>
                    </div>
                    <button class="btn-primary">Lanjutkan Sesi &rarr;</button>
                </form>

            <?php else: ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h2>Autentikasi</h2>
                    <a href="?ulang=1" style="font-size:12px; font-weight:800; color:var(--red); text-decoration:none; background:#fef2f2; padding:6px 12px; border-radius:99px;">Ubah Nomor</a>
                </div>
                <div style="background:#f8fafc; border:1px solid #eef0f4; border-radius:18px; padding:18px; margin-bottom:28px; text-align:center;">
                    <div style="font-size:11px; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">WhatsApp Terdeteksi</div>
                    <div style="font-size:26px; font-weight:1000; color:var(--ink); letter-spacing:-0.04em;"><?=mem_e(loyalty_mask_phone($pendingPhone))?></div>
                </div>

                <?php if($pendingMode==='pin'): ?>
                    <form method="post" class="floating-form" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
                        <input type="hidden" name="action" value="login_pin">
                        <div class="input-group">
                            <label>PIN Keamanan (4 Digit)</label>
                            <input name="pin" type="password" inputmode="numeric" required placeholder="••••" maxlength="4" pattern="\d{4}" autofocus>
                        </div>
                        <button class="btn-primary">Masuk Sekarang</button>
                    </form>

                <?php elseif($pendingMode==='verify_otp'): ?>
                    <form method="post" class="floating-form" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
                        <input type="hidden" name="action" value="verify_otp">
                        <div class="input-group">
                            <label>Kode OTP WhatsApp</label>
                            <input name="otp" type="text" inputmode="numeric" maxlength="6" required placeholder="123456" autofocus>
                        </div>
                        <p style="font-size:13px; color:var(--muted); font-weight:600; line-height:1.5; margin:0;">
                            Kode 6 digit telah dikirim ke WhatsApp Anda. 
                        </p>
                        <button class="btn-primary">Verifikasi OTP</button>
                    </form>

                <?php else: ?>
                    <p class="subtitle" style="margin-bottom:24px; font-size:13px;"><?=($pendingMode==='setup')?'Akun ini belum memiliki PIN. Buat PIN untuk aktivasi keamanan.':'Nomor Anda baru. Buat PIN untuk menyelesaikan pendaftaran member.'?></p>
                    <form method="post" class="floating-form" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?=mem_e($csrf)?>">
                        <input type="hidden" name="action" value="create_pin">
                        <div class="input-group">
                            <label>Buat PIN Baru</label>
                            <input name="pin" type="password" inputmode="numeric" required placeholder="4 digit PIN" maxlength="4" pattern="\d{4}" autofocus>
                        </div>
                        <div class="input-group">
                            <label>Konfirmasi PIN</label>
                            <input name="pin_confirm" type="password" inputmode="numeric" required placeholder="Masukkan kembali PIN 4 digit" maxlength="4" pattern="\d{4}">
                        </div>
                        <button class="btn-primary">Simpan PIN & Masuk</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Floating Glass Ticket for Claim -->
        <div class="glass-ticket">
            <div style="font-size:38px; line-height:1; filter: drop-shadow(0 6px 12px rgba(0,0,0,0.12));">🎟️</div>
            <div>
                <?php if($prefillClaim!==''): ?>
                    <h3 style="margin:0 0 6px; font-size:15px; font-weight:900; color:var(--ink); letter-spacing:-0.02em;">Struk: <?=mem_e($prefillClaim)?></h3>
                    <p style="margin:0; font-size:12px; font-weight:600; color:rgba(0,0,0,0.6); line-height:1.5;">Poin dari struk ini akan otomatis cair ke saldo Anda setelah berhasil login.</p>
                <?php else: ?>
                    <h3 style="margin:0 0 6px; font-size:15px; font-weight:900; color:var(--ink); letter-spacing:-0.02em;">Klaim Poin Struk</h3>
                    <p style="margin:0; font-size:12px; font-weight:600; color:rgba(0,0,0,0.6); line-height:1.5;">Punya struk belanja? Klaim setelah masuk untuk kumpulkan poin & hadiah.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// index.php — Halaman sambutan Lumero Kalibunder
$redirectUrl = 'http://lumero.co.id/';
$redirectDelay = 3;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#b5121b">

    <title>Selamat Datang di Lumero Kalibunder</title>

    <!-- Fallback redirect apabila JavaScript tidak aktif -->
    <meta http-equiv="refresh" content="<?= $redirectDelay; ?>;url=<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">

    <style>
        :root {
            --lumero-red: #b5121b;
            --lumero-dark-red: #7f0810;
            --lumero-gold: #f3c766;
            --lumero-cream: #fff8e9;
            --lumero-green: #178447;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--white);
            background:
                radial-gradient(circle at 20% 20%, rgba(243, 199, 102, 0.18), transparent 32%),
                radial-gradient(circle at 85% 80%, rgba(23, 132, 71, 0.16), transparent 32%),
                linear-gradient(145deg, var(--lumero-red), var(--lumero-dark-red));
        }

        .welcome-card {
            width: min(100%, 480px);
            text-align: center;
            padding: 36px 24px 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 28px;
            background: rgba(110, 0, 7, 0.28);
            box-shadow: 0 24px 65px rgba(45, 0, 3, 0.35);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: cardIn 700ms ease both;
        }

        .brand-mark {
            width: 104px;
            height: 104px;
            margin: 0 auto 22px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.94);
            color: var(--lumero-red);
            background: var(--lumero-cream);
            box-shadow:
                0 13px 30px rgba(45, 0, 3, 0.28),
                inset 0 0 0 5px var(--lumero-gold);
            font-size: 52px;
            animation: float 1.8s ease-in-out infinite;
        }

        .italy-strip {
            width: 92px;
            height: 8px;
            margin: 0 auto 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            overflow: hidden;
            border-radius: 999px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.14);
        }

        .italy-strip span:nth-child(1) { background: var(--lumero-green); }
        .italy-strip span:nth-child(2) { background: var(--white); }
        .italy-strip span:nth-child(3) { background: var(--lumero-red); }

        .eyebrow {
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--lumero-gold);
        }

        h1 {
            max-width: 390px;
            margin: 0 auto;
            font-size: clamp(28px, 8vw, 42px);
            line-height: 1.08;
            font-weight: 900;
            text-wrap: balance;
        }

        .tagline {
            max-width: 390px;
            margin: 16px auto 0;
            font-size: clamp(15px, 4vw, 18px);
            line-height: 1.55;
            color: rgba(255, 255, 255, 0.9);
            text-wrap: balance;
        }

        .tagline strong {
            display: block;
            margin-top: 3px;
            color: var(--lumero-gold);
        }

        .loader {
            width: 100%;
            max-width: 310px;
            height: 8px;
            margin: 30px auto 13px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
        }

        .loader::before {
            content: "";
            display: block;
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--lumero-gold), var(--white), var(--lumero-gold));
            animation: loading <?= $redirectDelay; ?>s linear forwards;
        }

        .redirect-text {
            min-height: 20px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.76);
        }

        .manual-link {
            display: inline-block;
            margin-top: 16px;
            padding: 11px 18px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            color: var(--white);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.08);
            transition: transform 180ms ease, background 180ms ease;
        }

        .manual-link:hover,
        .manual-link:focus-visible {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.16);
        }

        @keyframes loading {
            from { width: 0; }
            to { width: 100%; }
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(18px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-7px); }
        }

        @media (max-width: 420px) {
            body {
                padding: 16px;
            }

            .welcome-card {
                padding: 31px 18px 26px;
                border-radius: 24px;
            }

            .brand-mark {
                width: 92px;
                height: 92px;
                font-size: 45px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <main class="welcome-card" aria-labelledby="welcome-title">
        <div class="brand-mark" aria-hidden="true">🍗</div>

        <div class="italy-strip" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <p class="eyebrow">Benvenuto</p>

        <h1 id="welcome-title">Selamat Datang di Lumero Kalibunder</h1>

        <p class="tagline">
            Ayam Crispy Lokal, Cita Rasa Internasional
            <strong>Sekali Coba, Pasti Jatuh Cinta</strong>
        </p>

        <div class="loader" role="progressbar" aria-label="Memuat halaman Lumero"></div>

        <p class="redirect-text" id="redirectText">
            Mengarahkan dalam <span id="countdown"><?= $redirectDelay; ?></span> detik...
        </p>

        <a class="manual-link" href="<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">
            Masuk Sekarang
        </a>
    </main>

    <script>
        (() => {
            const redirectUrl = <?= json_encode($redirectUrl, JSON_UNESCAPED_SLASHES); ?>;
            const delaySeconds = <?= (int) $redirectDelay; ?>;
            const countdownElement = document.getElementById('countdown');
            const redirectText = document.getElementById('redirectText');

            let remaining = delaySeconds;

            const countdownTimer = window.setInterval(() => {
                remaining -= 1;

                if (remaining > 0) {
                    countdownElement.textContent = remaining;
                    return;
                }

                window.clearInterval(countdownTimer);
                redirectText.textContent = 'Membuka halaman Lumero...';
            }, 1000);

            window.setTimeout(() => {
                window.location.replace(redirectUrl);
            }, delaySeconds * 1000);
        })();
    </script>
</body>
</html>

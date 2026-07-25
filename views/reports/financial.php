<?php $role = Auth::role(); ?>
<style>
:root {
            --red: #c41230;
            --red-dark: #8f001b;
            --orange: #ff9f1c;
            --gold: #ffc72c;
            --green: #159447;
            --blue: #2563eb;
            --dark: #182033;
            --muted: #667085;
            --line: #e7eaf0;
            --bg: #f7f8fb;
            --cream: #fff8ea;
            --shadow: 0 18px 48px rgba(15, 23, 42, .09)
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background: linear-gradient(180deg, #fff7ec, #f7f9fc 42%, #fff);
            color: var(--dark)
        }

        .wrap {
            max-width: 1560px;
            margin: 0 auto;
            padding: 18px
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 22px;
            border-radius: 30px;
            background: linear-gradient(135deg, var(--red-dark), var(--red) 56%, #f45a0c);
            color: #fff;
            box-shadow: 0 22px 58px rgba(196, 18, 48, .22)
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px
        }

        .logo {
            width: 62px;
            height: 62px;
            border-radius: 19px;
            background: #fff;
            border: 2px solid var(--gold);
            display: grid;
            place-items: center;
            overflow: hidden
        }

        .logo img {
            width: 54px;
            height: 54px;
            object-fit: contain
        }

        .top h1 {
            margin: 0;
            font-size: clamp(26px, 3.4vw, 44px);
            line-height: 1;
            letter-spacing: -.055em
        }

        .top p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, .86);
            font-weight: 750;
            line-height: 1.45
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .btn {
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            font-weight: 950;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            white-space: nowrap
        }

        .btn.white {
            background: #fff;
            color: var(--red)
        }

        .btn.primary {
            background: linear-gradient(135deg, var(--gold), var(--orange));
            color: #2d2100
        }

        .btn.dark {
            background: #111827;
            color: #fff
        }

        .filter {
            display: flex;
            align-items: end;
            gap: 10px;
            flex-wrap: wrap;
            margin: 16px 0;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .05)
        }

        label {
            display: grid;
            gap: 6px;
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .065em;
            color: #475467
        }

        input[type=date] {
            height: 45px;
            border: 1px solid var(--line);
            border-radius: 15px;
            padding: 0 12px;
            font-weight: 850;
            color: var(--dark);
            background: #fff
        }

        .notice {
            padding: 12px 14px;
            border-radius: 16px;
            background: #fff3cd;
            border: 1px solid #ffe08a;
            color: #6b4a00;
            font-weight: 800;
            margin-bottom: 14px
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px
        }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 23px;
            padding: 15px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden
        }

        .card:before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--red))
        }

        .card small {
            display: block;
            color: var(--muted);
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .075em;
            font-weight: 950
        }

        .card b {
            display: block;
            margin-top: 8px;
            font-size: clamp(20px, 2vw, 30px);
            letter-spacing: -.045em;
            line-height: 1.08;
            overflow-wrap: anywhere
        }

        .card.green b {
            color: var(--green)
        }

        .card.red b {
            color: var(--red)
        }

        .card.blue b {
            color: var(--blue)
        }

        .card .sub {
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 750;
            line-height: 1.4
        }

        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 17px;
            box-shadow: var(--shadow);
            overflow: hidden
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 12px
        }

        .panel h2 {
            margin: 0;
            font-size: clamp(21px, 2.1vw, 29px);
            letter-spacing: -.04em
        }

        .panel .desc {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12.5px;
            font-weight: 750;
            line-height: 1.45
        }

        .badges {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 7px 10px;
            background: #f4f6fa;
            border: 1px solid var(--line);
            font-size: 11.5px;
            font-weight: 900;
            color: #475467
        }

        .badge.good {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0
        }

        .badge.bad {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca
        }

        .badge.warn {
            background: #fff7d6;
            color: #725100;
            border-color: #ffe08a
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 20px
        }

        table {
            width: 100%;
            min-width: 1200px;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px
        }

        th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafc;
            padding: 12px 10px;
            border-bottom: 1px solid var(--line);
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .065em;
            color: #475467;
            text-align: left
        }

        td {
            padding: 11px 10px;
            border-bottom: 1px solid #edf0f5;
            vertical-align: middle
        }

        tbody tr:hover {
            background: #fffaf1
        }

        tbody tr.row-profit {
            background: linear-gradient(90deg, rgba(22, 163, 74, .055), transparent 30%)
        }

        tbody tr.row-loss {
            background: linear-gradient(90deg, rgba(196, 18, 48, .07), transparent 35%)
        }

        tbody tr.row-empty {
            color: #98a2b3
        }

        tfoot td {
            font-weight: 950;
            background: #fff6de;
            border-top: 2px solid #f4c860;
            border-bottom: 0
        }

        .num {
            text-align: right;
            white-space: nowrap
        }

        .date-cell b {
            display: block;
            font-size: 13px
        }

        .date-cell span {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 11px
        }

        .money-profit {
            color: var(--green);
            font-weight: 950
        }

        .money-loss {
            color: var(--red);
            font-weight: 950
        }

        .status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 8px;
            font-size: 10.5px;
            font-weight: 950;
            white-space: nowrap
        }

        .status.profit {
            background: #dcfce7;
            color: #166534
        }

        .status.loss {
            background: #fee2e2;
            color: #991b1b
        }

        .status.even {
            background: #fff3cd;
            color: #745100
        }

        .status.empty {
            background: #f2f4f7;
            color: #667085
        }

        .warning {
            display: block;
            margin-top: 4px;
            color: #b54708;
            font-size: 10px;
            font-weight: 850
        }

        .formula {
            margin-top: 12px;
            padding: 11px 13px;
            border-radius: 16px;
            background: #f7f9fc;
            border: 1px solid var(--line);
            color: #475467;
            font-weight: 750;
            line-height: 1.5;
            font-size: 12px
        }

        .formula b {
            color: var(--dark)
        }

        .footer-print {
            display: none;
            margin-top: 26px
        }

        .sign-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 26px
        }

        .sign {
            text-align: center
        }

        .sign-space {
            height: 58px
        }

        @media(max-width:1250px) {
            .cards {
                grid-template-columns: repeat(3, 1fr)
            }
        }

        @media(max-width:850px) {
            .top {
                align-items: flex-start;
                flex-direction: column
            }

            .actions {
                justify-content: flex-start
            }

            .cards {
                grid-template-columns: repeat(2, 1fr)
            }

            .panel-head {
                flex-direction: column
            }

            .badges {
                justify-content: flex-start
            }
        }

        @media(max-width:580px) {
            .wrap {
                padding: 9px
            }

            .top {
                padding: 16px;
                border-radius: 23px
            }

            .logo {
                width: 52px;
                height: 52px
            }

            .logo img {
                width: 46px;
                height: 46px
            }

            .filter {
                display: grid
            }

            .filter label,
            .filter input,
            .filter .btn {
                width: 100%
            }

            .cards {
                grid-template-columns: 1fr
            }

            .panel {
                padding: 12px;
                border-radius: 21px
            }

            .actions {
                width: 100%
            }

            .actions .btn {
                width: 100%
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm
            }

            body {
                background: #fff;
                color: #111;
                font-size: 8.5pt
            }

            .wrap {
                max-width: none;
                padding: 0
            }

            .no-print {
                display: none !important
            }

            .top {
                background: #fff !important;
                color: #111;
                border: 1px solid #999;
                border-radius: 0;
                box-shadow: none;
                padding: 9px;
                margin-bottom: 7px
            }

            .logo {
                width: 44px;
                height: 44px;
                border-color: #aaa
            }

            .logo img {
                width: 39px;
                height: 39px
            }

            .top h1 {
                font-size: 20px
            }

            .top p {
                color: #444;
                margin-top: 4px;
                font-size: 9px
            }

            .cards {
                grid-template-columns: repeat(6, 1fr);
                gap: 4px;
                margin-bottom: 7px
            }

            .card {
                border: 1px solid #bbb;
                border-radius: 0;
                box-shadow: none;
                padding: 7px;
                break-inside: avoid
            }

            .card:before {
                display: none
            }

            .card small {
                font-size: 7px
            }

            .card b {
                font-size: 13px;
                margin-top: 3px
            }

            .card .sub {
                font-size: 7.5px;
                margin-top: 3px
            }

            .panel {
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
                overflow: visible
            }

            .panel-head {
                margin-bottom: 5px
            }

            .panel h2 {
                font-size: 14px
            }

            .panel .desc {
                font-size: 8px
            }

            .badges {
                display: none
            }

            .table-wrap {
                overflow: visible;
                border: 0;
                border-radius: 0
            }

            table {
                min-width: 0;
                font-size: 7.5px;
                border-collapse: collapse
            }

            th {
                position: static;
                background: #eee !important;
                color: #111;
                font-size: 6.8px;
                padding: 4px;
                border: 1px solid #aaa
            }

            td {
                padding: 4px;
                border: 1px solid #bbb
            }

            tbody tr.row-profit,
            tbody tr.row-loss {
                background: #fff !important
            }

            tfoot td {
                background: #eee !important;
                border: 1px solid #999
            }

            .status {
                padding: 2px 4px;
                font-size: 6.5px;
                border: 1px solid #bbb;
                background: #fff !important;
                color: #111 !important
            }

            .warning {
                font-size: 6px
            }

            .formula {
                font-size: 7.5px;
                padding: 5px;
                border-radius: 0
            }

            .footer-print {
                display: block;
                font-size: 8px
            }

            .sign-grid {
                margin-top: 15px
            }

            .sign-space {
                height: 36px
            }
        }
</style>
<div class="wrap">


        <form class="filter no-print" method="get">
            <label>Dari Tanggal<input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
            <label>Sampai Tanggal<input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
            <button class="btn primary" type="submit">Tampilkan Periode</button>
            <a class="btn white" href="#">Hari Ini</a>
            <a class="btn white" href="#">7 Hari</a>
            <a class="btn white" href="#">Bulan Ini</a>
        </form>


        <section class="cards">
    <?php
        $totalDays = count($closings);
        $activeDays = count(array_filter($closings, fn($c) => $c['total_revenue'] > 0));
        $totalOrders = array_sum(array_column($closings, "total_transactions"));
        $avgProfit = $activeDays > 0 ? ($pl['net_profit']??0) / $activeDays : 0;
        $hppRatio = ($pl['revenue'] > 0) ? number_format((($pl['hpp']??0)/$pl['revenue']*100), 1, ',', '.') : 0;
        $marginRatio = ($pl['revenue'] > 0) ? number_format((($pl['net_profit']??0)/$pl['revenue']*100), 1, ',', '.') : 0;
    ?>
    <div class="card green"><small>Total Omzet</small><b><?= rupiah($pl['revenue']??0) ?></b>
        <div class="sub"><?= number_format($totalOrders) ?> order paid</div>
    </div>
    <div class="card"><small>Total HPP</small><b><?= rupiah($pl['hpp']??0) ?></b>
        <div class="sub">Rasio HPP <?= $hppRatio ?>%</div>
    </div>
    <div class="card blue"><small>Laba Kotor</small><b><?= rupiah($pl['gross_profit']??0) ?></b>
        <div class="sub">Omzet dikurangi HPP</div>
    </div>
    <div class="card red"><small>Total Pengeluaran</small><b><?= rupiah($pl['expense']??0) ?></b>
        <div class="sub">Pengeluaran periode</div>
    </div>
    <div class="card green"><small>Laba/Rugi Bersih</small><b><?= rupiah($pl['net_profit']??0) ?></b>
        <div class="sub">Margin bersih <?= $marginRatio ?>%</div>
    </div>
    <div class="card"><small>Rata-rata Laba/Hari Aktif</small><b><?= rupiah($avgProfit) ?></b>
        <div class="sub"><?= $activeDays ?> hari aktif dari <?= $totalDays ?> hari</div>
    </div>
</section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>Tabel Resume Harian</h2>
                    <div class="desc">Semua tanggal pada periode ditampilkan, termasuk hari tanpa penjualan dan hari
                        yang hanya memiliki pengeluaran.</div>
                </div>
                <div class="badges">
                    <span class="badge good">Untung: <?= count(array_filter($closings, fn($c) => $c['net_profit'] > 0)) ?> hari</span>
                    <span class="badge bad">Rugi: <?= count(array_filter($closings, fn($c) => $c['net_profit'] < 0)) ?> hari</span>
                    <span class="badge">Impas: <?= count(array_filter($closings, fn($c) => $c['net_profit'] == 0)) ?> hari</span>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th class="num">Order</th>
                            <th class="num">Omzet</th>
                            <th class="num">HPP</th>
                            <th class="num">Rasio HPP</th>
                            <th class="num">Laba Kotor</th>
                            <th class="num">Pengeluaran</th>
                            <th class="num">Laba/Rugi Bersih</th>
                            <th class="num">Margin Bersih</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach ($closings as $i => $c): 
        $margin = $c['total_revenue'] > 0 ? ($c['net_profit'] / $c['total_revenue'] * 100) : 0;
        $hppPercent = $c['total_revenue'] > 0 ? ($c['total_hpp'] / $c['total_revenue'] * 100) : 0;
        $isProfit = $c['net_profit'] >= 0;
        $rowClass = $c['total_revenue'] == 0 ? 'row-empty' : ($isProfit ? 'row-profit' : 'row-loss');
        $statusClass = $c['total_revenue'] == 0 ? 'status empty' : ($isProfit ? 'status profit' : 'status loss');
        $statusText = $c['total_revenue'] == 0 ? 'Tidak ada aktivitas' : ($isProfit ? 'Untung' : 'Rugi');
        $timestamp = strtotime($c['business_date']);
        $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', $timestamp)];
    ?>
    <tr class="<?= $rowClass ?>">
        <td><?= $i + 1 ?></td>
        <td class="date-cell"><b><?= date('d M Y', $timestamp) ?></b><span><?= $hari ?></span></td>
        <td class="num"><?= number_format($c['total_transactions']) ?></td>
        <td class="num"><?= rupiah($c['total_revenue']) ?></td>
        <td class="num"><?= rupiah($c['total_hpp']) ?></td>
        <td class="num"><?= number_format($hppPercent, 1, ',', '.') ?>%</td>
        <td class="num"><?= rupiah($c['gross_profit']) ?></td>
        <td class="num"><?= rupiah($c['total_expense']) ?></td>
        <td class="num <?= $isProfit ? 'money-profit' : 'money-loss' ?>"><?= rupiah($c['net_profit']) ?></td>
        <td class="num"><?= number_format($margin, 1, ',', '.') ?>%</td>
        <td><span class="<?= $statusClass ?>"><?= $statusText ?></span></td>
    </tr>
    <?php endforeach; ?>
</tbody>
                    <tfoot>
        <tr>
            <td colspan="2">TOTAL PERIODE</td>
            <td class="num"><?= number_format(array_sum(array_column($closings, "total_transactions"))) ?></td>
            <td class="num"><?= rupiah($pl["revenue"]??0) ?></td>
            <td class="num"><?= rupiah($pl["hpp"]??0) ?></td>
            <td class="num"><?= ($pl["revenue"] > 0) ? number_format(($pl["hpp"]/$pl["revenue"]*100),1,",",".") : 0 ?>%</td>
            <td class="num"><?= rupiah($pl["gross_profit"]??0) ?></td>
            <td class="num"><?= rupiah($pl["expense"]??0) ?></td>
            <td class="num"><?= rupiah($pl["net_profit"]??0) ?></td>
            <td class="num"><?= ($pl["revenue"] > 0) ? number_format(($pl["net_profit"]/$pl["revenue"]*100),1,",",".") : 0 ?>%</td>
            <td><?= ($pl["net_profit"] >= 0) ? "UNTUNG" : "RUGI" ?></td>
        </tr></tfoot>
                </table>
            </div>

            <div class="formula">
                <b>Rumus konsisten:</b> Laba Kotor = Omzet − HPP. Laba/Rugi Bersih = Omzet − HPP − Pengeluaran. Hari
                tanpa omzet tetap dihitung rugi apabila terdapat pengeluaran pada tanggal tersebut.
            </div>
        </section>

        <div class="footer-print">
            <div>Periode: <b><?= date('d M Y', strtotime($from)) ?> s.d. <?= date('d M Y', strtotime($to)) ?></b> &bull; Dicetak <?= date('d/m/Y H:i') ?> WIB</div>
            <div class="sign-grid">
                <div class="sign"><b>Dicetak oleh</b>
                    <div class="sign-space"></div>(Admin D&#039;Celup Pasekon)
                </div>
                <div class="sign"><b>Mengetahui</b>
                    <div class="sign-space"></div>(________________________)
                </div>
            </div>
        </div>
    </div>
    <script>(function () {
            function text(el) { return (el.textContent || '').trim().toLowerCase(); }
            function iconFor(label) {
                if (label.includes('dashboard')) return '🏠';
                if (label.includes('produk') || label.includes('harga') || label.includes('hpp') || label.includes('resep') || label.includes('promo') || label.includes('voucher')) return '🍽️';
                if (label.includes('inventori') || label.includes('stok') || label.includes('persediaan') || label.includes('belanja') || label.includes('sinkron')) return '📦';
                if (label.includes('transaksi') || label.includes('order') || label.includes('kasir')) return '🧾';
                if (label.includes('laporan') || label.includes('keuangan') || label.includes('roi') || label.includes('pengeluaran') || label.includes('modal')) return '📊';
                if (label.includes('payment') || label.includes('pengaturan')) return '⚙️';
                if (label.includes('user')) return '👥';
                if (label.includes('logout')) return '🚪';
                return '•';
            }
            function groupFor(label) {
                if (label.includes('dashboard')) return 'Ringkasan Bisnis';
                if (label.includes('produk') || label.includes('harga') || label.includes('hpp') || label.includes('resep') || label.includes('promo') || label.includes('voucher')) return 'Produk & Penjualan';
                if (label.includes('inventori') || label.includes('stok') || label.includes('persediaan') || label.includes('belanja') || label.includes('sinkron')) return 'Persediaan & Belanja';
                if (label.includes('transaksi') || label.includes('order') || label.includes('kasir')) return 'Operasional Kasir';
                if (label.includes('laporan') || label.includes('keuangan') || label.includes('roi') || label.includes('pengeluaran') || label.includes('modal')) return 'Keuangan & Owner';
                if (label.includes('payment') || label.includes('pengaturan') || label.includes('user') || label.includes('logout')) return 'Sistem';
                return 'Lainnya';
            }
            function enhanceNav() {
                document.querySelectorAll('.nav').forEach(function (nav) {
                    if (nav.dataset.restoGrouped === '1') return;
                    var links = [].slice.call(nav.querySelectorAll(':scope > a'));
                    if (!links.length) return;
                    var frag = document.createDocumentFragment(); var last = '';
                    links.forEach(function (a) {
                        var label = text(a); var group = groupFor(label);
                        a.setAttribute('data-icon', iconFor(label));
                        if (group !== last) { var s = document.createElement('span'); s.className = 'nav-group-label'; s.textContent = group; frag.appendChild(s); last = group; }
                        frag.appendChild(a);
                    });
                    nav.appendChild(frag); nav.dataset.restoGrouped = '1';
                });
            }
            function addClock() {
                var top = document.querySelector('.topbar'); if (!top || top.querySelector('.pro-clock')) return;
                var clock = document.createElement('div'); clock.className = 'pro-clock'; top.appendChild(clock);
                function pad(n) { return String(n).padStart(2, '0') }
                function tick() {
                    var d = new Date();
                    clock.textContent = 'WIB ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()) + ' • ' + pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
                }
                tick(); setInterval(tick, 1000);
            }
            function enhanceTables() {
                document.querySelectorAll('table').forEach(function (t) { if (!t.parentElement.classList.contains('table-wrap') && !t.closest('.table-wrap')) { var w = document.createElement('div'); w.className = 'table-wrap'; t.parentNode.insertBefore(w, t); w.appendChild(t); } });
            }
            function init() { document.body.classList.add('resto-pro-admin'); enhanceNav(); addClock(); enhanceTables(); }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
        })();
    </script>
</div>

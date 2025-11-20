<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Kwitansi Infaq - {{ strtoupper($bulan) }}</title>
    <style>
        /* ============== PAGE ============== */
        @page {
            size: A6 portrait;
            margin: 8mm;
        }

        /* Dompdf hormati ini */
        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #222;
            line-height: 1.35;
        }

        /* ============== TOKENS ============== */
        :root {
            --brand: #622200;
            --muted: #666;
            --border: #e5e5e5;
        }

        /* ============== LAYOUT ============== */
        .wrap {
            width: 100%;
        }

        .muted {
            color: var(--muted);
        }

        .right {
            text-align: right;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mb-4 {
            margin-bottom: 4mm;
        }

        .mt-2 {
            margin-top: 2mm;
        }

        .mt-3 {
            margin-top: 3mm;
        }

        /* ============== HEADER ============== */
        .header {
            border-bottom: 0.6mm solid var(--brand);
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 18mm;
            vertical-align: top;
        }

        .logo {
            width: 16mm;
            height: 16mm;
            object-fit: contain;
            border-radius: 1.5mm;
        }

        .org-cell {
            vertical-align: top;
            padding-left: 3mm;
        }

        .org-title {
            font-size: 3.6mm;
            font-weight: 800;
            margin: 0;
            letter-spacing: .1mm;
        }

        .org-sub {
            font-size: 2.8mm;
            margin: 1mm 0 0;
            color: var(--muted);
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2mm;
        }

        .meta td {
            font-size: 2.8mm;
            padding: 0.8mm 0;
        }

        .meta .label {
            color: var(--muted);
            width: 22mm;
        }

        .meta .value {
            font-weight: 600;
        }

        /* ============== BOX DATA ============== */
        .box {
            border: .3mm solid var(--border);
            border-radius: 2mm;
            padding: 3mm;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 3mm;
            margin: 1.4mm 0;
        }

        .row .key {
            color: #333;
            font-size: 3mm;
        }

        .row .val {
            font-weight: 600;
            text-align: right;
            font-size: 3mm;
            max-width: 58mm;
        }

        .hr {
            border: none;
            border-top: .3mm solid var(--border);
            margin: 3mm 0;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: .4mm solid var(--brand);
            border-radius: 2mm;
            background: #fffaf6;
            padding: 2.2mm 2.6mm;
            font-weight: 700;
            font-size: 3.4mm;
            margin-top: 2mm;
        }

        /* ============== VERIFIKASI + TTD ============== */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
        }

        .sign-left {
            width: 36mm;
            vertical-align: top;
        }

        .sign-right {
            vertical-align: bottom;
            text-align: center;
        }

        .verify-label {
            font-size: 2.6mm;
            color: var(--muted);
        }

        .verify-url {
            font-size: 2.6mm;
            word-break: break-all;
        }

        .qr-box {
            margin-top: 1.6mm;
        }

        /* Pastikan SVG benar-benar kecil */
        .qr-box svg {
            width: 18mm;
            height: 18mm;
        }

        .sign-block {
            display: inline-block;
            padding: 2mm 3mm;
        }

        .sign-date {
            font-size: 2.6mm;
            color: var(--muted);
            margin-bottom: 1mm;
        }

        .sign-title {
            font-size: 2.8mm;
            color: var(--muted);
            margin-bottom: 9mm;
            line-height: 1.3;
        }

        .sign-name {
            font-size: 3mm;
            font-weight: 700;
            border-top: .3mm solid #999;
            display: inline-block;
            padding-top: 1.2mm;
            min-width: 38mm;
        }

        .sign-role {
            font-size: 2.6mm;
            color: var(--muted);
            margin-top: .6mm;
        }

        /* ============== FOOTNOTE ============== */
        .footnote {
            color: var(--muted);
            font-size: 2.6mm;
            margin-top: 3mm;
        }

        /* ============== WATERMARK ============== */
        .watermark {
            position: absolute;
            inset: 0;
            top: 40%;
            text-align: center;
            font-size: 8mm;
            color: #000;
            opacity: .06;
            transform: rotate(-17deg);
            pointer-events: none;
        }
    </style>
</head>

<body>
    <div class="wrap">

        @isset($watermark)
            <div class="watermark">{{ $watermark }}</div>
        @endisset

        <!-- HEADER -->
        <div class="header">
            <table class="grid">
                <tr>
                    <td class="logo-cell">
                        @if (!empty($logoDataUri))
                            <img class="logo" src="{{ $logoDataUri }}" alt="Logo Yayasan">
                        @endif
                    </td>
                    <td class="org-cell">
                        <h1 class="org-title">Yayasan Masjid Al Iman Sutorejo Indah</h1>
                        <p class="org-sub">
                            Sistem Baitul Maal — Kwitansi Infaq Bulanan Al Iman<br>
                            Alamat: {{ $alamatYayasan ?? 'Jl. Sutorejo Indah, Surabaya' }}<br>
                            Telp: {{ $teleponYayasan ?? '-' }} • Email: {{ $emailYayasan ?? '-' }}
                        </p>

                        <table class="meta">
                            <tr>
                                <td class="label">Kode Kwitansi</td>
                                <td class="value">{{ $kode }}</td>
                                <td class="label right">Tanggal</td>
                                <td class="value right">{{ $tanggal->format('d M Y H:i') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- DATA PEMBAYAR -->
        <div class="box">
            <div class="row">
                <div class="key">Nama</div>
                <div class="val">{{ $warga->nama }}</div>
            </div>
            <div class="row">
                <div class="key">RT / No</div>
                <div class="val">{{ $warga->rt }} / {{ $warga->no }}</div>
            </div>
            <div class="row">
                <div class="key">Alamat</div>
                <div class="val">{{ $warga->alamat }}</div>
            </div>

            <hr class="hr">

            <div class="row">
                <div class="key">Bulan</div>
                <div class="val text-capitalize">{{ ucfirst($bulan) }}</div>
            </div>

            <div class="total-line">
                <div>Nominal</div>
                <div>Rp {{ number_format($nominal, 0, ',', '.') }}</div>
            </div>

            <table class="sign-table">
                <tr>
                    <!-- KIRI: QR + URL -->
                    <td class="sign-left">
                        <div class="verify-label">Verifikasi keaslian:</div>
                        <div class="verify-url">{{ $verifyUrl ?? '' }}</div>
                        @if (!empty($qrSvg))
                            <div class="qr-box">{!! $qrSvg !!}</div>
                        @endif
                    </td>

                    <!-- KANAN: TTD -->
                    <td class="sign-right">
                        <div class="sign-block">
                            <div class="sign-date">Surabaya, {{ $tanggal->format('d M Y') }}</div>
                            <div class="sign-title">Mengetahui,<br>Bidang Sosial</div>
                            <div class="sign-name">{{ $ttdNama ?? '____________________' }}</div>
                            <div class="sign-role">{{ $ttdJabatan ?? 'Koordinator Bidang Sosial' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footnote">
            Simpan dokumen ini sebagai arsip. Untuk cetak/PDF: gunakan dialog cetak pada peramban Anda.
            Terima kasih atas partisipasi Anda.
        </div>
    </div>
</body>

</html>

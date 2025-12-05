@php
    /** @var \App\Models\TagihanSpp $tagihan */
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran SPP</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: monospace, Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
        }

        .receipt {
            width: 58mm;
            margin: 0 auto;
            padding: 8px 6px;
            /* sedikit turun + ada padding samping */
        }

        .logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo img {
            height: 75px;
        }

        .heading {
            text-align: center;
            font-weight: bold;
            margin: 2px 0;
            line-height: 1.3;
        }

        .subheading {
            text-align: center;
            font-size: 9px;
            margin: 0;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 6px 0;
            padding: 3px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        .meta,
        .info {
            font-size: 9.5px;
            line-height: 1.4;
            margin-top: 4px;
        }

        .row {
            display: flex;
            margin: 1px 0;
        }

        .label {
            min-width: 50px;
        }

        .colon {
            width: 8px;
            text-align: center;
        }

        .value {
            flex: 1;
        }

        .amount {
            font-weight: bold;
        }

        .footer-note,
        .signature {
            text-align: center;
            font-size: 9px;
            margin-top: 8px;
            line-height: 1.4;
        }

        .signature u {
            display: inline-block;
            margin-top: 14px;
        }
    </style>
</head>

<body>
    <div class="receipt">
        {{-- LOGO & KOP --}}
        <div class="logo">
            <img src="{{ $logo }}" alt="Logo Yayasan">
        </div>

        <h3 class="heading">KB-TK AL-IMAN SURABAYA</h3>
        <p class="subheading">Jl. Sutorejo Tengah No. 2-4, Surabaya</p>

        <div class="title">KWITANSI PEMBAYARAN SPP</div>

        {{-- META KWITANSI --}}
        <div class="meta">
            <p><span class="label">No</span>: {{ $nomorKwitansi }}</p>
            <p><span class="label">Tanggal</span>:
                {{ \Carbon\Carbon::parse($tagihan->tanggal)->format('d-m-Y') }}
            </p>
        </div>

        <div class="line"></div>

        {{-- DATA PEMBAYARAN --}}
        <div class="info">
            <p>
                <span class="label">Nama</span>:
                <strong>{{ optional($tagihan->student)->name ?? '-' }}</strong>
            </p>
            <p>
                <span class="label">Kelas</span>:
                {{ optional(optional($tagihan->student)->eduClass)->name ?? '-' }}
            </p>
            <p>
                <span class="label">Jumlah</span>:
                <span class="amount">
                    Rp {{ number_format($tagihan->jumlah ?? 0, 0, ',', '.') }}
                </span>
            </p>
            <p>
                <span class="label">Ket</span>:
                {{ $keterangan ?? '-' }}
            </p>
        </div>

        <div class="line"></div>

        {{-- CATATAN & TANDA TANGAN --}}
        <div class="footer-note">
            Telah diterima pembayaran tersebut<br>
            untuk keperluan administrasi sekolah.
        </div>

        <div class="signature">
            {{ now()->translatedFormat('d F Y') }}<br>
            <strong>Operator Sistem Keuangan KB/TK Al Iman</strong><br>
            <u>{{ optional(auth()->user())->name ?? '________________' }}</u>
        </div>

        <div class="footer-note" style="margin-top: 4px;">
            * Kwitansi ini dicetak otomatis dari sistem<br>
            dan sah tanpa tanda tangan basah.
        </div>
    </div>
</body>

</html>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran</title>
    <style>
        body {
            width: 58mm;
            margin: 0;
            padding: 5px;
            font-family: monospace, Arial, sans-serif;
            font-size: 10x;
            color: #000;
        }

        .logo {
            text-align: center;
            margin-bottom: 3px;
        }

        .logo img {
            height: 100px;
        }

        .heading {
            text-align: center;
            font-weight: bold;
            margin: 5px 0;
            padding: 4px 0;
        }

        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 5px 0;
            padding: 4px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        .info {
            margin-top: 5px;
            line-height: 1.4;
        }

        .info p {
            margin: 2px 0;
        }

        .qr-verifikasi {
            text-align: center;
            margin: 8px 0;
        }

        .qr-verifikasi img {
            height: 50px;
        }

        .footer-note,
        .signature {
            text-align: center;
            font-size: 9px;
            margin-top: 10px;
        }

        .signature u {
            display: inline-block;
            margin-top: 25px;
        }

        hr {
            border: none;
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="logo">
        <img src="{{ $logo }}" alt="Logo Yayasan">
    </div>

    <h3 class="heading">KB-TK AL-IMAN SURABAYA</h3>

    <div class="title">KWITANSI PEMBAYARAN</div>
    <p>No: {{ $nomorKwitansi }}</p>

    <div class="info">
        <p>Nama: <strong>{{ $tagihan->student->name }}</strong></p>
        <p>Kelas: {{ $tagihan->student->edu_class->name ?? '-' }}</p>
        <p>Tanggal: {{ \Carbon\Carbon::parse($tagihan->tanggal)->format('d-m-Y') }}</p>
        <p>Jumlah: <strong>Rp {{ number_format($tagihan->jumlah, 0, ',', '.') }}</strong></p>
        <p>Ket: {{ $keterangan }}</p>
    </div>

    <div class="qr-verifikasi">
        <p class="footer-note">Kode QR Verifikasi:</p>
        <img src="{{ $qrPath }}" alt="QR Code">
    </div>

    <hr>

    <div class="footer-note">
        Telah diterima pembayaran tersebut<br>
        untuk keperluan administrasi sekolah.
    </div>

    <div class="signature">
        {{ now()->translatedFormat('d F Y') }}<br>
        <strong>Bendahara</strong><br>
        <u>____________________</u>
    </div>

    <div class="footer-note" style="margin-top: 6px;">
        * Kwitansi dicetak otomatis dan sah tanpa tanda tangan jika sesuai QR
    </div>

</body>

</html>

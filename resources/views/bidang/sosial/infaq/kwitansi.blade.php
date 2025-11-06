<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Kwitansi Infaq - {{ strtoupper($bulan) }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 18px;
            position: relative;
        }

        .wrap {
            max-width: 720px;
            margin: 0 auto;
            position: relative;
        }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .box {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 14px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
        }

        .muted {
            color: #666;
            font-size: 12px;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
        }

        .qr {
            width: 120px;
            height: 120px;
        }

        .watermark {
            position: absolute;
            left: 0;
            right: 0;
            top: 35%;
            text-align: center;
            font-size: 28px;
            color: #000;
            opacity: 0.06;
            transform: rotate(-18deg);
        }

        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="wrap">
        @isset($watermark)
            <div class="watermark">{{ $watermark }}</div>
        @endisset

        <div class="head">
            <div>
                <div class="title">Kwitansi Infaq Bulanan</div>
                <div class="muted">{{ $tanggal->format('d M Y H:i') }}</div>
            </div>
            <div class="muted">{{ $kode }}</div>
        </div>

        <div class="box">
            <div class="row">
                <div>Nama</div>
                <div><strong>{{ $warga->nama }}</strong></div>
            </div>
            <div class="row">
                <div>RT / No</div>
                <div>{{ $warga->rt }} / {{ $warga->no }}</div>
            </div>
            <div class="row">
                <div>Alamat</div>
                <div style="max-width: 380px; text-align: right;">{{ $warga->alamat }}</div>
            </div>
            <hr>
            <div class="row">
                <div>Bulan</div>
                <div class="text-capitalize">{{ ucfirst($bulan) }}</div>
            </div>
            <div class="row total">
                <div>Nominal</div>
                <div>Rp {{ number_format($nominal, 0, ',', '.') }}</div>
            </div>
            <hr>
            <div class="row" style="align-items:center;">
                <div>
                    <div class="muted">Verifikasi keaslian:</div>
                    <div style="font-size: 12px; max-width: 380px; word-break: break-all;">{{ $verifyUrl ?? '' }}</div>
                </div>
                <div>
                    {{-- render QR SVG --}}
                    @if (!empty($qrSvg))
                        {!! $qrSvg !!}
                    @endif
                </div>
            </div>
        </div>

        <div class="muted" style="margin-top:10px;">
            Simpan sebagai PDF melalui dialog cetak. Terima kasih atas partisipasi Anda.
        </div>
    </div>
</body>

</html>

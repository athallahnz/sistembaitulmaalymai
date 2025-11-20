{{-- resources/views/bidang/pengajuan/pdf.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <title>Laporan Pengajuan Dana</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 16pt;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 5px;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .detail-table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .total-row td {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>LAPORAN PENGAJUAN DANA</h1>
        <h2>{{ $pengajuan->judul }}</h2>
        @php
            // Ambil bulan dalam bentuk angka (1-12)
            $bulanAngka = $pengajuan->created_at->month;

            // Konversi angka bulan ke Romawi
            $bulanRomawi = App\Models\PengajuanDana::numberToRoman($bulanAngka);

            // Ambil tahun (4 digit)
            $tahun = $pengajuan->created_at->year;
        @endphp

        <small>BID.KEU/{{ $pengajuan->id }}/{{ $bulanRomawi }}/{{ $tahun }}</small>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Status:</strong> {{ $pengajuan->status }}<br>
                <strong>Diajukan Oleh:</strong> {{ $pengajuan->pembuat->name ?? '-' }}<br>
                <strong>Bidang:</strong> {{ $pengajuan->bidang->name ?? '-' }}
            </td>
            <td style="width: 50%;">
                <strong>Tanggal Pengajuan:</strong> {{ $pengajuan->created_at->format('d F Y') }}<br>
                <strong>Verifikasi Oleh:</strong> {{ $pengajuan->validator->name ?? '-' }}<br>
                <strong>Dicairkan Oleh:</strong> {{ $pengajuan->treasurer->name ?? '-' }}
            </td>
        </tr>
    </table>

    <h3>Rincian Anggaran</h3>
    <table class="detail-table">
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 30%;">Keterangan Item</th>
                <th style="width: 25%;">Pos Akun (CoA)</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%;">Harga Satuan</th>
                <th style="width: 15%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pengajuan->details as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $item->keterangan_item }}</td>
                    <td>{{ $item->akunKeuangan->kode_akun ?? '-' }} - {{ $item->akunKeuangan->nama_akun ?? '-' }}</td>
                    <td style="text-align: center;">{{ number_format($item->kuantitas, 0, ',', '.') }}</td>
                    <td style="text-align: right;">Rp {{ number_format($item->harga_pokok, 0, ',', '.') }}</td>
                    <td style="text-align: right;">Rp {{ number_format($item->jumlah_dana, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align: right;">TOTAL KESELURUHAN</td>
                <td style="text-align: right;">Rp {{ number_format($pengajuan->total_jumlah, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px;">
        <p>Demikian laporan pengajuan dana ini dibuat untuk ditindaklanjuti.</p>
        <p><strong>Deskripsi/Catatan:</strong> {{ $pengajuan->deskripsi }}</p>
    </div>

</body>

</html>

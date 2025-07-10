<!DOCTYPE html>
<html>
<head>
    <title>Kwitansi Pembayaran</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        h2, h3 { margin: 0; }
    </style>
</head>
<body>
    <h2>Kwitansi Pembayaran</h2>
    <p>Nama Siswa: <strong>{{ $student->nama }}</strong></p>
    <p>NISN: {{ $student->nisn }}</p>

    <h3>Ringkasan</h3>
    <table>
        <tr>
            <th>Total Biaya</th>
            <th>Total Dibayar</th>
            <th>Sisa Tanggungan</th>
        </tr>
        <tr>
            <td>Rp {{ number_format($totalBiaya, 0, ',', '.') }}</td>
            <td>Rp {{ number_format($totalBayar, 0, ',', '.') }}</td>
            <td>Rp {{ number_format($sisa, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h3>Riwayat Pembayaran</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $index => $p)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') }}</td>
                    <td>Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Belum ada pembayaran</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 50px;">Dicetak pada: {{ now()->format('d-m-Y H:i') }}</p>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Arus Kas</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        h2 { text-align: center; }
    </style>
</head>
<body>

<h2>Laporan Arus Kas</h2>
<p><strong>Periode:</strong> {{ $startDate }} - {{ $endDate }}</p>

@if(Auth::user()->role === 'Bidang')
    <p><strong>Bidang:</strong> {{ auth()->user()->bidang->name }}</p>
@endif

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Kategori</th>
            <th>Arus Kas Masuk</th>
            <th>Arus Kas Keluar</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Operasional</strong></td>
            <td>Rp {{ number_format($kasOperasionalMasuk, 2, ',', '.') }}</td>
            <td>Rp {{ number_format($kasOperasionalKeluar, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Investasi</strong></td>
            <td>Rp {{ number_format($kasInvestasiMasuk, 2, ',', '.') }}</td>
            <td>Rp {{ number_format($kasInvestasiKeluar, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Pendanaan</strong></td>
            <td>Rp {{ number_format($kasPendanaanMasuk, 2, ',', '.') }}</td>
            <td>Rp {{ number_format($kasPendanaanKeluar, 2, ',', '.') }}</td>
        </tr>
    </tbody>
    <tfoot class="table-light fw-bold">
        <tr>
            <td><strong>Total</strong></td>
            <td><strong>Rp {{ number_format($totalKasMasuk, 2, ',', '.') }}</strong></td>
            <td><strong>Rp {{ number_format($totalKasKeluar, 2, ',', '.') }}</strong></td>
        </tr>
    </tfoot>
</table>

</body>
</html>

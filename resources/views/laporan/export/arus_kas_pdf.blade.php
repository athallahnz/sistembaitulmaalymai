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
    <p><strong>Bidang:</strong> {{ Auth::user()->bidang_name }}</p>
@endif

<table>
    <tr>
        <th>Penerimaan</th>
        <th>Pengeluaran</th>
    </tr>
    <tr>
        <td>Rp {{ number_format($penerimaan, 0, ',', '.') }}</td>
        <td>Rp {{ number_format($pengeluaran, 0, ',', '.') }}</td>
    </tr>
</table>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0 auto;
            width: 80%;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table, .table th, .table td {
            border: 1px solid #000;
        }
        .table th, .table td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kwitansi Transaksi</h1>
        <p><strong>Tanggal:</strong> {{ $tanggal_transaksi }}</p>
    </div>
    <table class="table">
        <tr>
            <th>Kode Transaksi</th>
            <td>{{ $transaksi->kode_transaksi }}</td>
        </tr>
        <tr>
            <th>Jenis Transaksi</th>
            <td>{{ ucfirst($transaksi->type) }}</td>
        </tr>
        <tr>
            <th>Akun</th>
            <td>{{ $transaksi->akunKeuangan ? $transaksi->akunKeuangan->nama_akun : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Sub Akun</th>
            <td>{{ $transaksi->parentAkunKeuangan ? $transaksi->parentAkunKeuangan->nama_akun : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Deskripsi</th>
            <td>{{ $transaksi->deskripsi }}</td>
        </tr>
        <tr>
            <th>Jumlah</th>
            <td>Rp {{ number_format($transaksi->amount, 2) }}</td>
        </tr>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Export Transaksi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            /* Set the font size to 12px */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-before: auto;
            page-break-after: auto;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 6px;
            /* Reduced padding for a more compact layout */
            text-align: left;
            font-size: 12px;
            /* Ensure table content has a consistent font size */
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>Daftar Transaksi</h2>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Tanggal</th>
                <th>Kode Transaksi</th>
                <th>Jenis Transaksi</th>
                <th>Akun</th>
                <th>Sub Akun</th>
                <th>Deskripsi</th>
                <th>Jumlah</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaksis as $transaksi)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $transaksi->tanggal_transaksi }}</td>
                    <td>{{ $transaksi->kode_transaksi }}</td>
                    <td>{{ $transaksi->type }}</td>
                    <td>{{ $transaksi->akunKeuangan ? $transaksi->akunKeuangan->nama_akun : 'N/A' }}</td>
                    <td>{{ $transaksi->parentAkunKeuangan ? $transaksi->parentAkunKeuangan->nama_akun : 'N/A' }}</td>
                    <td>{{ $transaksi->deskripsi }}</td>
                    <td>{{ number_format($transaksi->amount) }}</td>
                    <td>{{ number_format($transaksi->saldo) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>

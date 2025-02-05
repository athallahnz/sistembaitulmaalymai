@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Laporan Transaksi Bank</h1>
        <p><strong>ID Akun Bank:</strong> 102</p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kode Transaksi</th>
                    <th>Deskripsi</th>
                    <th>Type</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transaksiBank as $transaksi)
                    <tr>
                        <td>{{ $transaksi->tanggal_transaksi }}</td>
                        <td>{{ $transaksi->kode_transaksi }}</td>
                        <td>{{ $transaksi->deskripsi }}</td>
                        <td>{{ ucfirst($transaksi->type) }}</td>
                        <td>{{ number_format($transaksi->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Total Saldo Bank: Rp{{ number_format($totalSaldoBank, 2) }}</h3>
    </div>
@endsection

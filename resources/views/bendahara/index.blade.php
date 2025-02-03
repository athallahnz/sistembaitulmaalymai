@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Selamat Datang, Bidang {{ auth()->user()->role }}!</h1>
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="icon bi bi-cash-coin"></div>
                <h5>Total Saldo Keuangan</h5>
                <h5>Yayasan Masjid Al Iman</h5>
                <h3 class="value {{ $totalSaldo >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</h3>
                <div class="description">
                    Update terakhir: {{ $lastUpdate ? $lastUpdate->translatedFormat('d F Y H:i') : 'Belum ada transaksi' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

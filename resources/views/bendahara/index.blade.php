@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Selamat Datang, <strong>{{ auth()->user()->role }} Yayasan!</strong></h1>
        <div class="row">
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-gem"></div>
                    <h5>Total Nilai Kekayaan</h5>
                    <h5>Yayasan</h5>
                    <h3 class="value {{ $totalKeuanganBidang >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($totalKeuanganBidang) }}
                    </h3>
                </div>
            </div>
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-cash-coin"></div>
                    <h5>Total Saldo Kas</h5>
                    <h5>Seluruh Bidang</h5>
                    <h3 class="value {{ $totalseluruhKas >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($totalseluruhKas) }}
                    </h3>
                </div>
            </div>
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-bank"></div>
                    <h5>Total Saldo Bank</h5>
                    <h5>Seluruh Bidang</h5>
                    <h3 class="value {{ $totalSeluruhBank >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($totalSeluruhBank) }}
                    </h3>
                </div>
            </div>
        </div>
    </div>
@endsection

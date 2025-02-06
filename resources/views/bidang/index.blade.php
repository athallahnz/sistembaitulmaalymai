@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid p-4">
        <h1 class="mb-4">Selamat Datang, di Dashboard {{ auth()->user()->bidang_name }}!</h1>
        <h4 class="mb-4">Nilai Asset, Bidang {{ auth()->user()->bidang_name }}!</h4>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-cash-coin"></div>
                    <h5>Nilai Kekayaan</h5>
                    <div class="value {{ $lastSaldo >= 0 ? 'positive' : 'negative' }}">{{ number_format($lastSaldo) }}</div>
                    <div class="description">untuk bidang {{ auth()->user()->bidang_name }}!</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-credit-card"></div>
                    <h5>Transaksi</h5>
                    <div class="value">{{ $jumlahTransaksi }}</div>
                    <div class="description">Jumlah Transaksi bulan ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-cash"></div>
                    <h5>Saldo Kas</h5>
                    <div class="value {{ $totalKas >= 0 ? 'positive' : 'negative' }}">{{ number_format($totalKas) }}</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-bank"></div>
                    <h5>Saldo Bank</h5>
                    <div class="value {{ $totalSaldoBank >= 0 ? 'positive' : 'negative' }}">{{ number_format($totalSaldoBank) }}</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-building"></div>
                    <h5>Tanah Bangunan</h5>
                    <div class="value {{ $jumlahTanahBangunan >= 0 ? 'positive' : 'negative' }}">{{ number_format($jumlahTanahBangunan) }}</div>
                    <div class="description">Total Nilai Asset</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-truck"></div>
                    <h5>Inventaris</h5>
                    <div class="value {{ $jumlahInventaris >= 0 ? 'positive' : 'negative' }}">{{ number_format($jumlahInventaris) }}</div>
                    <div class="description">Total Nilai Inventaris</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-wallet"></div>
                    <h5>Piutang</h5>
                    <div class="value {{ $jumlahPiutang >= 0 ? 'positive' : 'negative' }}">{{ number_format($jumlahPiutang) }}</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div>
            {{-- <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-bank"></div>
                    <h5>Bank</h5>
                    <div class="value">{{ number_format($jumlahBank) }}</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div> --}}
        </div>
        <h4 class="mb-4">Beban & Biaya, Bidang {{ auth()->user()->bidang_name }}!</h4>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-percent"></div>
                    <h5>Nilai Penyusutan Asset</h5>
                    <div class="value negative">{{ number_format($jumlahPenyusutanAsset) }}</div>
                    <div class="description">untuk bidang {{ auth()->user()->bidang_name }}!</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-cash"></div>
                    <h5>Beban Gaji dan Upah</h5>
                    <div class="value negative">{{ number_format($jumlahBebanGaji) }}</div>
                    <div class="description">Jumlah Transaksi bulan ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-tools"></div>
                    <h5>Beban Pemeliharaan</h5>
                    <div class="value negative">0</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-calendar-check"></div>
                    <h5>Biaya Kegiatan Siswa</h5>
                    <div class="value negative">{{ number_format($jumlahBiayaKegiatan) }}</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-stack"></div>
                    <h5>Biaya Perlengkapan</h5>
                    <div class="value negative">0</div>
                    <div class="description">Total Nilai Asset</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-trash"></div>
                    <h5>Biaya Habis Pakai</h5>
                    <div class="value negative">0</div>
                    <div class="description">Total Nilai Inventaris</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-gear"></div>
                    <h5>Biaya Operasional</h5>
                    <div class="value negative">{{ number_format($jumlahBiayaOperasional) }}</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="icon bi bi-gear"></div>
                    <h5>Biaya Operasional Lain</h5>
                    <div class="value negative">0</div>
                    <div class="description">Total s/d Bulan ini</div>
                </div>
            </div>
        </div>

    </div>
    {{-- <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        Logout
    </a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form> --}}
</div>

@endsection

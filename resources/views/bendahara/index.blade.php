@extends('layouts.app')
@section('title', 'Dashboard Bendahara')
@section('content')
    <div class="container">
        <h1 class="mb-4">Selamat Datang, <strong>{{ auth()->user()->role }} Yayasan!</strong></h1>
        <div class="row">
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-gem"></div>
                    <h5>Total Nilai Kekayaan</h5>
                    <h5>Yayasan</h5>
                    <h3 class="value {{ $totalKeuanganSemuaBidang >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($totalKeuanganSemuaBidang) }}
                    </h3>
                </div>
            </div>
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-cash-coin"></div>
                    <h5>Total Saldo Kas</h5>
                    <h5>Seluruh Bidang</h5>
                    <h3 class="value {{ $saldoKasTotal >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($saldoKasTotal) }}
                    </h3>
                </div>
            </div>
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-bank"></div>
                    <h5>Total Saldo Bank</h5>
                    <h5>Seluruh Bidang</h5>
                    <h3 class="value {{ $saldoBankTotal >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($saldoBankTotal) }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <h4 class="mb-4">Nilai Asset, Yayasan!</h4>
            <div class="row">
                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-building"></div>
                        <h5>Tanah Bangunan</h5>
                        <div class="value {{ $totalTanahBangunan >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($totalTanahBangunan) }}</div>
                        <div class="description">Total Nilai Asset</div>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-truck"></div>
                        <h5>Inventaris</h5>
                        <div class="value {{ $totalInventaris >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($totalInventaris) }}</div>
                        <div class="description">Total Nilai Inventaris</div>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-wallet"></div>
                            <h5>Piutang</h5>
                            <div class="value {{ $totalPiutang >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPiutang) }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
            <h4 class="mb-4">Nilai Liability, Yayasan!</h4>
            <div class="row">
                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Hutang</h5>
                            <div class="value {{ $totalHutang >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalHutang) }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Donasi (Pendapatan)</h5>
                            <div class="value {{ $totalDonasi >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalDonasi) }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Pendapatan Belum Diterima</h5>
                            <div class="value {{ $totalPendapatanBelumDiterima >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanBelumDiterima) }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
            <h4 class="mb-4">Nilai Beban & Biaya, Yayasan!</h4>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-percent"></div>
                        <h5>Nilai Penyusutan Asset</h5>
                        <div class="value negative">{{ number_format($totalPenyusutanAsset) }}</div>
                        <div class="description">untuk bidang {{ auth()->user()->bidang_name }}!</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Beban Gaji dan Upah</h5>
                            <div class="value negative">{{ number_format($totalBebanGaji) }}</div>
                            <div class="description">total Transaksi bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-tools"></div>
                            <h5>Beban Pemeliharaan</h5>
                            <div class="value negative">0</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 304]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-calendar-check"></div>
                            <h5>Biaya Kegiatan Siswa</h5>
                            <div class="value negative">{{ number_format($totalBiayaKegiatan) }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-stack"></div>
                            <h5>Biaya Perlengkapan</h5>
                            <div class="value negative">0</div>
                            <div class="description">Total Nilai Asset</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-trash"></div>
                            <h5>Biaya Habis Pakai</h5>
                            <div class="value negative">0</div>
                            <div class="description">Total Nilai Inventaris</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 303]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-gear"></div>
                            <h5>Biaya Operasional</h5>
                            <div class="value negative">{{ number_format($totalBiayaOperasional) }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-gear"></div>
                            <h5>Biaya Operasional Lain</h5>
                            <div class="value negative">0</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')
@section('title', 'Dashboard Bendahara')
@section('content')
    <div class="container">
        <h1 class="mb-4">Selamat Datang, <strong>{{ auth()->user()->role }} Yayasan!</strong></h1>
        <div class="row">
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-gem"></div>
                    <h5 class="w-50">Total Nilai Kekayaan Yayasan</h5>
                    <h3 class="value {{ $totalKeuanganSemuaBidang >= 0 ? 'positive' : 'negative' }}">
                        Rp <span class="hidden-value"
                            style="display: none;">{{ number_format($totalKeuanganSemuaBidang, 0, ',', '.') }}</span>
                        <span class="masked-value">***</span>
                        <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                            onclick="toggleVisibility(this)"></i>
                    </h3>
                </div>
            </div>
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-cash-coin"></div>
                    <h5 class="w-50">Total Saldo Kas Seluruh Bidang</h5>
                    <h3 class="value {{ $saldoKasTotal >= 0 ? 'positive' : 'negative' }}">
                        Rp <span class="hidden-value"
                            style="display: none;">{{ number_format($saldoKasTotal, 0, ',', '.') }}</span>
                        <span class="masked-value">***</span>
                        <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                            onclick="toggleVisibility(this)"></i>
                    </h3>
                </div>
            </div>
            <div class="col-md-4 mb-5">
                <div class="card">
                    <div class="icon bi bi-bank"></div>
                    <h5 class="w-50">Total Saldo Bank Seluruh Bidang</h5>
                    <h3 class="value {{ $saldoBankTotal >= 0 ? 'positive' : 'negative' }}">
                        Rp <span class="hidden-value"
                            style="display: none;">{{ number_format($saldoBankTotal, 0, ',', '.') }}</span>
                        <span class="masked-value">***</span>
                        <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                            onclick="toggleVisibility(this)"></i>
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
                            {{ number_format($totalTanahBangunan, 0, ',', '.') }}</div>
                        <div class="description">Total Nilai Asset</div>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-truck"></div>
                        <h5>Inventaris</h5>
                        <div class="value {{ $totalInventaris >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($totalInventaris, 0, ',', '.') }}</div>
                        <div class="description">Total Nilai Inventaris</div>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-wallet"></div>
                            <h5>Piutang</h5>
                            <div class="value {{ $totalPiutang >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPiutang, 0, ',', '.') }}</div>
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
                                {{ number_format($totalHutang, 0, ',', '.') }}</div>
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
                                {{ number_format($totalDonasi, 0, ',', '.') }}</div>
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
                                {{ number_format($totalPendapatanBelumDiterima, 0, ',', '.') }}</div>
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
                        <div class="value negative">{{ number_format($totalPenyusutanAsset, 0, ',', '.') }}</div>
                        <div class="description">untuk bidang {{ auth()->user()->bidang_name }}!</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Beban Gaji dan Upah</h5>
                            <div class="value negative">{{ number_format($totalBebanGaji, 0, ',', '.') }}</div>
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
<script>
    function toggleVisibility(icon) {
        let parent = icon.closest('.card'); // Cari elemen terdekat yang memiliki class 'card'
        let hiddenValue = parent.querySelector('.hidden-value');
        let maskedValue = parent.querySelector('.masked-value');

        if (hiddenValue.style.display === 'none') {
            hiddenValue.style.display = 'inline';
            maskedValue.style.display = 'none';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            hiddenValue.style.display = 'none';
            maskedValue.style.display = 'inline';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>

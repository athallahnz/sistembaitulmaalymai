@extends('layouts.app')
@section('title', 'Dashboard Bidang')
@section('content')
    <div class="container">
        <h1 class="mb-2"><strong>Selamat Datang, di Dashboard {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</strong>
        </h1>
        <div class="container-fluid p-4">
            <h4 class="mb-4">Nilai Asset, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-cash-coin"></div>
                        <h6><strong>Nilai Kekayaan</strong></h6>
                        <div class="value {{ $totalKeuanganBidang >= 0 ? 'positive' : 'negative' }}">
                            Rp <span class="hidden-value"
                                style="display: none;">{{ number_format($totalKeuanganBidang, 0, ',', '.') }}</span>
                            <span class="masked-value">***</span>
                            <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                                onclick="toggleVisibility(this)"></i>
                        </div>
                        <div class="description">untuk bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-credit-card"></div>
                        <h6><strong>Transaksi</strong></h6>
                        <div class="value">{{ $jumlahTransaksi }}</div>
                        <div class="description">Jumlah Transaksi bulan ini</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-cash"></div>
                        <h6><strong>Saldo Kas</strong></h6>
                        <div class="value {{ $saldoKas >= 0 ? 'positive' : 'negative' }}">
                            Rp <span class="hidden-value"
                                style="display: none;">{{ number_format($saldoKas, 0, ',', '.') }}</span>
                            <span class="masked-value">***</span>
                            <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                                onclick="toggleVisibility(this)"></i>
                        </div>
                        <div class="description">Total s/d Bulan ini</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-bank"></div>
                        <h6><strong>Saldo Bank</strong></h6>
                        <div class="value {{ $saldoBank >= 0 ? 'positive' : 'negative' }}">
                            Rp <span class="hidden-value"
                                style="display: none;">{{ number_format($saldoBank, 0, ',', '.') }}</span>
                            <span class="masked-value">***</span>
                            <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                                onclick="toggleVisibility(this)"></i>
                        </div>
                        <div class="description">Total s/d Bulan ini</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-building"></div>
                        <h6><strong>Tanah Bangunan</strong></h6>
                        <div class="value {{ $jumlahTanahBangunan >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($jumlahTanahBangunan, 0, ',', '.') }}</div>
                        <div class="description">Total Nilai Asset</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-truck"></div>
                        <h6><strong>Inventaris</strong></h6>
                        <div class="value {{ $jumlahInventaris >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($jumlahInventaris, 0, ',', '.') }}</div>
                        <div class="description">Total Nilai Inventaris</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 103]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-wallet"></div>
                            <h6><strong>Piutang</strong></h6>
                            <div class="value {{ $jumlahPiutang >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahPiutang, 0, ',', '.') }}</span>
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
            <h4 class="mb-4">Liability, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h6><strong>Hutang</strong></h6>
                            <div class="value {{ $jumlahHutang >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahHutang, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 202]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h6><strong>Donasi (Pendapatan)</strong></h6>
                            <div class="value {{ $jumlahDonasi >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahDonasi, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['type' => 'pendapatan belum diterima']) }}"
                        class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h6><strong>Pendapatan Belum Diterima</strong></h6>
                            <div class="value {{ $jumlahPendapatanBelumDiterima >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahPendapatanBelumDiterima, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 310]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h6><strong>Biaya Dibayar di Muka</strong></h6>
                            <div class="value {{ $jumlahBiayadibayardimuka >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahBiayadibayardimuka, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
            <h4 class="mb-4">Beban & Biaya, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-percent"></div>
                        <h6><strong>Nilai Penyusutan Asset</strong></h6>
                        <div class="value negative">{{ number_format($jumlahPenyusutanAsset, 0, ',', '.') }}</div>
                        <div class="description">untuk bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h6><strong>Beban Gaji dan Upah</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBebanGaji, 0, ',', '.') }}</div>
                            <div class="description">Jumlah Transaksi bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 303]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-gear"></div>
                            <h6><strong>Biaya Operasional</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaOperasional, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 304]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-calendar-check"></div>
                            <h6><strong>Biaya Kegiatan Siswa</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaKegiatanSiswa, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 305]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-tools"></div>
                            <h6><strong>Beban Pemeliharaan</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaPemeliharaan, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 306]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-people-fill"></div> {{-- Ikon untuk "Biaya Sosial" --}}
                            <h6><strong>Biaya Sosial</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaSosial, 0, ',', '.') }}</div>
                            <div class="description">Total Nilai Inventaris</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 307]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-box-seam"></div> {{-- Ikon untuk "Biaya Per. Extra" --}}
                            <h6><strong>Biaya Per. Extra</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaPerlengkapanExtra, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Nilai Asset</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 308]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi bi-incognito"></div> {{-- Ikon untuk "Biaya Seragam" --}}
                            <h6><strong>Biaya Seragam</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaSeragam, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 309]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi bi-incognito"></div> {{-- Ikon untuk "Biaya Seragam" --}}
                            <h6><strong>Biaya Peningkatan SDM</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaPeningkatanSDM, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
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
@endpush

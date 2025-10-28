@extends('layouts.app')
@section('title', 'Dashboard Bendahara')

@section('content')
    <div class="container">
        <h1 class="mb-4">Selamat Datang, <strong>{{ auth()->user()->role }} Yayasan!</strong></h1>

        {{-- ========================  DASHBOARD BENDAHARA (scope: bidang aktif)  ======================== --}}
        <h3 class="mb-4 d-flex">
            <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#dataKeuanganbendahara" role="button"
                aria-expanded="true" aria-controls="dataKeuanganbendahara">
                Dashboard Keuangan <strong>{{ auth()->user()->role }}!</strong>
                <i class="bi bi-chevron-down ms-2 chevron"></i>
            </a>
        </h3>

        <div class="collapse show" id="dataKeuanganbendahara">
            <h4 class="mb-4">Nilai Asset, {{ auth()->user()->role }}!</h4>
            <div class="row">

                {{-- Nilai Kekayaan (Kas+Bank bidang aktif) --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-cash-coin"></div>
                        <h5>Nilai Kekayaan</h5>
                        <div class="value {{ $totalKeuanganBidang >= 0 ? 'positive' : 'negative' }}">
                            Rp <span class="hidden-value" style="display:none;">
                                {{ number_format($totalKeuanganBidang, 0, ',', '.') }}
                            </span>
                            <span class="masked-value">***</span>
                            <i class="bi bi-eye toggle-eye" style="cursor:pointer;margin-left:10px;"
                                onclick="toggleVisibility(this)"></i>
                        </div>
                        <div class="description">untuk {{ auth()->user()->role }}!</div>
                    </div>
                </div>

                {{-- Jumlah transaksi bulan ini --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-credit-card"></div>
                        <h5>Transaksi</h5>
                        <div class="value">
                            {{ $jumlahTransaksi }}
                        </div>
                        <div class="description">Jumlah Transaksi bulan ini</div>
                    </div>
                </div>

                {{-- Saldo Kas --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-cash"></div>
                        <h5>Saldo Kas</h5>
                        <div class="value {{ $saldoKas >= 0 ? 'positive' : 'negative' }}">
                            Rp <span class="hidden-value" style="display:none;">
                                {{ number_format($saldoKas, 0, ',', '.') }}
                            </span>
                            <span class="masked-value">***</span>
                            <i class="bi bi-eye toggle-eye" style="cursor:pointer;margin-left:10px;"
                                onclick="toggleVisibility(this)"></i>
                        </div>
                        <div class="description">Total s/d Bulan ini</div>
                    </div>
                </div>

                {{-- Saldo Bank --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-bank"></div>
                        <h5>Saldo Bank</h5>
                        <div class="value {{ $saldoBank >= 0 ? 'positive' : 'negative' }}">
                            Rp <span class="hidden-value" style="display:none;">
                                {{ number_format($saldoBank, 0, ',', '.') }}
                            </span>
                            <span class="masked-value">***</span>
                            <i class="bi bi-eye toggle-eye" style="cursor:pointer;margin-left:10px;"
                                onclick="toggleVisibility(this)"></i>
                        </div>
                        <div class="description">Total s/d Bulan ini</div>
                    </div>
                </div>

                {{-- Tanah Bangunan --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-building"></div>
                        <h5>Tanah Bangunan</h5>
                        <div class="value {{ $jumlahTanahBangunan >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($jumlahTanahBangunan, 0, ',', '.') }}
                        </div>
                        <div class="description">Total Nilai Asset</div>
                    </div>
                </div>

                {{-- Inventaris --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-truck"></div>
                        <h5>Inventaris</h5>
                        <div class="value {{ $jumlahInventaris >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($jumlahInventaris, 0, ',', '.') }}
                        </div>
                        <div class="description">Total Nilai Inventaris</div>
                    </div>
                </div>

                {{-- Piutang --}}
                <div class="col-md-3 mb-4">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-wallet"></div>
                            <h5>Piutang</h5>
                            <div class="value {{ $jumlahPiutang >= 0 ? 'positive' : 'negative' }}">
                                Rp <span class="hidden-value" style="display:none;">
                                    {{ number_format($jumlahPiutang, 0, ',', '.') }}
                                </span>
                                <span class="masked-value">***</span>
                                <i class="bi bi-eye toggle-eye" style="cursor:pointer;margin-left:10px;"
                                    onclick="toggleVisibility(this)"></i>
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- ========================  DASHBOARD YAYASAN (akumulasi semua bidang + bendahara)  ======================== --}}
        <h3 class="mb-4 d-flex">
            <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#dataKeuangan" role="button"
                aria-expanded="true" aria-controls="dataKeuangan">
                Dashboard Keuangan <strong>Yayasan!</strong>
                <i class="bi bi-chevron-down ms-2 chevron"></i>
            </a>
        </h3>

        <div class="collapse show" id="dataKeuangan">
            <div class="row">
                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-gem"></div>
                        <h5 class="w-50">Total Nilai Kekayaan Yayasan</h5>
                        <h3 class="value {{ $totalKeuanganSemuaBidang >= 0 ? 'positive' : 'negative' }}">
                            Rp {{ number_format($totalKeuanganSemuaBidang, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>

                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-cash-coin"></div>
                        <h5 class="w-50">Total Saldo Kas Seluruh Bidang</h5>
                        <h3 class="value {{ $saldoKasTotal >= 0 ? 'positive' : 'negative' }}">
                            Rp {{ number_format($saldoKasTotal, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>

                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-bank"></div>
                        <h5 class="w-50">Total Saldo Bank Seluruh Bidang</h5>
                        <h3 class="value {{ $saldoBankTotal >= 0 ? 'positive' : 'negative' }}">
                            Rp {{ number_format($saldoBankTotal, 0, ',', '.') }}
                        </h3>
                    </div>
                </div>
            </div>

            {{-- NOTE: variabel di bawah ini memakai penamaan dari controller baru (prefix "jumlah*") --}}
            <h4 class="mb-4 d-flex">
                <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#dataKeuanganSub1"
                    role="button" aria-expanded="true" aria-controls="dataKeuanganSub1">
                    Nilai Asset, Yayasan!
                    <i class="bi bi-chevron-down ms-2 chevron"></i>
                </a>
            </h4>
            <div class="collapse show row" id="dataKeuanganSub1">
                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-building"></div>
                        <h5>Tanah Bangunan</h5>
                        <div class="value {{ $totalTanahBangunan >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($totalTanahBangunan, 0, ',', '.') }}
                        </div>
                        <div class="description">Total Nilai Asset</div>
                    </div>
                </div>

                <div class="col-md-4 mb-5">
                    <div class="card">
                        <div class="icon bi bi-truck"></div>
                        <h5>Inventaris</h5>
                        <div class="value {{ $totalInventaris >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($totalInventaris, 0, ',', '.') }}
                        </div>
                        <div class="description">Total Nilai Inventaris</div>
                    </div>
                </div>

                <div class="col-md-4 mb-5">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 103]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-wallet"></div>
                            <h5>Piutang</h5>
                            <div class="value {{ $totalPiutang >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPiutang, 0, ',', '.') }}
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>

            <h4 class="mb-4 d-flex">
                <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#dataKeuanganSub2"
                    role="button" aria-expanded="true" aria-controls="dataKeuanganSub2">
                    Nilai Liability, Yayasan!
                    <i class="bi bi-chevron-down ms-2 chevron"></i>
                </a>
            </h4>
            <div class="collapse show row" id="dataKeuanganSub2">
                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Hutang</h5>
                            <div class="value {{ $totalHutang >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalHutang, 0, ',', '.') }}
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 mb-5">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 202]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Donasi (Pendapatan)</h5>
                            <div class="value {{ $totalDonasi >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalDonasi, 0, ',', '.') }}
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 mb-5">
                    <a href="{{ route('bendahara.detail', ['type' => 'pendapatan belum diterima']) }}"
                        class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Pendapatan Belum Diterima</h5>
                            <div class="value {{ $totalPendapatanBelumDiterima >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanBelumDiterima, 0, ',', '.') }}
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Biaya Dibayar di Muka</h5>
                            <div class="value {{ $totalBiayadibayardimuka >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalBiayadibayardimuka, 0, ',', '.') }}
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>

            <h4 class="mb-4 d-flex">
                <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#dataKeuanganSub3"
                    role="button" aria-expanded="true" aria-controls="dataKeuanganSub3">
                    Nilai Beban & Biaya, Yayasan!
                    <i class="bi bi-chevron-down ms-2 chevron"></i>
                </a>
            </h4>
            <div class="collapse show row" id="dataKeuanganSub3">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-percent"></div>
                        <h5>Nilai Penyusutan Asset</h5>
                        <div class="value negative">{{ number_format($totalPenyusutanAsset, 0, ',', '.') }}</div>
                        <div class="description">Total s/d Bulan ini</div>
                    </div>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Beban Gaji dan Upah</h5>
                            <div class="value negative">{{ number_format($totalBebanGaji, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 303]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-gear"></div>
                            <h5>Biaya Operasional</h5>
                            <div class="value negative">{{ number_format($totalBiayaOperasional, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 304]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-calendar-check"></div>
                            <h5>Biaya Kegiatan Siswa</h5>
                            <div class="value negative">{{ number_format($totalBiayaKegiatanSiswa, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 305]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-tools"></div>
                            <h5>Beban Pemeliharaan</h5>
                            <div class="value negative">{{ number_format($totalBiayaPemeliharaan, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 306]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-people-fill"></div>
                            <h5>Biaya Sosial</h5>
                            <div class="value negative">{{ number_format($totalBiayaSosial, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 307]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-box-seam"></div>
                            <h5>Biaya Per. Extra</h5>
                            <div class="value negative">{{ number_format($totalBiayaPerlengkapanExtra, 0, ',', '.') }}
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 308]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-incognito"></div>
                            <h5>Biaya Seragam</h5>
                            <div class="value negative">{{ number_format($totalBiayaSeragam, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 309]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-incognito"></div>
                            <h5>Biaya Peningkatan SDM</h5>
                            <div class="value negative">{{ number_format($totalBiayaPeningkatanSDM, 0, ',', '.') }}</div>
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
            const parent = icon.closest('.card');
            const hiddenValue = parent.querySelector('.hidden-value');
            const maskedValue = parent.querySelector('.masked-value');

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

        document.addEventListener('DOMContentLoaded', () => {
            const pairs = [
                ['#dataKeuanganbendahara', 'h3 a[href="#dataKeuanganbendahara"] i.chevron'],
                ['#dataKeuangan', 'h3 a[href="#dataKeuangan"] i.chevron'],
                ['#dataKeuanganSub1', 'h4 a[href="#dataKeuanganSub1"] i.chevron'],
                ['#dataKeuanganSub2', 'h4 a[href="#dataKeuanganSub2"] i.chevron'],
                ['#dataKeuanganSub3', 'h4 a[href="#dataKeuanganSub3"] i.chevron'],
            ];
            pairs.forEach(([collapseSel, iconSel]) => {
                const col = document.querySelector(collapseSel);
                const ico = document.querySelector(iconSel);
                if (!col || !ico) return;
                col.addEventListener('shown.bs.collapse', () => ico.classList.add('rotated'));
                col.addEventListener('hidden.bs.collapse', () => ico.classList.remove('rotated'));
            });
        });
    </script>
@endpush

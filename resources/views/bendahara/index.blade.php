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
            </div>
            <h4 class="mb-4">Nilai Kewajiban, {{ auth()->user()->role }}!</h4>
            <div class="row">
                {{-- Hutang Perantara --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 'hutang-perantara']) }}"
                        class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-arrow-left-right"></div>
                            <h6><strong>Hutang Perantara</strong></h6>
                            <div class="value {{ $saldoHutangPerantara >= 0 ? 'positive' : 'negative' }}">
                                Rp {{ number_format($saldoHutangPerantara, 0, ',', '.') }}
                            </div>
                            <div class="description">
                                Kewajiban Bendahara pada Bidang
                            </div>
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

                {{-- Piutang (Buku Besar – Bendahara) --}}
                <div class="col-md-4 mb-5">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => config('akun.group_piutang')]) }}"
                        class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-wallet2"></div>
                            <h5>Piutang</h5>
                            <div class="value {{ $piutangLedger >= 0 ? 'positive' : 'negative' }}">
                                Rp {{ number_format($piutangLedger, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Piutang (konsolidasi) s/d bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>

            <h4 class="mb-4 d-flex">
                <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#dataKeuanganPendapatan"
                    role="button" aria-expanded="true" aria-controls="dataKeuanganPendapatan">
                    Nilai Pendapatan, Yayasan!
                    <i class="bi bi-chevron-down ms-2 chevron"></i>
                </a>
            </h4>

            <div class="collapse show row" id="dataKeuanganPendapatan">
                {{-- Pendapatan PMB --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 201]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-journal-text"></div>
                            <h5>Pendapatan PMB</h5>
                            <div class="value {{ $totalPendapatanPMB >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanPMB, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pendapatan SPP --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 202]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-journal-check"></div>
                            <h5>Pendapatan SPP</h5>
                            <div class="value {{ $totalPendapatanSPP >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanSPP, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pendapatan Lain-lain Pendidikan --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 203]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-mortarboard"></div>
                            <h5>Pendapatan Lain Pendidikan</h5>
                            <div class="value {{ $totalPendapatanLainPendidikan >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanLainPendidikan, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pendapatan Infaq Tidak Terikat --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 204]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-heart"></div>
                            <h5>Infaq Tidak Terikat</h5>
                            <div class="value {{ $totalPendapatanInfaqTidakTerikat >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanInfaqTidakTerikat, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pendapatan Infaq Terikat --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 205]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-heart-pulse"></div>
                            <h5>Infaq / Zakat Terikat</h5>
                            <div class="value {{ $totalPendapatanInfaqTerikat >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanInfaqTerikat, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pendapatan Usaha --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 206]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-shop"></div>
                            <h5>Pendapatan Usaha</h5>
                            <div class="value {{ $totalPendapatanUsaha >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanUsaha, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pendapatan Bendahara Umum --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 207]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-bank2"></div>
                            <h5>Pendapatan Bendahara Umum</h5>
                            <div class="value {{ $totalPendapatanBendaharaUmum >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($totalPendapatanBendaharaUmum, 0, ',', '.') }}
                            </div>
                            <div class="description">Total Tahun ini</div>
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
                {{-- Penyusutan --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-percent"></div>
                        <h5>Penyusutan Aset</h5>
                        <div class="value negative">{{ number_format($totalPenyusutanAsset, 0, ',', '.') }}</div>
                        <div class="description">Total s/d bulan ini</div>
                    </div>
                </div>

                {{-- Gaji --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-people"></div>
                            <h5>Beban Gaji & Tunjangan</h5>
                            <div class="value negative">{{ number_format($totalBebanGaji, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- Operasional --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 303]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-gear"></div>
                            <h5>Biaya Operasional</h5>
                            <div class="value negative">{{ number_format($totalBiayaOperasional, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- Kegiatan --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 304]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-calendar-check"></div>
                            <h5>Biaya Kegiatan</h5>
                            <div class="value negative">{{ number_format($totalBiayaKegiatan, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- Konsumsi --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 305]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cup-hot"></div>
                            <h5>Biaya Konsumsi</h5>
                            <div class="value negative">{{ number_format($totalBiayaKonsumsi, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pemeliharaan --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 306]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-tools"></div>
                            <h5>Biaya Pemeliharaan</h5>
                            <div class="value negative">{{ number_format($totalBiayaPemeliharaan, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pengeluaran Infaq/Zakat Terikat --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 307]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-heart-pulse"></div>
                            <h5>Pengeluaran Terikat</h5>
                            <div class="value negative">{{ number_format($totalPengeluaranTerikat, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- Biaya Lain-lain --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 308]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-three-dots"></div>
                            <h5>Biaya Lain-lain</h5>
                            <div class="value negative">{{ number_format($totalBiayaLainLain, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- Pengeluaran Bendahara Umum --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 309]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-building-fill-gear"></div>
                            <h5>Pengeluaran Bendahara</h5>
                            <div class="value negative">{{ number_format($totalPengeluaranBendahara, 0, ',', '.') }}</div>
                            <div class="description">Total s/d bulan ini</div>
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

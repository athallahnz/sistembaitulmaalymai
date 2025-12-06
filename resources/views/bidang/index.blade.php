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
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="icon bi bi-building"></div>
                        <h6><strong>Tanah Bangunan</strong></h6>
                        <div class="value {{ $jumlahTanahBangunan >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($jumlahTanahBangunan, 0, ',', '.') }}</div>
                        <div class="description">Total Nilai Asset</div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="icon bi bi-truck"></div>
                        <h6><strong>Inventaris</strong></h6>
                        <div class="value {{ $jumlahInventaris >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($jumlahInventaris, 0, ',', '.') }}</div>
                        <div class="description">Total Nilai Inventaris</div>
                    </div>
                </div>
                @if (auth()->user()->bidang && auth()->user()->bidang->name === 'Pendidikan')
                    <div class="col-md-4 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => config('akun.group_piutang')]) }}"
                            class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-wallet2"></div>
                                <h6><strong>Piutang (Buku Besar)</strong></h6>
                                <div class="value {{ $piutangLedger >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($piutangLedger, 0, ',', '.') }}
                                </div>
                                <div class="description">Total Seluruh Piutang</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-4">
                        <a href="{{ route('piutangs.index') }}" class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-people"></div>
                                <h6><strong>Piutang Murid (SPP/PMB)</strong></h6>
                                <div class="value {{ $piutangMurid >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($piutangMurid, 0, ',', '.') }}
                                </div>
                                <div class="description">Total piutang SPP/PMB Murid</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => 'piutang-perantara']) }}"
                            class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-arrow-left-right"></div>
                                <h6><strong>Piutang Perantara</strong></h6>
                                <div class="value {{ $saldoPiutangPerantara >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($saldoPiutangPerantara, 0, ',', '.') }}
                                </div>
                                <div class="description">
                                    Uang Bidang dipegang Bendahara
                                </div>
                            </div>
                        </a>
                    </div>
                @else
                    <div class="col-md-6 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => config('akun.group_piutang')]) }}"
                            class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-wallet2"></div>
                                <h6><strong>Piutang (Buku Besar)</strong></h6>
                                <div class="value {{ $piutangLedger >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($piutangLedger, 0, ',', '.') }}
                                </div>
                                <div class="description">Total Seluruh Piutang</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => 'piutang-perantara']) }}"
                            class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-arrow-left-right"></div>
                                <h6><strong>Piutang Perantara</strong></h6>
                                <div class="value {{ $saldoPiutangPerantara >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($saldoPiutangPerantara, 0, ',', '.') }}
                                </div>
                                <div class="description">
                                    Uang Bidang dipegang Bendahara
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                @if (auth()->user()->bidang && auth()->user()->bidang->name === 'Pendidikan')
                    <h4 class="mb-4">Nilai Kewajiban, {{ auth()->user()->role }}!</h4>
                    {{-- Pendapatan Belum Diterima PMB --}}
                    <div class="col-md-3 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => 50012]) }}" class="text-decoration-none">
                            <div class="card monitoring-card">
                                <div class="icon bi bi-hourglass-split"></div>
                                <h6><strong>PBD PMB</strong></h6>
                                <div class="value {{ $pendapatanBelumDiterimaPMB >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($pendapatanBelumDiterimaPMB, 0, ',', '.') }}
                                </div>
                                <div class="description">Kewajiban PMB yang belum diakui</div>
                            </div>
                        </a>
                    </div>
                    {{-- Pendapatan Belum Diterima SPP --}}
                    <div class="col-md-3 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => 50011]) }}" class="text-decoration-none">
                            <div class="card monitoring-card">
                                <div class="icon bi bi-hourglass-split"></div>
                                <h6><strong>PBD SPP</strong></h6>
                                <div class="value {{ $pendapatanBelumDiterimaSPP >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($pendapatanBelumDiterimaSPP, 0, ',', '.') }}
                                </div>
                                <div class="description">Kewajiban SPP yang belum diakui</div>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
            <h4 class="mb-4">Pendapatan, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>
            <div class="row">
                @if (auth()->user()->bidang && auth()->user()->bidang->name === 'Pendidikan')
                    {{-- 201 - Pendapatan PMB --}}
                    <div class="col-md-3 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => 201]) }}" class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-journal-text"></div>
                                <h6><strong>Pendapatan PMB</strong></h6>
                                <div class="value {{ $jumlahPendapatanPMB >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($jumlahPendapatanPMB, 0, ',', '.') }}
                                </div>
                                <div class="description">Total tahun ini</div>
                            </div>
                        </a>
                    </div>

                    {{-- 202 - Pendapatan SPP --}}
                    <div class="col-md-3 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => 202]) }}" class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-journal-check"></div>
                                <h6><strong>Pendapatan SPP</strong></h6>
                                <div class="value {{ $jumlahPendapatanSPP >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($jumlahPendapatanSPP, 0, ',', '.') }}
                                </div>
                                <div class="description">Total tahun ini</div>
                            </div>
                        </a>
                    </div>

                    {{-- 203 - Pendapatan Lain Pendidikan --}}
                    <div class="col-md-3 mb-4">
                        <a href="{{ route('bidang.detail', ['parent_akun_id' => 203]) }}" class="text-decoration-none">
                            <div class="card">
                                <div class="icon bi bi-mortarboard"></div>
                                <h6><strong>Pendapatan Lain Pendidikan</strong></h6>
                                <div class="value {{ $jumlahPendapatanLainPendidikan >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($jumlahPendapatanLainPendidikan, 0, ',', '.') }}
                                </div>
                                <div class="description">Total tahun ini</div>
                            </div>
                        </a>
                    </div>
                @endif
                {{-- 204 - Infaq Tidak Terikat --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 204]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-heart"></div>
                            <h6><strong>Infaq Tidak Terikat</strong></h6>
                            <div class="value {{ $jumlahPendapatanInfaqTidakTerikat >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahPendapatanInfaqTidakTerikat, 0, ',', '.') }}
                            </div>
                            <div class="description">Total tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- 205 - Infaq / Zakat Terikat --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 205]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-heart-pulse"></div>
                            <h6><strong>Infaq / Zakat Terikat</strong></h6>
                            <div class="value {{ $jumlahPendapatanInfaqTerikat >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahPendapatanInfaqTerikat, 0, ',', '.') }}
                            </div>
                            <div class="description">Total tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- 206 - Pendapatan Usaha --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 206]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-shop"></div>
                            <h6><strong>Pendapatan Usaha</strong></h6>
                            <div class="value {{ $jumlahPendapatanUsaha >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahPendapatanUsaha, 0, ',', '.') }}
                            </div>
                            <div class="description">Total tahun ini</div>
                        </div>
                    </a>
                </div>

                {{-- 207 - Pendapatan Bendahara Umum --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 207]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-bank2"></div>
                            <h6><strong>Pendapatan Bendahara Umum</strong></h6>
                            <div class="value {{ $jumlahPendapatanBendaharaUmum >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahPendapatanBendaharaUmum, 0, ',', '.') }}
                            </div>
                            <div class="description">Total tahun ini</div>
                        </div>
                    </a>
                </div>
            </div>
            <h4 class="mb-4">Beban & Biaya, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>
            <div class="row">
                {{-- 301 - Penyusutan --}}
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-percent"></div>
                        <h6><strong>Nilai Penyusutan Aset</strong></h6>
                        <div class="value">{{ number_format($jumlahPenyusutanAsset, 0, ',', '.') }}</div>
                        <div class="description">untuk bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</div>
                    </div>
                </div>

                {{-- 302 - Beban Gaji --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 302]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-people"></div>
                            <h6><strong>Beban Gaji & Tunjangan</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBebanGaji, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- 303 - Biaya Operasional --}}
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

                {{-- 304 - Biaya Kegiatan --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 304]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-calendar-check"></div>
                            <h6><strong>Biaya Kegiatan</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaKegiatan, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- 305 - Biaya Konsumsi --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 305]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cup-hot"></div>
                            <h6><strong>Biaya Konsumsi</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaKonsumsi, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- 306 - Biaya Pemeliharaan --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 306]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-tools"></div>
                            <h6><strong>Biaya Pemeliharaan</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaPemeliharaan, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- 307 - Pengeluaran Terikat --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 307]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-heart-pulse"></div>
                            <h6><strong>Pengeluaran Terikat</strong></h6>
                            <div class="value negative">{{ number_format($jumlahPengeluaranTerikat, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- 308 - Biaya Lain-lain --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 308]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-three-dots"></div>
                            <h6><strong>Biaya Lain-lain</strong></h6>
                            <div class="value negative">{{ number_format($jumlahBiayaLainLain, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>

                {{-- 309 - Pengeluaran Bendahara --}}
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bidang.detail', ['parent_akun_id' => 309]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-building-fill-gear"></div>
                            <h6><strong>Pengeluaran Bendahara Umum</strong></h6>
                            <div class="value negative">{{ number_format($jumlahPengeluaranBendahara, 0, ',', '.') }}
                            </div>
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

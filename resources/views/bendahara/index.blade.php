@extends('layouts.app')
@section('title', 'Dashboard Bendahara')
@section('content')
    <div class="container">
        <h1 class="mb-4">Selamat Datang, <strong>{{ auth()->user()->role }} Yayasan!</strong></h1>
        {{-- <h3 class="mb-4 d-flex">
            <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#dataKeuanganbendahara" role="button"
                aria-expanded="true" aria-controls="dataKeuanganbendahara"> Dashboard Keuangan
                <strong>{{ auth()->user()->role }}!</strong>
                <i class="bi bi-chevron-down ms-2 chevron"></i>
            </a>
        </h3>
        <div class="collapse show" id="dataKeuanganbendahara">
            <h4 class="mb-4">Nilai Asset, {{ auth()->user()->role }}!</h4>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-cash-coin"></div>
                        <h5>Nilai Kekayaan</h5>
                        <div class="">
                            Rp <span class="hidden-value" style="display: none;">***</span>
                            <span class="masked-value">***</span>
                            <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                                onclick="toggleVisibility(this)"></i>
                        </div>
                        <div class="description">untuk {{ auth()->user()->role }}!</div>
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
                        <div class="">
                            Rp <span class="hidden-value" style="display: none;">***</span>
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
                        <h5>Saldo Bank</h5>
                        <div class="">
                            Rp <span class="hidden-value" style="display: none;">***</span>
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
                        <h5>Tanah Bangunan</h5>
                        <div class="">
                            ***</div>
                        <div class="description">Total Nilai Asset</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="icon bi bi-truck"></div>
                        <h5>Inventaris</h5>
                        <div class="">
                            ***</div>
                        <div class="description">Total Nilai Inventaris</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-wallet"></div>
                            <h5>Piutang</h5>
                            <div class="">
                                Rp <span class="hidden-value" style="display: none;">***</span>
                                <span class="masked-value">***</span>
                                <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                                    onclick="toggleVisibility(this)"></i>
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
            </div>
        </div> --}}
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
                <div class="col-md-4 mb-5">
                    <a href="#" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-cash"></div>
                            <h5>Biaya Dibayar di Muka</h5>
                            <div class="value {{ $jumlahBiayadibayardimuka >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($jumlahBiayadibayardimuka, 0, ',', '.') }}</div>
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
                            <div class="value negative">{{ number_format($totalBiayaKegiatan, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 305]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-tools"></div>
                            <h5>Beban Pemeliharaan</h5>
                            <div class="value negative">{{ number_format($totalBiayaPemeliharaan, 0, ',', '.') }}
                            </div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 306]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-people-fill"></div> {{-- Ikon untuk "Biaya Sosial" --}}
                            <h5>Biaya Sosial</h5>
                            <div class="value negative">{{ number_format($totalBiayaSosial, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 307]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-box-seam"></div> {{-- Ikon untuk "Biaya Per. Extra" --}}
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
                            <div class="icon bi bi-incognito"></div> {{-- Ikon untuk "Biaya Seragam" --}}
                            <h5>Biaya Seragam</h5>
                            <div class="value negative">{{ number_format($totalBiayaSeragam, 0, ',', '.') }}</div>
                            <div class="description">Total s/d Bulan ini</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-4">
                    <a href="{{ route('bendahara.detail', ['parent_akun_id' => 309]) }}" class="text-decoration-none">
                        <div class="card">
                            <div class="icon bi bi-incognito"></div> {{-- Ikon untuk "Biaya Seragam" --}}
                            <h5>Biaya Peningkatan SDM</h5>
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

        document.addEventListener('DOMContentLoaded', () => {
            // main section bendahara
            const mainCollapse = document.getElementById('dataKeuanganbendahara');
            const mainChevron = document.querySelector('h3 a[href="#dataKeuanganbendahara"] i.chevron');

            mainCollapse.addEventListener('shown.bs.collapse', () => mainChevron.classList.add('rotated'));
            mainCollapse.addEventListener('hidden.bs.collapse', () => mainChevron.classList.remove('rotated'));

            // main section yayasan
            const mainCollapse = document.getElementById('dataKeuangan');
            const mainChevron = document.querySelector('h3 a i.chevron');

            mainCollapse.addEventListener('shown.bs.collapse', () => mainChevron.classList.add('rotated'));
            mainCollapse.addEventListener('hidden.bs.collapse', () => mainChevron.classList.remove('rotated'));

            // sub‑section 1
            const sub1Collapse = document.getElementById('dataKeuanganSub1');
            const sub1Chevron = document.querySelector('h4 a[href="#dataKeuanganSub1"] i.chevron');

            sub1Collapse.addEventListener('shown.bs.collapse', () => sub1Chevron.classList.add('rotated'));
            sub1Collapse.addEventListener('hidden.bs.collapse', () => sub1Chevron.classList.remove('rotated'));

            // sub‑section 2
            const sub2Collapse = document.getElementById('dataKeuanganSub2');
            const sub2Chevron = document.querySelector('h4 a[href="#dataKeuanganSub2"] i.chevron');

            sub2Collapse.addEventListener('shown.bs.collapse', () => sub2Chevron.classList.add('rotated'));
            sub2Collapse.addEventListener('hidden.bs.collapse', () => sub2Chevron.classList.remove('rotated'));

            // sub‑section 3
            const sub3Collapse = document.getElementById('dataKeuanganSub3');
            const sub3Chevron = document.querySelector('h4 a[href="#dataKeuanganSub3"] i.chevron');

            sub3Collapse.addEventListener('shown.bs.collapse', () => sub3Chevron.classList.add('rotated'));
            sub3Collapse.addEventListener('hidden.bs.collapse', () => sub3Chevron.classList.remove('rotated'));
        });
    </script>
@endpush

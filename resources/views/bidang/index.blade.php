@extends('layouts.app')
@section('title', 'Dashboard Bidang')

@section('content')
    @php
        $periodStart = request('start_date', now()->startOfYear()->toDateString());
        $periodEnd = request('end_date', now()->toDateString());

        $periodLabel =
            'Periode ' .
            \Carbon\Carbon::parse($periodStart)->translatedFormat('M Y') .
            ' – ' .
            \Carbon\Carbon::parse($periodEnd)->translatedFormat('M Y');

        $cutoffLabel = 'Posisi per ' . \Carbon\Carbon::parse($periodEnd)->translatedFormat('M Y');

        // cards dari DashboardService::getCards('BIDANG', ...)
        $revenueCards = $cards['revenue'] ?? [];
        $expenseCards = $cards['expense'] ?? [];
    @endphp

    <div class="container">
        {{-- Header + Filter --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
            <h1 class="mb-2 mb-md-0">
                <strong>
                    Selamat Datang, di Dashboard {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!
                </strong>
            </h1>

            <form method="GET" class="d-flex align-items-center gap-2">
                <input type="date" name="start_date" class="form-control form-control-sm"
                    value="{{ request('start_date', now()->startOfYear()->toDateString()) }}">

                <span class="fw-semibold">s/d</span>

                <input type="date" name="end_date" class="form-control form-control-sm"
                    value="{{ request('end_date', now()->toDateString()) }}">

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-filter"></i>
                </button>
            </form>
        </div>

        <div class="container-fluid p-4">

            {{-- ===================== ASSET (STATIK) ===================== --}}
            <h4 class="mb-4">Nilai Asset, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>

            <div class="row">
                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-cash-coin" title="Nilai Kekayaan" :value="$totalKeuanganBidang ?? 0" :label="$periodLabel"
                        :masked="true" />
                </div>

                <div class="col-md-3 mb-4">
                    <div class="stat-card h-100" data-cardstat data-cardstat-id="transaksi-counter"
                        data-value="{{ (int) ($jumlahTransaksi ?? 0) }}" data-format="number" data-animate="1">
                        <div class="stat-card__top">
                            <div class="stat-card__icon">
                                <i class="bi bi-credit-card"></i>
                            </div>
                        </div>

                        <div class="stat-card__title">
                            Transaksi
                        </div>

                        <div class="stat-card__value is-positive">
                            <span id="transaksi-counter" class="stat-card__number"
                                data-target="{{ (int) ($jumlahTransaksi ?? 0) }}">
                                0
                            </span>
                        </div>

                        <div class="stat-card__meta">
                            Bulan ini
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-cash" title="Saldo Kas" :value="$saldoKas ?? 0" :label="$cutoffLabel" :masked="true" />
                </div>

                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-bank" title="Saldo Bank" :value="$saldoBank ?? 0" :label="$cutoffLabel" :masked="true" />
                </div>

                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-building" title="Tanah Bangunan" :value="$jumlahTanahBangunan ?? 0" :label="$cutoffLabel"
                        :masked="false" />
                </div>

                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-truck" title="Inventaris" :value="$jumlahInventaris ?? 0" :label="$cutoffLabel"
                        :masked="false" />
                </div>

                {{-- Piutang (Buku Besar) --}}
                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-wallet2" title="Piutang (Buku Besar)" :value="$piutangLedger ?? 0" :label="$cutoffLabel"
                        :masked="false" :link="route('bidang.detail', ['parent_akun_id' => config('akun.group_piutang')])" />
                </div>

                {{-- Pendidikan: Piutang Murid --}}
                @if (auth()->user()->bidang && auth()->user()->bidang->name === 'Pendidikan')
                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-people" title="Piutang Murid (SPP/PMB)" :value="$piutangMurid ?? 0" :label="$cutoffLabel"
                            :masked="false" :link="route('piutangs.index')" />
                    </div>
                @endif

                {{-- Piutang Perantara --}}
                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-arrow-left-right" title="Piutang Perantara" :value="$saldoPiutangPerantara ?? 0" :label="$cutoffLabel"
                        :masked="false" :link="route('bidang.detail', [
                            'parent_akun_id' => 'piutang-perantara',
                            'start_date' => request('start_date'),
                            'end_date' => request('end_date'),
                        ])" />
                </div>
            </div>

            {{-- ===================== KEWAJIBAN / LIABILITIES (BIDANG) ===================== --}}
            @php
                $bidang = auth()->user()->bidang;
                $bidangName = $bidang->name ?? null;
            @endphp

            @if ($bidangName)
                <h4 class="mb-4">Nilai Kewajiban, Bidang {{ $bidangName }}!</h4>
                <div class="row">

                    {{-- ===================== UMUM: Hutang Perantara (SEMUA BIDANG) ===================== --}}
                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-arrow-left-right" title="Hutang Perantara" :value="$saldoHutangPerantara ?? 0"
                            :label="$cutoffLabel" :masked="false" :link="route('bidang.detail', [
                                'parent_akun_id' => 'hutang-perantara',
                                'start_date' => request('start_date'),
                                'end_date' => request('end_date'),
                            ])" />
                    </div>

                    {{-- ===================== KHUSUS: Pendidikan ===================== --}}
                    @if ($bidangName === 'Pendidikan')
                        <div class="col-md-3 mb-4">
                            <x-card-stat icon="bi-hourglass-split" title="PBD PMB" :value="$pendapatanBelumDiterimaPMB ?? 0" :label="$cutoffLabel"
                                :masked="false" :link="route('bidang.detail', ['parent_akun_id' => 50012])" />
                        </div>

                        <div class="col-md-3 mb-4">
                            <x-card-stat icon="bi-hourglass-split" title="PBD SPP" :value="$pendapatanBelumDiterimaSPP ?? 0" :label="$cutoffLabel"
                                :masked="false" :link="route('bidang.detail', ['parent_akun_id' => 50011])" />
                        </div>
                    @endif

                    {{-- ===================== KHUSUS: Sosial ===================== --}}
                    @if ($bidangName === 'Sosial')
                        <div class="col-md-3 mb-4">
                            <x-card-stat icon="bi-shield-lock" title="Hutang Program Sosial" :value="$hutangProgramSosial ?? 0"
                                :label="$cutoffLabel" :masked="false" :link="route('bidang.detail', ['parent_akun_id' => 5005])" />
                        </div>
                    @endif

                </div>
            @endif

            {{-- ===================== PENDAPATAN (DINAMIS) ===================== --}}
            <h4 class="mb-4">Pendapatan, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>
            <div class="row">
                @forelse ($revenueCards as $c)
                    <div class="col-md-3 mb-4">
                        <x-card-stat :icon="$c['icon'] ?? 'bi-question-circle'" :title="$c['title'] ?? ($c['nama_akun'] ?? '-')" :value="$c['value'] ?? 0" :label="$c['label'] ?? null"
                            :format="$c['format'] ?? 'currency'" :masked="(bool) ($c['masked'] ?? true)" :link="$c['link'] ?? null" />
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-muted small">Belum ada konfigurasi card Pendapatan (scope BIDANG).</div>
                    </div>
                @endforelse
            </div>

            {{-- ===================== BEBAN (DINAMIS) ===================== --}}
            <h4 class="mb-4">Beban & Biaya, Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}!</h4>
            <div class="row">
                @forelse ($expenseCards as $c)
                    <div class="col-md-3 mb-4">
                        <x-card-stat :icon="$c['icon'] ?? 'bi-question-circle'" :title="$c['title'] ?? ($c['nama_akun'] ?? '-')" :value="$c['value'] ?? 0" :label="$c['label'] ?? null"
                            :format="$c['format'] ?? 'currency'" :masked="(bool) ($c['masked'] ?? true)" :link="$c['link'] ?? null" />
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-muted small">Belum ada konfigurasi card Beban (scope BIDANG).</div>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Format angka ke id-ID style: 1.234.567
        function formatIDNumber(n) {
            try {
                const x = Math.round(Number(n) || 0);
                return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            } catch (e) {
                return "0";
            }
        }

        function animateCounter(el, target, opts = {}) {
            const duration = Number(opts.duration ?? 900); // ms
            const startValue = Number(opts.start ?? 0);
            const startTime = performance.now();

            const step = (t) => {
                const p = Math.min(1, (t - startTime) / duration);
                // easing (smooth)
                const eased = 1 - Math.pow(1 - p, 3);

                const current = startValue + (target - startValue) * eased;
                el.textContent = formatIDNumber(current);

                if (p < 1) requestAnimationFrame(step);
            };

            requestAnimationFrame(step);
        }

        function runCardCounters(scopeRoot = document) {
            const cards = scopeRoot.querySelectorAll('[data-cardstat=""] , [data-cardstat]');
            cards.forEach(card => {
                const animate = card.getAttribute('data-animate') === '1';
                if (!animate) return;

                const format = card.getAttribute('data-format') || 'currency';
                const prefix = card.getAttribute('data-currency-prefix') || '';
                const value = Number(card.getAttribute('data-value') || 0);

                const id = card.getAttribute('data-cardstat-id');
                const numEl = id ? document.getElementById(id) : null;
                if (!numEl) return;

                // Jika masked: jangan auto animate sebelum dibuka
                const hiddenValue = card.querySelector('.hidden-value');
                const maskedValue = card.querySelector('.masked-value');
                const isMaskedMode = !!hiddenValue && !!maskedValue;

                if (isMaskedMode) {
                    // mode masked -> hanya animate saat unmask (toggleVisibility)
                    return;
                }

                // Non-masked -> animate on load
                // handle prefix: prefix ditampilkan via span terpisah di blade (jadi tidak ditulis di textContent)
                animateCounter(numEl, value, {
                    duration: 1000,
                    start: 0
                });
            });
        }

        // Toggle mask + jalankan counter saat dibuka (untuk masked=true)
        function toggleVisibility(btnOrIcon) {
            const card = btnOrIcon.closest('.stat-card, .card');
            if (!card) return;

            const hiddenValue = card.querySelector('.hidden-value');
            const maskedValue = card.querySelector('.masked-value');

            // versi baru: element number untuk counter
            const id = card.getAttribute('data-cardstat-id');
            const numEl = id ? document.getElementById(id) : null;

            // icon (support: button>i, or old icon)
            const iconEl = btnOrIcon.querySelector?.('i') || btnOrIcon;

            // Jika pakai komponen baru (masked)
            if (hiddenValue && maskedValue && numEl) {
                const isHidden = (numEl.style.display === 'none' || numEl.style.display === '');

                if (isHidden) {
                    // show animated number
                    maskedValue.style.display = 'none';
                    hiddenValue.style.display = 'none';
                    numEl.style.display = 'inline';

                    const target = Number(numEl.getAttribute('data-target') || card.getAttribute('data-value') || 0);
                    numEl.textContent = "0";
                    animateCounter(numEl, target, {
                        duration: 1000,
                        start: 0
                    });

                    iconEl.classList.remove('bi-eye');
                    iconEl.classList.add('bi-eye-slash');
                } else {
                    // back to masked
                    numEl.style.display = 'none';
                    maskedValue.style.display = 'inline';
                    hiddenValue.style.display = 'none';

                    iconEl.classList.remove('bi-eye-slash');
                    iconEl.classList.add('bi-eye');
                }
                return;
            }

            // Fallback untuk card lama kamu (yang pakai hidden-value/masked-value saja)
            if (hiddenValue && maskedValue) {
                if (hiddenValue.style.display === 'none' || hiddenValue.style.display === '') {
                    hiddenValue.style.display = 'inline';
                    maskedValue.style.display = 'none';
                    iconEl.classList.remove('bi-eye');
                    iconEl.classList.add('bi-eye-slash');
                } else {
                    hiddenValue.style.display = 'none';
                    maskedValue.style.display = 'inline';
                    iconEl.classList.remove('bi-eye-slash');
                    iconEl.classList.add('bi-eye');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            runCardCounters(document);
        });
    </script>
@endpush

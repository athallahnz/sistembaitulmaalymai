@extends('layouts.app')
@section('title', 'Dashboard Bendahara')

@section('content')
    @php
        // Period dari controller (Carbon)
        $periodStart = $period['start'] ?? now()->startOfYear();
        $periodEnd = $period['end'] ?? now();

        $periodLabel = 'Periode ' . $periodStart->translatedFormat('M Y') . ' – ' . $periodEnd->translatedFormat('M Y');
        $cutoffLabel = 'Posisi per ' . $periodEnd->translatedFormat('M Y');

        // ========= CARDS (DB-driven) =========
        // BENDAHARA
        $cardsB = $cardsBendahara ?? ($cards ?? []); // fallback kalau controller masih kirim $cards
        $bRevenueCards = $cardsB['revenue'] ?? [];
        $bExpenseCards = $cardsB['expense'] ?? [];

        // YAYASAN (Bidang + Bendahara / konsolidasi)
        $cardsY = $cardsYayasan ?? [];
        $yRevenueCards = $cardsY['revenue'] ?? [];
        $yExpenseCards = $cardsY['expense'] ?? [];
    @endphp

    <div class="container">
        {{-- Header + Filter (dibuat konsisten seperti Bidang) --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
            <h1 class="mb-2 mb-md-0">
                <strong>
                    Selamat Datang, {{ auth()->user()->role }} Yayasan!
                </strong>
            </h1>

            <form method="GET" class="d-flex align-items-center gap-2">
                <input type="date" name="start_date" class="form-control form-control-sm"
                    value="{{ request('start_date', $periodStart->toDateString()) }}">

                <span class="fw-semibold">s/d</span>

                <input type="date" name="end_date" class="form-control form-control-sm"
                    value="{{ request('end_date', $periodEnd->toDateString()) }}">

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-filter"></i>
                </button>
            </form>
        </div>

        <div class="container-fluid p-4">

            {{-- =========================================================
                SECTION 1: BENDAHARA (AKUN AKTIF BENDAHARA)
            ========================================================== --}}
            <h3 class="mb-3 d-flex">
                <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#secBendahara" role="button"
                    aria-expanded="true" aria-controls="secBendahara">
                    Dashboard Keuangan <strong>{{ auth()->user()->role }}</strong>
                    <i class="bi bi-chevron-down ms-2 chevron"></i>
                </a>
            </h3>

            <div class="collapse show" id="secBendahara">

                {{-- ===================== ASSET (STATIK) ===================== --}}
                <h4 class="mb-4">Nilai Asset, {{ auth()->user()->role }}!</h4>
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-cash-coin" title="Nilai Kekayaan" :value="$totalKeuanganBendahara ?? 0" :label="$cutoffLabel"
                            :masked="true" />
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="stat-card h-100" data-cardstat data-cardstat-id="transaksi-counter"
                            data-value="{{ (int) ($jumlahTransaksiBendahara ?? 0) }}" data-format="number" data-animate="1">
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
                                    data-target="{{ (int) ($jumlahTransaksiBendahara ?? 0) }}">
                                    0
                                </span>
                            </div>

                            <div class="stat-card__meta">
                                Bulan ini
                            </div>
                        </div>
                    </div>


                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-cash" title="Saldo Kas" :value="$saldoKas ?? 0" :label="$cutoffLabel"
                            :masked="true" />
                    </div>

                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-bank" title="Saldo Bank" :value="$saldoBank ?? 0" :label="$cutoffLabel"
                            :masked="true" />
                    </div>

                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-building" title="Tanah Bangunan" :value="$tanahBangunanBendahara ?? 0" :label="$cutoffLabel"
                            :masked="false" />
                    </div>

                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-truck" title="Inventaris" :value="$inventarisBendahara ?? 0" :label="$cutoffLabel"
                            :masked="false" />
                    </div>

                    {{-- Piutang Perantara --}}
                    <div class="col-md-3 mb-4">
                        <x-card-stat icon="bi-arrow-left-right" title="Piutang Perantara" :value="$saldoPiutangPerantara ?? 0"
                            :label="$cutoffLabel" :masked="false" :link="route('bendahara.detail', [
                                'parent_akun_id' => 'piutang-perantara',
                                'start_date' => request('start_date'),
                                'end_date' => request('end_date'),
                            ])" />
                    </div>

                </div>

                {{-- ===================== LIABILITI (STATIK) ===================== --}}
                <h4 class="mb-4">Nilai Liability, {{ auth()->user()->role }}!</h4>
                {{-- Hutang Perantara --}}
                <div class="col-md-3 mb-4">
                    <x-card-stat icon="bi-arrow-left-right" title="Hutang Perantara" :value="$saldoHutangPerantara ?? 0" :label="$cutoffLabel"
                        :masked="false" :link="route('bendahara.detail', [
                            'parent_akun_id' => 'hutang-perantara',
                            'start_date' => request('start_date'),
                            'end_date' => request('end_date'),
                        ])" />
                </div>

                {{-- ===================== PENDAPATAN (DINAMIS) ===================== --}}
                <h4 class="mb-4">Pendapatan, {{ auth()->user()->role }}!</h4>
                <div class="row">
                    @forelse ($bRevenueCards as $c)
                        <div class="col-md-3 mb-4">
                            <x-card-stat :icon="$c['icon'] ?? 'bi-question-circle'" :title="$c['title'] ?? ($c['nama_akun'] ?? '-')" :value="$c['value'] ?? 0" :label="$c['label'] ?? null"
                                :format="$c['format'] ?? 'currency'" :masked="(bool) ($c['masked'] ?? true)" :link="$c['link'] ?? null" />
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted small">Belum ada konfigurasi card Pendapatan (scope BENDAHARA).</div>
                        </div>
                    @endforelse
                </div>

                {{-- ===================== BEBAN (DINAMIS) ===================== --}}
                <h4 class="mb-4">Beban & Biaya, {{ auth()->user()->role }}!</h4>
                <div class="row">
                    @forelse ($bExpenseCards as $c)
                        <div class="col-md-3 mb-4">
                            <x-card-stat :icon="$c['icon'] ?? 'bi-question-circle'" :title="$c['title'] ?? ($c['nama_akun'] ?? '-')" :value="$c['value'] ?? 0" :label="$c['label'] ?? null"
                                :format="$c['format'] ?? 'currency'" :masked="(bool) ($c['masked'] ?? true)" :link="$c['link'] ?? null" />
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted small">Belum ada konfigurasi card Beban (scope BENDAHARA).</div>
                        </div>
                    @endforelse
                </div>
            </div>


            {{-- =========================================================
                SECTION 2: YAYASAN (KONSOLIDASI: BIDANG + BENDAHARA)
            ========================================================== --}}
            <h3 class="mb-3 d-flex mt-4">
                <a class="text-decoration-none text-dark" data-bs-toggle="collapse" href="#secYayasan" role="button"
                    aria-expanded="true" aria-controls="secYayasan">
                    Dashboard Keuangan <strong>Yayasan (Konsolidasi)</strong>
                    <i class="bi bi-chevron-down ms-2 chevron"></i>
                </a>
            </h3>

            <div class="collapse show" id="secYayasan">

                {{-- ===================== ASSET (STATIK) ===================== --}}
                <h4 class="mb-4">Nilai Asset, Yayasan!</h4>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <x-card-stat icon="bi-gem" title="Total Nilai Kekayaan Yayasan" :value="$totalKeuanganSemuaBidang ?? 0"
                            :label="$cutoffLabel" :masked="true" />
                    </div>

                    <div class="col-md-4 mb-4">
                        <x-card-stat icon="bi-cash-coin" title="Total Saldo Kas Seluruh Bidang" :value="$saldoKasTotal ?? 0"
                            :label="$cutoffLabel" :masked="true" />
                    </div>

                    <div class="col-md-4 mb-4">
                        <x-card-stat icon="bi-bank" title="Total Saldo Bank Seluruh Bidang" :value="$saldoBankTotal ?? 0"
                            :label="$cutoffLabel" :masked="true" />
                    </div>
                </div>

                {{-- ===================== PENDAPATAN YAYASAN (DINAMIS) ===================== --}}
                <h4 class="mb-4">Pendapatan, Yayasan!</h4>
                <div class="row">
                    @forelse ($yRevenueCards as $c)
                        <div class="col-md-3 mb-4">
                            <x-card-stat :icon="$c['icon'] ?? 'bi-question-circle'" :title="$c['title'] ?? ($c['nama_akun'] ?? '-')" :value="$c['value'] ?? 0" :label="$c['label'] ?? null"
                                :format="$c['format'] ?? 'currency'" :masked="(bool) ($c['masked'] ?? true)" :link="$c['link'] ?? null" />
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted small">Belum ada konfigurasi card Pendapatan (scope YAYASAN).</div>
                        </div>
                    @endforelse
                </div>

                {{-- ===================== BEBAN YAYASAN (DINAMIS) ===================== --}}
                <h4 class="mb-4">Beban & Biaya, Yayasan!</h4>
                <div class="row">
                    @forelse ($yExpenseCards as $c)
                        <div class="col-md-3 mb-4">
                            <x-card-stat :icon="$c['icon'] ?? 'bi-question-circle'" :title="$c['title'] ?? ($c['nama_akun'] ?? '-')" :value="$c['value'] ?? 0" :label="$c['label'] ?? null"
                                :format="$c['format'] ?? 'currency'" :masked="(bool) ($c['masked'] ?? true)" :link="$c['link'] ?? null" />
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted small">Belum ada konfigurasi card Beban (scope YAYASAN).</div>
                        </div>
                    @endforelse
                </div>

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

        document.addEventListener('DOMContentLoaded', () => {
            const pairs = [
                ['#secBendahara', 'h3 a[href="#secBendahara"] i.chevron'],
                ['#secYayasan', 'h3 a[href="#secYayasan"] i.chevron'],
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

@extends('layouts.app')

@section('title', 'Detail Infaq Warga')
@push('styles')
    <style>
        .input-lunas {
            background-color: #e6f7ee;
            border: 1px solid #28a745;
        }

        .input-belum {
            background-color: #fdeaea;
            border: 1px solid #dc3545;
        }
    </style>
@endpush
@section('content')
    <div class="container py-4">
        {{-- ===== Header ===== --}}
        <header class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h1 class="section-heading mb-1">
                <span class="text-brown">Detail Infaq - Sdr. <strong>{{ $warga->nama ?? '-' }}</strong></span>
            </h1>

            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('kemasjidan.infaq.index') }}" class="btn btn-outline-secondary">
                    Kembali
                </a>
                {{-- bila perlu tambahkan tombol lain --}}
            </div>
        </header>

        {{-- ===== Alerts ===== --}}
        @if (session('success'))
            <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ===== FORM UPDATE BULANAN ===== --}}
        <div class="card glass shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('kemasjidan.infaq.update', $warga->id) }}" id="form-infaq-update"
                    class="animate-fadein">
                    @csrf
                    @method('PUT')

                    @php
                        $bulanList = [
                            'januari',
                            'februari',
                            'maret',
                            'april',
                            'mei',
                            'juni',
                            'juli',
                            'agustus',
                            'september',
                            'oktober',
                            'november',
                            'desember',
                        ];
                    @endphp
                    <input type="hidden" name="metode_bayar" id="metode-bayar-detail">

                    {{-- grid 4 kolom di md+, 2 kolom di sm --}}
                    <div class="row g-3">
                        @foreach ($bulanList as $bulan)
                            @php
                                $nominal = (float) ($infaq->$bulan ?? 0);
                                $isLunas = $nominal > 0;
                            @endphp

                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label text-capitalize fw-semibold mb-1">{{ $bulan }}</label>
                                <div class="input-group">
                                    <span class="input-group-text {{ $isLunas ? 'input-lunas' : 'input-belum' }}">Rp</span>
                                    <input type="text" inputmode="numeric" autocomplete="off" name="{{ $bulan }}"
                                        class="form-control nominal-bulan {{ $isLunas ? 'input-lunas' : 'input-belum' }}"
                                        value="{{ old($bulan, $nominal) }}">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- total --}}
                    <div class="row mt-4 g-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-semibold">Total</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                {{-- Display terformat --}}
                                <input type="text" id="total-display" class="form-control" readonly
                                    style="border-top-right-radius: var(--radius-sm); border-bottom-right-radius: var(--radius-sm);">
                                {{-- Hidden untuk dikirim ke backend --}}
                                <input type="hidden" name="total" id="total-infaq"
                                    value="{{ old('total', $infaq->total ?? 0) }}">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            Simpan
                        </button>
                        <a href="{{ route('kemasjidan.infaq.index') }}" class="btn btn-outline-secondary">
                            Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== STATUS PER BULAN + KWITANSI ===== --}}
        <div class="card glass shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Status Pembayaran Per Bulan</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bulan</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Kwitansi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bulanList as $bulan)
                                @php
                                    $nom = (float) ($infaq->$bulan ?? 0);
                                    $lunas = $nom > 0;
                                @endphp
                                <tr>
                                    <td class="text-capitalize fw-semibold">{{ $bulan }}</td>
                                    <td>Rp{{ number_format($nom, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($lunas)
                                            <span class="badge text-bg-success text-white">Lunas</span>
                                        @else
                                            <span class="badge text-bg-secondary">Belum Lunas</span>
                                        @endif
                                    </td>
                                    <td class="d-flex gap-2">
                                        @if ($lunas)
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('kemasjidan.infaq.receipt', ['warga' => $warga->id, 'bulan' => $bulan, 'pdf' => 1]) }}">
                                                Cetak Kwitansi
                                            </a>
                                            <a class="btn btn-sm btn-outline-success"
                                                href="{{ route('kemasjidan.infaq.open-wa', ['warga' => $warga->id, 'bulan' => $bulan]) }}"
                                                target="_blank">
                                                Kirim via WhatsApp
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Belum
                                                tersedia</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.nominal-bulan');
            const totalHidden = document.getElementById('total-infaq'); // nilai murni untuk submit
            const totalDisplay = document.getElementById('total-display'); // tampilan terformat

            function number_format(number, decimals = 0, dec_point = ',', thousands_sep = '.') {
                number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = thousands_sep,
                    dec = dec_point,
                    s = '',
                    toFixedFix = function(n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }

            function formatInput(input) {
                const raw = (input.value || '').replace(/\D/g, '');
                input.dataset.raw = raw; // simpan angka murni
                input.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
            }

            function hitungTotal() {
                let total = 0;
                inputs.forEach(i => {
                    const raw = i.dataset.raw ?? i.value.replace(/\D/g, '');
                    const val = parseInt(raw || '0', 10);
                    if (!isNaN(val)) total += val;
                });
                totalHidden.value = total; // untuk submit
                totalDisplay.value = number_format(total, 0, ',', '.'); // tampilan
            }

            // init format saat load
            inputs.forEach(i => {
                formatInput(i);
                i.addEventListener('input', e => {
                    formatInput(e.target);
                    hitungTotal();
                });
                i.addEventListener('blur', e => {
                    formatInput(e.target);
                    hitungTotal();
                });
                i.addEventListener('change', hitungTotal);
            });
            hitungTotal();

            // sebelum submit: munculkan pilihan metode bayar (Tunai / Transfer)
            const form = document.getElementById('form-infaq-update');
            const metodeInput = document.getElementById('metode-bayar-detail');

            function prepareAndSubmit(metode) {
                if (metodeInput) {
                    metodeInput.value = metode || ''; // 'tunai' / 'transfer' / ''
                }

                // bersihkan semua input agar backend nerima angka murni
                inputs.forEach(i => {
                    const raw = i.dataset.raw ?? i.value.replace(/\D/g, '');
                    i.value = raw;
                });

                form.submit();
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // cek: apakah ada minimal satu bulan yang diisi (biar nggak ganggu kalau kosong semua)
                let adaIsi = false;
                inputs.forEach(i => {
                    const raw = (i.dataset.raw ?? i.value.replace(/\D/g, '')) || '0';
                    if (parseInt(raw, 10) > 0) {
                        adaIsi = true;
                    }
                });

                if (!adaIsi) {
                    // kalau nggak ada perubahan nilai, langsung submit tanpa set metode
                    prepareAndSubmit('');
                    return;
                }

                Swal.fire({
                    title: 'Pilih Metode Pembayaran',
                    html: `
                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <button id="btn-tunai" class="swal2-confirm swal2-styled"
                                style="display:block; width:100%; background:#198754;">
                                Tunai (Cash)
                            </button>

                            <button id="btn-transfer" class="swal2-confirm swal2-styled"
                                style="display:block; width:100%; background:#0d6efd;">
                                Transfer
                            </button>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button id="btn-cancel" class="swal2-cancel swal2-styled"
                                style="display:block; width:100%; background:#6c757d; margin-top:10px;">
                                Batal
                            </button>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCancelButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        const btnTunai = document.getElementById('btn-tunai');
                        const btnTransfer = document.getElementById('btn-transfer');
                        const btnCancel = document.getElementById('btn-cancel');

                        btnTunai.addEventListener('click', () => {
                            prepareAndSubmit('tunai');
                            Swal.close();
                        });

                        btnTransfer.addEventListener('click', () => {
                            prepareAndSubmit('transfer');
                            Swal.close();
                        });

                        btnCancel.addEventListener('click', () => Swal.close());
                    }
                });
            });
        });
    </script>
@endpush

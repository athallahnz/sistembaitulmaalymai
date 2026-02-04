@extends('layouts.app')

@section('title', 'Detail Infaq Warga')

@push('styles')
    <style>
        .badge-lunas {
            background: #198754;
        }

        .badge-belum {
            background: #6c757d;
        }
    </style>
@endpush

@section('content')
    <div class="container py-4">

        {{-- ===== Header ===== --}}
        <header class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="section-heading mb-1">
                    <span class="text-brown">Detail Infaq - Sdr. <strong>{{ $warga->nama ?? '-' }}</strong></span>
                </h1>
                <div class="text-muted small">
                    HP: {{ $warga->hp ?? '-' }} • RT/No: {{ $warga->rt ?? '-' }}/{{ $warga->no ?? '-' }}
                </div>
            </div>

            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('kemasjidan.infaq.index') }}" class="btn btn-outline-secondary">Kembali</a>

                {{-- tombol buka modal tambah infaq --}}
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#modalCreateInfaqDetail">
                    Tambah Infaq
                </button>
            </div>
        </header>

        {{-- ===== Alerts ===== --}}
        @if (session('success'))
            <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
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

        {{-- ===== Filter Tahun ===== --}}
        @php
            $namaBulan = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];
            $tahunSekarang = (int) ($tahun ?? now()->year);
        @endphp

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <form method="GET" action="{{ route('kemasjidan.infaq.detail', $warga->id) }}"
                    class="d-flex gap-2 align-items-center">
                    <label class="fw-semibold">Tahun</label>
                    <input type="number" name="tahun" class="form-control" style="width:130px" min="2020"
                        max="2100" value="{{ $tahunSekarang }}">
                    <button class="btn btn-outline-primary" type="submit">Terapkan</button>
                </form>

                @php
                    $totalTahun = $total ?? 0;
                @endphp

                <div class="text-end">
                    <div class="text-muted small">Total Infaq Tahun {{ $tahunSekarang }}</div>
                    <div class="fw-bold">Rp {{ number_format($totalTahun, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- ===== Tabel Status Per Bulan + Kwitansi ===== --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Status Pembayaran Per Bulan ({{ $tahunSekarang }})</h5>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 130px;">Bulan</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th style="width: 320px;">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($bulanList as $bulanAngka => $data)
                                @php
                                    $trx = $data['trx'];
                                    $nom = (float) $data['nominal'];
                                    $lunas = (bool) $data['lunas'];
                                @endphp

                                <tr>
                                    <td class="fw-semibold">{{ $data['nama'] }}</td>
                                    <td>Rp {{ number_format($nom, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($lunas)
                                            <span class="badge badge-lunas text-white">Lunas</span>
                                        @else
                                            <span class="badge badge-belum text-white">Belum</span>
                                        @endif
                                    </td>
                                    <td class="d-flex flex-wrap gap-2">
                                        @if ($lunas)
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('kemasjidan.infaq.receipt', ['warga' => $warga->id, 'tahun' => $tahunSekarang, 'bulan' => $bulanAngka, 'pdf' => 1]) }}">
                                                Cetak Kwitansi
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-infaq"
                                                data-bulan="{{ $bulanAngka }}" data-tahun="{{ $tahunSekarang }}"
                                                data-bs-toggle="modal" data-bs-target="#modalCreateInfaqDetail">
                                                Tambah Infaq
                                            </button>
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

    {{-- ================= MODAL TAMBAH INFAQ (DETAIL) ================= --}}
    <div class="modal fade" id="modalCreateInfaqDetail" tabindex="-1" aria-labelledby="modalCreateInfaqDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="form-infaq-detail" method="POST" action="{{ route('kemasjidan.infaq.store') }}">
                    @csrf

                    <input type="hidden" name="warga_id" value="{{ $warga->id }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCreateInfaqDetailLabel">Tambah Infaq Bulanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama Warga</label>
                                <input type="text" class="form-control" value="{{ $warga->nama }}" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tahun <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="tahun" id="tahun-detail-modal"
                                    min="2020" max="2100" value="{{ $tahunSekarang }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Bulan <span class="text-danger">*</span></label>
                                <select class="form-select" name="bulan" id="bulan-detail-modal" required>
                                    @foreach ($namaBulan as $k => $v)
                                        <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                                <div id="paid-hint-detail" class="form-text text-danger d-none">
                                    Infaq bulan & tahun ini sudah ada.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="nominal" id="nominal-detail-modal"
                                        step="10000" min="1" required>
                                </div>
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach ([50000, 100000, 200000, 500000] as $n)
                                        <button type="button" class="btn btn-sm btn-outline-primary set-nominal-detail"
                                            data-amount="{{ $n }}">
                                            {{ number_format($n, 0, ',', '.') }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran</label>
                                <select class="form-select" name="metode_bayar" id="metode-detail-modal">
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="tunai">Tunai</option>
                                    <option value="transfer">Transfer</option>
                                </select>

                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="form-label">Sumber (opsional)</label>
                                        <input type="text" class="form-control" name="sumber"
                                            placeholder="Kotak infaq/transfer/dll">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Keterangan (opsional)</label>
                                        <input type="text" class="form-control" name="keterangan"
                                            placeholder="Catatan tambahan">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-detail">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-infaq-detail');
            const btnSubmit = document.getElementById('btn-submit-detail');
            const paidHint = document.getElementById('paid-hint-detail');

            const wargaId = {{ (int) $warga->id }};
            const inpTahun = document.getElementById('tahun-detail-modal');
            const selBulan = document.getElementById('bulan-detail-modal');

            // quick nominal
            document.querySelectorAll('.set-nominal-detail').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('nominal-detail-modal').value = btn.dataset.amount;
                });
            });

            // ketika klik "Tambah Infaq" dari tabel, set bulan otomatis
            document.querySelectorAll('.btn-add-infaq').forEach(btn => {
                btn.addEventListener('click', () => {
                    const b = btn.dataset.bulan;
                    const t = btn.dataset.tahun;
                    if (inpTahun) inpTahun.value = t;
                    if (selBulan) selBulan.value = b;
                    checkPaid();
                });
            });

            async function checkPaid() {
                const tahun = (inpTahun?.value || '').trim();
                const bulan = (selBulan?.value || '').trim();
                if (!tahun || !bulan) return;

                const url =
                    `{{ route('kemasjidan.infaq.check') }}?warga_id=${encodeURIComponent(wargaId)}&tahun=${encodeURIComponent(tahun)}&bulan=${encodeURIComponent(bulan)}`;

                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                // DEBUG penting:
                console.log('checkPaid status:', res.status, 'url:', url);
                const text = await res.text();
                console.log('checkPaid raw response:', text);

                // kalau response bukan JSON, ini akan kelihatan di console
                const data = JSON.parse(text);

                const paid = !!data.paid;
                paidHint.classList.toggle('d-none', !paid);
                btnSubmit.disabled = paid;
            }

            if (inpTahun) inpTahun.addEventListener('input', checkPaid);
            if (selBulan) selBulan.addEventListener('change', checkPaid);

            // cek awal saat modal dibuka
            const modalEl = document.getElementById('modalCreateInfaqDetail');
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', () => {
                    paidHint.classList.add('d-none');
                    btnSubmit.disabled = false;
                    checkPaid();
                });
            }
        });
    </script>
@endpush

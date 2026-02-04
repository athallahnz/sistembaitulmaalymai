@extends('layouts.app')

@section('title', 'Detail Iuran Warga')

@push('styles')
    <style>
        .badge-lunas {
            background-color: #198754;
            color: #fff;
        }

        .badge-sebagian {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-belum {
            background-color: #6c757d;
            color: #fff;
        }

        .card-summary {
            border-left: 4px solid var(--brand-brown, #622200);
        }

        .td-aksi {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="container py-4">
        {{-- ===== Header ===== --}}
        <header class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="section-heading mb-1">
                    <span class="text-brown">
                        Detail Iuran - KK <strong>{{ $warga->nama ?? '-' }}</strong>
                    </span>
                </h1>
                <div class="small text-muted">
                    RT {{ $warga->rt ?? '-' }} • {{ $warga->alamat ?? 'Alamat belum diisi' }} • Tahun {{ $tahun }}
                </div>
            </div>

            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('sosial.iuran.index', ['tahun' => $tahun]) }}" class="btn btn-outline-secondary">
                    Kembali
                </a>
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

        @php
            $totalTagihan = 0;
            $totalBayar = 0;
            $lunasCount = 0;

            foreach ($bulanList as $info) {
                if ($info['data']) {
                    $totalTagihan += (int) $info['data']->nominal_tagihan;
                    $totalBayar += (int) $info['data']->nominal_bayar;
                    if ($info['status'] === \App\Models\IuranBulanan::STATUS_LUNAS) {
                        $lunasCount++;
                    }
                }
            }
        @endphp

        {{-- ===== Ringkasan Iuran KK (Tahun ini) ===== --}}
        <div class="card glass shadow-sm border-0 mb-4 card-summary">
            <div class="card-body">
                <h5 class="mb-3">Ringkasan Iuran Tahun {{ $tahun }}</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Total Tagihan</div>
                        <div class="h5 mb-0">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Total Terbayar</div>
                        <div class="h5 mb-0 text-success">Rp {{ number_format($totalBayar, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Sisa</div>
                        @php $sisa = max($totalTagihan - $totalBayar, 0); @endphp
                        <div class="h5 mb-0 text-danger">Rp {{ number_format($sisa, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Progress Bulan Lunas</div>
                        <div class="h6 mb-1">{{ $lunasCount }}/12 bulan</div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                style="width: {{ ($lunasCount / 12) * 100 }}%;" aria-valuenow="{{ $lunasCount }}"
                                aria-valuemin="0" aria-valuemax="12">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Detail Per Bulan ===== --}}
        <div class="card glass shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Status Iuran Per Bulan ({{ $tahun }})</h5>

                    {{-- Optional: tombol tambah (kalau ingin add manual dari detail) --}}
                    <button type="button" class="btn btn-sm btn-primary btn-add-iuran" data-bs-toggle="modal"
                        data-bs-target="#modalCreateIuran" data-warga="{{ $warga->id }}"
                        data-tahun="{{ $tahun }}" data-bulan="{{ now()->month }}" data-tagihan="0" data-bayar="0"
                        data-metode="">
                        <i class="bi bi-plus-circle"></i> Tambah
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 16%;">Bulan</th>
                                <th style="width: 16%;">Tagihan (Rp)</th>
                                <th style="width: 16%;">Terbayar (Rp)</th>
                                <th style="width: 16%;">Status</th>
                                <th style="width: 16%;">Tanggal Bayar</th>
                                <th style="width: 20%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bulanList as $bulanNum => $info)
                                @php
                                    /** @var \App\Models\IuranBulanan|null $row */
                                    $row = $info['data'];
                                    $status = $info['status'];

                                    $tagihan = $row ? (int) $row->nominal_tagihan : 0;
                                    $bayar = $row ? (int) $row->nominal_bayar : 0;
                                    $tanggal =
                                        $row && $row->tanggal_bayar ? $row->tanggal_bayar->format('d-m-Y') : null;

                                    $badgeClass = 'badge-belum';
                                    $label = 'Belum Bayar';

                                    if ($status === \App\Models\IuranBulanan::STATUS_LUNAS) {
                                        $badgeClass = 'badge-lunas';
                                        $label = 'Lunas';
                                    } elseif ($status === \App\Models\IuranBulanan::STATUS_SEBAGIAN) {
                                        $badgeClass = 'badge-sebagian';
                                        $label = 'Sebagian';
                                    }
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $info['nama'] }}</td>
                                    <td>Rp {{ number_format($tagihan, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($bayar, 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $badgeClass }}">{{ $label }}</span></td>
                                    <td>{{ $tanggal ?? '—' }}</td>
                                    <td class="td-aksi text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-iuran"
                                            data-bs-toggle="modal" data-bs-target="#modalCreateIuran"
                                            data-warga="{{ $warga->id }}" data-tahun="{{ $tahun }}"
                                            data-bulan="{{ $bulanNum }}" data-tagihan="{{ $tagihan }}"
                                            data-bayar="{{ $bayar }}"
                                            data-metode="{{ $row?->metode_bayar ?? '' }}">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="{{ route('sosial.iuran.index', ['tahun' => $tahun]) }}"
                        class="btn btn-outline-secondary btn-sm">
                        Kembali ke daftar iuran
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal (dipanggil sekali) --}}
    @include('bidang.sosial.iuran._modal_add_iuran', ['tahun' => $tahun])
@endsection

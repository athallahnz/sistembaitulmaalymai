@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-2">
            Laporan Aktivitas —
            @if ($role === 'Bidang')
                <strong>Bidang {{ auth()->user()->bidang->name ?? $bidangId }}</strong>
            @else
                <strong>Yayasan</strong>
            @endif
        </h1>

        {{-- Filter + Export --}}
        <form method="GET" action="{{ route('laporan.aktivitas') }}" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                        value="{{ request('start_date', $startDate ?? \Carbon\Carbon::now()->startOfMonth()->toDateString()) }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                        value="{{ request('end_date', $endDate ?? \Carbon\Carbon::now()->endOfMonth()->toDateString()) }}">
                </div>
                <div class="col-md-4 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>

                    {{-- Export PDF --}}
                    <a class="btn btn-outline-danger"
                        href="{{ route('laporan.aktivitas.export.pdf', ['start_date' => request('start_date', $startDate ?? null), 'end_date' => request('end_date', $endDate ?? null)]) }}">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </a>

                    {{-- Export Excel --}}
                    <a class="btn btn-outline-success"
                        href="{{ route('laporan.aktivitas.export.excel', ['start_date' => request('start_date', $startDate ?? null), 'end_date' => request('end_date', $endDate ?? null)]) }}">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                    </a>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2" class="fw-bold">PENDAPATAN</td>
                            </tr>
                            <tr>
                                <td>Donasi (202*)</td>
                                <td class="text-end">{{ number_format($pendapatanDonasi, 2, ',', '.') }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td class="text-end">Total Pendapatan</td>
                                <td class="text-end">{{ number_format($totalPendapatan, 2, ',', '.') }}</td>
                            </tr>

                            <tr>
                                <td colspan="2" class="fw-bold pt-3">BEBAN</td>
                            </tr>
                            @php
                                $labelBeban = [
                                    302 => 'Beban Gaji (302*)',
                                    303 => 'Biaya Operasional (303*)',
                                    304 => 'Biaya Kegiatan Siswa (304*)',
                                    305 => 'Biaya Pemeliharaan (305*)',
                                    306 => 'Biaya Sosial (306*)',
                                    307 => 'Perlengkapan Extra (307*)',
                                    308 => 'Seragam (308*)',
                                    309 => 'Peningkatan SDM (309*)',
                                    310 => 'Biaya Dibayar Dimuka (310*)',
                                ];
                            @endphp

                            @foreach ($labelBeban as $pid => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end">{{ number_format($rincianBeban[$pid] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach

                            <tr class="table-light fw-bold">
                                <td class="text-end">Total Beban</td>
                                <td class="text-end">{{ number_format($totalBeban, 2, ',', '.') }}</td>
                            </tr>

                            <tr class="table-success fw-bold">
                                <td class="text-end">Surplus / (Defisit) Periode</td>
                                <td class="text-end">{{ number_format($surplus, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small">
                    Catatan: Laporan Aktivitas mengikuti PSAK 45. Donasi diperlakukan sebagai pendapatan.
                    Jika kamu memakai pembatasan dana (terikat / tidak terikat), kita bisa pecah pendapatan per kategori
                    pembatasan
                    dengan menandai akun pendapatan pada COA dan memetakan parent terikat vs tidak terikat.
                </p>
            </div>
        </div>
    </div>
@endsection

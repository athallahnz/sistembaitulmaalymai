@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-2">
            @if ($role === 'Bidang')
                Laporan Aktivitas — <strong>Bidang {{ auth()->user()->bidang->name ?? $bidangId }}</strong>
            @else
                Laporan Aktivitas — <strong>Yayasan</strong>
            @endif
        </h1>

        {{-- Filter + Export --}}
        <form method="GET" action="{{ route('laporan.aktivitas') }}" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                        value="{{ request('start_date', optional($startDate)->toDateString()) }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                        value="{{ request('end_date', optional($endDate)->toDateString()) }}">
                </div>
                <div class="col-md-4 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>

                    {{-- Export Aktivitas - PDF --}}
                    <a class="btn btn-outline-danger"
                        href="{{ route('laporan.aktivitas.export.pdf') . (request()->all() ? '?' . http_build_query(request()->all()) : '') }}">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </a>

                    {{-- Export Aktivitas - Excel --}}
                    <a class="btn btn-outline-success"
                        href="{{ route('laporan.aktivitas.export.excel') . (request()->all() ? '?' . http_build_query(request()->all()) : '') }}">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                    </a>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 200px">Kode</th>
                                <th>Akun</th>
                                <th class="text-end">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- ===================== PENDAPATAN ===================== --}}
                            <tr>
                                <td colspan="3" class="fw-bold">PENDAPATAN</td>
                            </tr>

                            {{-- Pendapatan Tidak Terikat --}}
                            <tr>
                                <td colspan="3" class="text-muted">Pendapatan Tidak Terikat</td>
                            </tr>
                            @php $pdTT = $pendapatan['tidak_terikat'] ?? []; @endphp
                            @forelse ($pdTT as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format(abs($row['saldo']), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada pendapatan tidak terikat.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Pendapatan Tidak Terikat</td>
                                <td class="text-end">
                                    {{ number_format(abs($totalPendapatan['tidak_terikat'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- Pendapatan Terikat Temporer --}}
                            <tr>
                                <td colspan="3" class="text-muted pt-3">Pendapatan Terikat Temporer</td>
                            </tr>
                            @php $pdTemp = $pendapatan['terikat_temporer'] ?? []; @endphp
                            @forelse ($pdTemp as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format(abs($row['saldo']), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada pendapatan terikat temporer.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Pendapatan Terikat Temporer</td>
                                <td class="text-end">
                                    {{ number_format(abs($totalPendapatan['terikat_temporer'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- Pendapatan Terikat Permanen --}}
                            <tr>
                                <td colspan="3" class="text-muted pt-3">Pendapatan Terikat Permanen</td>
                            </tr>
                            @php $pdPerm = $pendapatan['terikat_permanen'] ?? []; @endphp
                            @forelse ($pdPerm as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format(abs($row['saldo']), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada pendapatan terikat permanen.
                                    </td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Pendapatan Terikat Permanen</td>
                                <td class="text-end">
                                    {{ number_format(abs($totalPendapatan['terikat_permanen'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- ===================== BEBAN ===================== --}}
                            <tr>
                                <td colspan="3" class="fw-bold pt-3">BEBAN</td>
                            </tr>

                            {{-- Beban Tidak Terikat --}}
                            <tr>
                                <td colspan="3" class="text-muted">Beban Tidak Terikat</td>
                            </tr>
                            @php $bbTT = $bebanTidakTerikat ?? []; @endphp
                            @forelse ($bbTT as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format(abs($row['saldo']), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada beban tidak terikat.</td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Beban Tidak Terikat</td>
                                <td class="text-end">
                                    {{ number_format($totalBebanTidakTerikat ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- Beban Terikat Temporer --}}
                            <tr>
                                <td colspan="3" class="text-muted pt-3">Beban Terikat Temporer</td>
                            </tr>
                            @php $bbTemp = $bebanTerikatTemporer ?? []; @endphp
                            @forelse ($bbTemp as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format(abs($row['saldo']), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada beban terikat temporer.</td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Beban Terikat Temporer</td>
                                <td class="text-end">
                                    {{ number_format($totalBebanTemporer ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- Beban Terikat Permanen --}}
                            <tr>
                                <td colspan="3" class="text-muted pt-3">Beban Terikat Permanen</td>
                            </tr>
                            @php $bbPerm = $bebanTerikatPermanen ?? []; @endphp
                            @forelse ($bbPerm as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format(abs($row['saldo']), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada beban terikat permanen.</td>
                                </tr>
                            @endforelse
                            @php
                                $grandTotalBeban =
                                    ($totalBebanTidakTerikat ?? 0) +
                                    ($totalBebanTemporer ?? 0) +
                                    ($totalBebanPermanen ?? 0);
                            @endphp
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Beban Terikat Permanen</td>
                                <td class="text-end">
                                    {{ number_format($totalBebanPermanen ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr class="table-secondary fw-bold">
                                <td colspan="2" class="text-end">Total Seluruh Beban</td>
                                <td class="text-end">
                                    {{ number_format($grandTotalBeban, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- ===================== PERUBAHAN ASET NETO ===================== --}}
                            <tr>
                                <td colspan="3" class="fw-bold pt-3">PERUBAHAN ASET NETO</td>
                            </tr>

                            <tr>
                                <td colspan="2">Kenaikan (Penurunan) Aset Neto Tidak Terikat</td>
                                <td class="text-end">
                                    {{ number_format($perubahanTidakTerikat ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">Kenaikan (Penurunan) Aset Neto Terikat Temporer</td>
                                <td class="text-end">
                                    {{ number_format($perubahanTemporer ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">Kenaikan (Penurunan) Aset Neto Terikat Permanen</td>
                                <td class="text-end">
                                    {{ number_format($perubahanPermanen ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr class="table-secondary fw-bold">
                                <td colspan="2">Jumlah Perubahan Aset Neto</td>
                                <td class="text-end">
                                    {{ number_format($totalPerubahanAsetNeto ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection

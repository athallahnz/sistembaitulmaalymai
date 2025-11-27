@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-2">
            @if ($role === 'Bidang')
                Neraca (Posisi Keuangan) — <strong>Bidang {{ auth()->user()->bidang->name ?? $bidangId }}</strong>
            @else
                Neraca (Posisi Keuangan) — <strong>Yayasan</strong>
            @endif
        </h1>

        {{-- Filter + Export --}}
        <form method="GET" action="{{ route('laporan.posisi-keuangan') }}" class="mb-4">
            <div class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label for="end_date" class="form-label">Per Tanggal</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                        value="{{ request('end_date', optional($endDate)->toDateString()) }}">
                </div>

                <div class="col-md-8 d-flex gap-2 justify-content-md-end">

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>

                    {{-- Export Posisi Keuangan - PDF --}}
                    <a class="btn btn-outline-danger"
                        href="{{ route('laporan.posisi-keuangan.export.pdf') . (request()->all() ? '?' . http_build_query(request()->all()) : '') }}">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </a>

                    {{-- Export Posisi Keuangan - Excel --}}
                    <a class="btn btn-outline-success"
                        href="{{ route('laporan.posisi-keuangan.export.excel') . (request()->all() ? '?' . http_build_query(request()->all()) : '') }}">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                    </a>

                </div>

            </div>
        </form>

        <div class="card">
            <div class="card-body">
                {{-- Info balancing --}}
                @if (abs($selisih) > 0.01)
                    <div class="alert alert-warning">
                        <strong>Perhatian!</strong> Total Aset tidak sama dengan Total Liabilitas + Aset Neto.
                        Selisih: {{ number_format($selisih, 2, ',', '.') }}
                    </div>
                @endif

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
                            {{-- ===================== ASET ===================== --}}
                            <tr>
                                <td colspan="3" class="fw-bold">ASET</td>
                            </tr>

                            {{-- Aset Lancar --}}
                            <tr>
                                <td colspan="3" class="text-muted">Aset Lancar</td>
                            </tr>
                            @php $asetLancar = $kelompok['aset_lancar'] ?? []; @endphp
                            @forelse ($asetLancar as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada akun aset lancar.</td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Aset Lancar</td>
                                <td class="text-end">{{ number_format($total['aset_lancar'] ?? 0, 0, ',', '.') }}</td>
                            </tr>

                            {{-- Aset Tidak Lancar --}}
                            <tr>
                                <td colspan="3" class="text-muted pt-3">Aset Tidak Lancar</td>
                            </tr>
                            @php $asetTidakLancar = $kelompok['aset_tidak_lancar'] ?? []; @endphp
                            @forelse ($asetTidakLancar as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada akun aset tidak lancar.</td>
                                </tr>
                            @endforelse
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Jumlah Aset Tidak Lancar</td>
                                <td class="text-end">{{ number_format($total['aset_tidak_lancar'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr class="table-secondary fw-bold">
                                <td colspan="2" class="text-end">Total Aset</td>
                                <td class="text-end">{{ number_format($totalAset ?? 0, 0, ',', '.') }}</td>
                            </tr>

                            {{-- =================== LIABILITAS =================== --}}
                            <tr>
                                <td colspan="3" class="fw-bold pt-3">LIABILITAS</td>
                            </tr>

                            {{-- Liabilitas Jangka Pendek --}}
                            <tr>
                                <td colspan="3" class="text-muted">Liabilitas Jangka Pendek</td>
                            </tr>
                            @php $ljs = $kelompok['liabilitas_jangka_pendek'] ?? []; @endphp
                            @forelse ($ljs as $row)
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted fst-italic">Tidak ada liabilitas jangka pendek.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Liabilitas Jangka Panjang (kalau ada) --}}
                            @php $ljp = $kelompok['liabilitas_jangka_panjang'] ?? []; @endphp
                            @if (!empty($ljp))
                                <tr>
                                    <td colspan="3" class="text-muted pt-3">Liabilitas Jangka Panjang</td>
                                </tr>
                                @foreach ($ljp as $row)
                                    <tr>
                                        <td>{{ $row['akun']->kode_akun }}</td>
                                        <td>{{ $row['akun']->nama_akun }}</td>
                                        <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Total Liabilitas</td>
                                <td class="text-end">{{ number_format($totalLiabilitas ?? 0, 0, ',', '.') }}</td>
                            </tr>

                            {{-- =================== ASET NETO =================== --}}
                            <tr class="fw-bold pt-3">
                                <td colspan="3">ASET NETO</td>
                            </tr>

                            {{-- Aset Neto Tidak Terikat --}}
                            @php $anTT = $kelompok['aset_neto_tidak_terikat'] ?? []; @endphp
                            @if (!empty($anTT))
                                <tr>
                                    <td colspan="3" class="text-muted">Aset Neto Tidak Terikat</td>
                                </tr>
                                @foreach ($anTT as $row)
                                    <tr>
                                        <td>{{ $row['akun']->kode_akun }}</td>
                                        <td>{{ $row['akun']->nama_akun }}</td>
                                        <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            {{-- Aset Neto Terikat Temporer --}}
                            @php $anTemp = $kelompok['aset_neto_terikat_temporer'] ?? []; @endphp
                            @if (!empty($anTemp))
                                <tr>
                                    <td colspan="3" class="text-muted pt-2">Aset Neto Terikat Temporer</td>
                                </tr>
                                @foreach ($anTemp as $row)
                                    <tr>
                                        <td>{{ $row['akun']->kode_akun }}</td>
                                        <td>{{ $row['akun']->nama_akun }}</td>
                                        <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            {{-- Aset Neto Terikat Permanen --}}
                            @php $anPerm = $kelompok['aset_neto_terikat_permanen'] ?? []; @endphp
                            @if (!empty($anPerm))
                                <tr>
                                    <td colspan="3" class="text-muted pt-2">Aset Neto Terikat Permanen</td>
                                </tr>
                                @foreach ($anPerm as $row)
                                    <tr>
                                        <td>{{ $row['akun']->kode_akun }}</td>
                                        <td>{{ $row['akun']->nama_akun }}</td>
                                        <td class="text-end">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Total Aset Neto</td>
                                <td class="text-end">{{ number_format($totalAsetNeto ?? 0, 0, ',', '.') }}</td>
                            </tr>

                            <tr class="table-secondary fw-bold">
                                <td colspan="2" class="text-end">Total Liabilitas dan Aset Neto</td>
                                <td class="text-end">
                                    {{ number_format(($totalLiabilitas ?? 0) + ($totalAsetNeto ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Summary khusus Bendahara --}}
                @if ($role === 'Bendahara')
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="small text-muted">Total Kas (Semua)</div>
                                <div class="fs-5 fw-bold">
                                    Rp {{ number_format($saldoKasTotal ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="small text-muted">Total Bank (Semua)</div>
                                <div class="fs-5 fw-bold">
                                    Rp {{ number_format($saldoBankTotal ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="small text-muted">Total Keuangan (Kas+Bank)</div>
                                <div class="fs-5 fw-bold">
                                    Rp {{ number_format($totalKeuanganSemuaBidang ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

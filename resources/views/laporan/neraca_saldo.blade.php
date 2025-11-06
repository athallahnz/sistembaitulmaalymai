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
        <form method="GET" action="{{ route('laporan.neraca-saldo') }}" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                        value="{{ request('start_date', $startDate ?? null) }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                        value="{{ request('end_date', $endDate ?? \Carbon\Carbon::now()->toDateString()) }}">
                </div>
                <div class="col-md-4 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>

                    {{-- Export PDF --}}
                    <a class="btn btn-outline-danger"
                        href="{{ route('laporan.neraca.export.pdf', ['start_date' => request('start_date', $startDate ?? null), 'end_date' => request('end_date', $endDate ?? \Carbon\Carbon::now()->toDateString())]) }}">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </a>

                    {{-- Export Excel --}}
                    <a class="btn btn-outline-success"
                        href="{{ route('laporan.neraca.export.excel', ['start_date' => request('start_date', $startDate ?? null), 'end_date' => request('end_date', $endDate ?? \Carbon\Carbon::now()->toDateString())]) }}">
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
                            <tr>
                                <td colspan="3" class="fw-bold">ASET</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-muted">Aset Lancar</td>
                            </tr>
                            <tr>
                                <td>101</td>
                                <td>Kas</td>
                                <td class="text-end">{{ number_format($saldoKas, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>102</td>
                                <td>Bank</td>
                                <td class="text-end">{{ number_format($saldoBank, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-muted">Aset Tidak Lancar</td>
                            </tr>
                            <tr>
                                <td>103</td>
                                <td>Piutang (outstanding)</td>
                                <td class="text-end">{{ number_format($piutang, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>104</td>
                                <td>Tanah & Bangunan</td>
                                <td class="text-end">{{ number_format($tanahBangunan, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>105</td>
                                <td>Inventaris</td>
                                <td class="text-end">{{ number_format($inventaris, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Total Aset</td>
                                <td class="text-end">{{ number_format($totalAset, 0, ',', '.') }}</td>
                            </tr>

                            <tr>
                                <td colspan="3" class="fw-bold pt-3">LIABILITAS</td>
                            </tr>
                            <tr>
                                <td>2xx</td>
                                <td>Hutang</td>
                                <td class="text-end">{{ number_format($hutang, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>203</td>
                                <td>Pendapatan Belum Diterima</td>
                                <td class="text-end">{{ number_format($pendapatanBelumDiterima, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Total Liabilitas</td>
                                <td class="text-end">{{ number_format($totalLiabilitas, 0, ',', '.') }}</td>
                            </tr>

                            <tr class="fw-bold pt-3">
                                <td colspan="3">ASET BERSIH</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Aset Bersih (Aset - Liabilitas)</td>
                                <td class="text-end">{{ number_format($asetBersih, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if ($role === 'Bendahara')
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="small text-muted">Total Kas (Semua)</div>
                                <div class="fs-5 fw-bold">Rp {{ number_format($saldoKasTotal ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="small text-muted">Total Bank (Semua)</div>
                                <div class="fs-5 fw-bold">Rp {{ number_format($saldoBankTotal ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="small text-muted">Total Keuangan (Kas+Bank)</div>
                                <div class="fs-5 fw-bold">Rp
                                    {{ number_format($totalKeuanganSemuaBidang ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

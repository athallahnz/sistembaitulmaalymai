@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-2">
            @if ($role === 'Bidang')
                Laporan Arus Kas — <strong>Bidang {{ auth()->user()->bidang->name ?? $bidangId }}</strong>
            @else
                Laporan Arus Kas — <strong>Yayasan</strong>
            @endif
        </h1>

        {{-- Filter + Export --}}
        <form method="GET" action="{{ route('laporan.arus-kas') }}" class="mb-4">
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

                    {{-- Export Arus Kas - PDF --}}
                    <a class="btn btn-outline-danger"
                        href="{{ route('laporan.arus-kas.export.pdf') . (request()->all() ? '?' . http_build_query(request()->all()) : '') }}">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </a>

                    {{-- Export Arus Kas - Excel --}}
                    <a class="btn btn-outline-success"
                        href="{{ route('laporan.arus-kas.export.excel') . (request()->all() ? '?' . http_build_query(request()->all()) : '') }}">
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
                                <th style="width: 160px">Kode</th>
                                <th>Uraian</th>
                                <th class="text-end" style="width: 140px">Debit</th>
                                <th class="text-end" style="width: 140px">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- ===================== ARUS KAS DARI AKTIVITAS OPERASIONAL ===================== --}}
                            <tr>
                                <td colspan="4" class="fw-bold">ARUS KAS DARI AKTIVITAS OPERASIONAL</td>
                            </tr>

                            @php
                                $oper = $arus['operasional'] ?? [];
                                $operDebit = collect($oper)->sum(function ($row) {
                                    return $row['saldo'] > 0 ? $row['saldo'] : 0;
                                });
                                $operKredit = collect($oper)->sum(function ($row) {
                                    return $row['saldo'] < 0 ? abs($row['saldo']) : 0;
                                });
                            @endphp

                            @forelse ($oper as $row)
                                @php
                                    $amount = $row['saldo'];
                                    $debit = $amount > 0 ? $amount : 0;
                                    $kredit = $amount < 0 ? abs($amount) : 0;
                                @endphp
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">
                                        {{ $debit ? number_format($debit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $kredit ? number_format($kredit, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted fst-italic">Tidak ada arus kas operasional.</td>
                                </tr>
                            @endforelse

                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">
                                    Jumlah Arus Kas Operasional
                                </td>
                                <td class="text-end">
                                    {{ number_format($operDebit, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($operKredit, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fst-italic">
                                    Kas Bersih Diperoleh dari (Digunakan untuk) Aktivitas Operasional
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format($totalOperasional ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- ===================== ARUS KAS DARI AKTIVITAS INVESTASI ===================== --}}
                            <tr>
                                <td colspan="4" class="fw-bold pt-3">ARUS KAS DARI AKTIVITAS INVESTASI</td>
                            </tr>

                            @php
                                $inv = $arus['investasi'] ?? [];
                                $invDebit = collect($inv)->sum(function ($row) {
                                    return $row['saldo'] > 0 ? $row['saldo'] : 0;
                                });
                                $invKredit = collect($inv)->sum(function ($row) {
                                    return $row['saldo'] < 0 ? abs($row['saldo']) : 0;
                                });
                            @endphp

                            @forelse ($inv as $row)
                                @php
                                    $amount = $row['saldo'];
                                    $debit = $amount > 0 ? $amount : 0;
                                    $kredit = $amount < 0 ? abs($amount) : 0;
                                @endphp
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">
                                        {{ $debit ? number_format($debit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $kredit ? number_format($kredit, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted fst-italic">Tidak ada arus kas investasi.</td>
                                </tr>
                            @endforelse

                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">
                                    Jumlah Arus Kas Investasi
                                </td>
                                <td class="text-end">
                                    {{ number_format($invDebit, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($invKredit, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fst-italic">
                                    Kas Bersih Diperoleh dari (Digunakan untuk) Aktivitas Investasi
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format($totalInvestasi ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- ===================== ARUS KAS DARI AKTIVITAS PENDANAAN ===================== --}}
                            <tr>
                                <td colspan="4" class="fw-bold pt-3">ARUS KAS DARI AKTIVITAS PENDANAAN</td>
                            </tr>

                            @php
                                $pend = $arus['pendanaan'] ?? [];
                                $pendDebit = collect($pend)->sum(function ($row) {
                                    return $row['saldo'] > 0 ? $row['saldo'] : 0;
                                });
                                $pendKredit = collect($pend)->sum(function ($row) {
                                    return $row['saldo'] < 0 ? abs($row['saldo']) : 0;
                                });
                            @endphp

                            @forelse ($pend as $row)
                                @php
                                    $amount = $row['saldo'];
                                    $debit = $amount > 0 ? $amount : 0;
                                    $kredit = $amount < 0 ? abs($amount) : 0;
                                @endphp
                                <tr>
                                    <td>{{ $row['akun']->kode_akun }}</td>
                                    <td>{{ $row['akun']->nama_akun }}</td>
                                    <td class="text-end">
                                        {{ $debit ? number_format($debit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $kredit ? number_format($kredit, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted fst-italic">Tidak ada arus kas pendanaan.</td>
                                </tr>
                            @endforelse

                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">
                                    Jumlah Arus Kas Pendanaan
                                </td>
                                <td class="text-end">
                                    {{ number_format($pendDebit, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($pendKredit, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fst-italic">
                                    Kas Bersih Diperoleh dari (Digunakan untuk) Aktivitas Pendanaan
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format($totalPendanaan ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- ===================== KENAIKAN (PENURUNAN) KAS ===================== --}}
                            <tr class="table-secondary fw-bold">
                                <td colspan="3">Kenaikan (Penurunan) Bersih Kas dan Setara Kas</td>
                                <td class="text-end">
                                    {{ number_format($kenaikanPenurunanKas ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- ===================== REKONSILIASI SALDO KAS (PSAK 2 lengkap) ===================== --}}
                            <tr>
                                <td colspan="4" class="py-3"></td>
                            </tr>

                            <tr class="fw-bold">
                                <td colspan="4">REKONSILIASI SALDO KAS DAN SETARA KAS</td>
                            </tr>

                            <tr>
                                <td colspan="3">Saldo Kas & Setara Kas Awal Periode</td>
                                <td class="text-end">
                                    {{ number_format($openingCash ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr>
                                <td colspan="3">Kenaikan (Penurunan) Bersih Kas Selama Periode</td>
                                <td class="text-end">
                                    {{ number_format($kenaikanPenurunanKas ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr class="table-light fw-bold">
                                <td colspan="3">Saldo Kas & Setara Kas Akhir Periode</td>
                                <td class="text-end">
                                    {{ number_format($endingCash ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- Optional: cek silang --}}
                            @if (abs($endingCash - $openingCash - $kenaikanPenurunanKas) > 5)
                                <tr class="table-danger">
                                    <td colspan="4" class="text-danger fw-bold">
                                        ⚠️ Warning: Selisih rekonsiliasi kas tidak sesuai! Periksa transaksi kas/bank.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

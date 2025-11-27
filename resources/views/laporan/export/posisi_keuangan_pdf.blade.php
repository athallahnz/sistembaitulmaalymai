<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Posisi Keuangan</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 24px;
            color: #222;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            padding: 0;
        }

        .text-center {
            text-align: center;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-3 {
            margin-top: 12px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mb-1 {
            margin-bottom: 4px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-3 {
            margin-bottom: 12px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            padding: 4px 6px;
            border: 1px solid #ddd;
        }

        th {
            background: #f5f5f5;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .section-header {
            background: #efefef;
            font-weight: bold;
        }

        .muted {
            color: #777;
            font-style: italic;
        }

        .summary-row {
            background: #eaeaea;
            font-weight: bold;
        }
    </style>
</head>

<body>
    {{-- HEADER --}}
    <div class="text-center mb-4">
        <h2>Yayasan Masjid Al Iman</h2>
        <h3 class="mt-1">Laporan Posisi Keuangan</h3>
        <div class="mt-1">
            <span>Per {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</span>
        </div>
        @if ($role === 'Bidang')
            <div class="mt-1">
                <strong>Bidang: {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}</strong>
            </div>
        @else
            <div class="mt-1">
                <strong>Level: Bendahara / Yayasan</strong>
            </div>
        @endif
    </div>

    {{-- ASET --}}
    <h4>A. ASET</h4>
    <table>
        <thead>
            <tr>
                <th style="width: 15%">Kode</th>
                <th>Nama Akun</th>
                <th style="width: 25%" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Aset Lancar --}}
            <tr>
                <td colspan="3" class="section-header">Aset Lancar</td>
            </tr>
            @php $totalAsetLancar = $total['aset_lancar'] ?? 0; @endphp
            @forelse($kelompok['aset_lancar'] as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }}</td>
                    <td>{{ $row['akun']->nama_akun }}</td>
                    <td class="text-right">
                        {{ number_format($row['saldo'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">Tidak ada akun aset lancar.</td>
                </tr>
            @endforelse
            <tr class="summary-row">
                <td colspan="2">Jumlah Aset Lancar</td>
                <td class="text-right">
                    {{ number_format($totalAsetLancar, 0, ',', '.') }}
                </td>
            </tr>

            {{-- Aset Tidak Lancar --}}
            <tr>
                <td colspan="3" class="section-header">Aset Tidak Lancar</td>
            </tr>
            @php $totalAsetTidakLancar = $total['aset_tidak_lancar'] ?? 0; @endphp
            @forelse($kelompok['aset_tidak_lancar'] as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }}</td>
                    <td>{{ $row['akun']->nama_akun }}</td>
                    <td class="text-right">
                        {{ number_format($row['saldo'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">Tidak ada akun aset tidak lancar.</td>
                </tr>
            @endforelse
            <tr class="summary-row">
                <td colspan="2">Jumlah Aset Tidak Lancar</td>
                <td class="text-right">
                    {{ number_format($totalAsetTidakLancar, 0, ',', '.') }}
                </td>
            </tr>

            {{-- TOTAL ASET --}}
            <tr class="summary-row">
                <td colspan="2">TOTAL ASET</td>
                <td class="text-right">
                    {{ number_format($totalAset, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- LIABILITAS --}}
    <h4 class="mt-3">B. LIABILITAS</h4>
    <table>
        <thead>
            <tr>
                <th style="width: 15%">Kode</th>
                <th>Nama Akun</th>
                <th style="width: 25%" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Liabilitas Jangka Pendek --}}
            <tr>
                <td colspan="3" class="section-header">Liabilitas Jangka Pendek</td>
            </tr>
            @php $totalLJP = $total['liabilitas_jangka_pendek'] ?? 0; @endphp
            @forelse($kelompok['liabilitas_jangka_pendek'] as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }}</td>
                    <td>{{ $row['akun']->nama_akun }}</td>
                    <td class="text-right">
                        {{ number_format($row['saldo'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">Tidak ada liabilitas jangka pendek.</td>
                </tr>
            @endforelse
            <tr class="summary-row">
                <td colspan="2">Jumlah Liabilitas Jangka Pendek</td>
                <td class="text-right">
                    {{ number_format($totalLJP, 0, ',', '.') }}
                </td>
            </tr>

            {{-- Liabilitas Jangka Panjang --}}
            <tr>
                <td colspan="3" class="section-header">Liabilitas Jangka Panjang</td>
            </tr>
            @php $totalLJPanjang = $total['liabilitas_jangka_panjang'] ?? 0; @endphp
            @forelse($kelompok['liabilitas_jangka_panjang'] as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }}</td>
                    <td>{{ $row['akun']->nama_akun }}</td>
                    <td class="text-right">
                        {{ number_format($row['saldo'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">Tidak ada liabilitas jangka panjang.</td>
                </tr>
            @endforelse
            <tr class="summary-row">
                <td colspan="2">Jumlah Liabilitas Jangka Panjang</td>
                <td class="text-right">
                    {{ number_format($totalLJPanjang, 0, ',', '.') }}
                </td>
            </tr>

            {{-- TOTAL LIABILITAS --}}
            <tr class="summary-row">
                <td colspan="2">TOTAL LIABILITAS</td>
                <td class="text-right">
                    {{ number_format($totalLiabilitas, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ASET NETO --}}
    <h4 class="mt-3">C. ASET NETO</h4>
    <table>
        <thead>
            <tr>
                <th style="width: 15%">Kode</th>
                <th>Nama Akun</th>
                <th style="width: 25%" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Tidak Terikat --}}
            <tr>
                <td colspan="3" class="section-header">Aset Neto Tidak Terikat</td>
            </tr>
            @php $totalANTT = $total['aset_neto_tidak_terikat'] ?? 0; @endphp
            @forelse($kelompok['aset_neto_tidak_terikat'] as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }}</td>
                    <td>{{ $row['akun']->nama_akun }}</td>
                    <td class="text-right">
                        {{ number_format($row['saldo'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">Tidak ada aset neto tidak terikat.</td>
                </tr>
            @endforelse
            <tr class="summary-row">
                <td colspan="2">Jumlah Aset Neto Tidak Terikat</td>
                <td class="text-right">
                    {{ number_format($totalANTT, 0, ',', '.') }}
                </td>
            </tr>

            {{-- Terikat Temporer --}}
            <tr>
                <td colspan="3" class="section-header">Aset Neto Terikat Temporer</td>
            </tr>
            @php $totalANTemp = $total['aset_neto_terikat_temporer'] ?? 0; @endphp
            @forelse($kelompok['aset_neto_terikat_temporer'] as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }}</td>
                    <td>{{ $row['akun']->nama_akun }}</td>
                    <td class="text-right">
                        {{ number_format($row['saldo'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">Tidak ada aset neto terikat temporer.</td>
                </tr>
            @endforelse
            <tr class="summary-row">
                <td colspan="2">Jumlah Aset Neto Terikat Temporer</td>
                <td class="text-right">
                    {{ number_format($totalANTemp, 0, ',', '.') }}
                </td>
            </tr>

            {{-- Terikat Permanen --}}
            <tr>
                <td colspan="3" class="section-header">Aset Neto Terikat Permanen</td>
            </tr>
            @php $totalANPerm = $total['aset_neto_terikat_permanen'] ?? 0; @endphp
            @forelse($kelompok['aset_neto_terikat_permanen'] as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }}</td>
                    <td>{{ $row['akun']->nama_akun }}</td>
                    <td class="text-right">
                        {{ number_format($row['saldo'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">Tidak ada aset neto terikat permanen.</td>
                </tr>
            @endforelse
            <tr class="summary-row">
                <td colspan="2">Jumlah Aset Neto Terikat Permanen</td>
                <td class="text-right">
                    {{ number_format($totalANPerm, 0, ',', '.') }}
                </td>
            </tr>

            {{-- TOTAL ASET NETO --}}
            <tr class="summary-row">
                <td colspan="2">TOTAL ASET NETO</td>
                <td class="text-right">
                    {{ number_format($totalAsetNeto, 0, ',', '.') }}
                </td>
            </tr>

            {{-- CHECK BALANCE --}}
            <tr>
                <td colspan="3" class="muted">
                    Selisih Aset - (Liabilitas + Aset Neto):
                    <strong>{{ number_format($selisih, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>

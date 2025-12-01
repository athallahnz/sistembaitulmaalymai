<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Aktivitas</title>
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
        h3,
        h4,
        p {
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
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 4px 6px;
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
    <div class="text-center mb-3">
        <h2>Yayasan Masjid Al Iman</h2>
        <h3 class="mt-1">Laporan Aktivitas</h3>
        <p class="mt-1">
            Periode
            {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}
            s/d
            {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
        </p>
    </div>

    {{-- ===================== PENDAPATAN ===================== --}}
    <h4 class="mt-3 mb-1">Pendapatan</h4>

    <table>
        <thead>
            <tr>
                <th style="width: 30%">Kelompok</th>
                <th style="width: 40%">Akun</th>
                <th style="width: 30%" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $labelPembatasan = [
                    'tidak_terikat' => 'Tidak Terikat',
                    'terikat_temporer' => 'Terikat Temporer',
                    'terikat_permanen' => 'Terikat Permanen',
                ];
            @endphp

            @foreach (['tidak_terikat', 'terikat_temporer', 'terikat_permanen'] as $key)
                @php
                    $rows = $pendapatan[$key] ?? [];
                    $totalKelompok = $totalPendapatan[$key] ?? 0;
                @endphp

                @if (!empty($rows))
                    <tr class="section-header">
                        <td colspan="3">{{ $labelPembatasan[$key] }}</td>
                    </tr>

                    @foreach ($rows as $row)
                        <tr>
                            <td></td>
                            <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                            <td class="text-right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    <tr class="summary-row">
                        <td colspan="2" class="text-right">
                            Subtotal {{ $labelPembatasan[$key] }}
                        </td>
                        <td class="text-right">
                            {{ number_format($totalKelompok, 0, ',', '.') }}
                        </td>
                    </tr>
                @endif
            @endforeach

            @php
                $totalPendapatanAll =
                    ($totalPendapatan['tidak_terikat'] ?? 0) +
                    ($totalPendapatan['terikat_temporer'] ?? 0) +
                    ($totalPendapatan['terikat_permanen'] ?? 0);
            @endphp

            <tr class="summary-row">
                <td colspan="2" class="text-right">
                    <strong>Total Pendapatan</strong>
                </td>
                <td class="text-right">
                    <strong>{{ number_format($totalPendapatanAll, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ===================== BEBAN ===================== --}}
    <h4 class="mt-4 mb-1">Beban</h4>

    <table>
        <thead>
            <tr>
                <th style="width: 40%">Akun</th>
                <th style="width: 30%">Kelompok</th>
                <th style="width: 30%" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Beban Tidak Terikat --}}
            @php $bbTT = $bebanTidakTerikat ?? []; @endphp
            @if (!empty($bbTT))
                <tr class="section-header">
                    <td colspan="3">Beban Tidak Terikat</td>
                </tr>
                @foreach ($bbTT as $row)
                    <tr>
                        <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                        <td>Tidak Terikat</td>
                        <td class="text-right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td class="text-right">Subtotal Beban Tidak Terikat</td>
                    <td></td>
                    <td class="text-right">
                        {{ number_format($totalBebanTidakTerikat ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            {{-- Beban Terikat Temporer --}}
            @php $bbTemp = $bebanTerikatTemporer ?? []; @endphp
            @if (!empty($bbTemp))
                <tr class="section-header">
                    <td colspan="3">Beban Terikat Temporer</td>
                </tr>
                @foreach ($bbTemp as $row)
                    <tr>
                        <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                        <td>Terikat Temporer</td>
                        <td class="text-right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td class="text-right">Subtotal Beban Terikat Temporer</td>
                    <td></td>
                    <td class="text-right">
                        {{ number_format($totalBebanTemporer ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            {{-- Beban Terikat Permanen --}}
            @php $bbPerm = $bebanTerikatPermanen ?? []; @endphp
            @if (!empty($bbPerm))
                <tr class="section-header">
                    <td colspan="3">Beban Terikat Permanen</td>
                </tr>
                @foreach ($bbPerm as $row)
                    <tr>
                        <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                        <td>Terikat Permanen</td>
                        <td class="text-right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td class="text-right">Subtotal Beban Terikat Permanen</td>
                    <td></td>
                    <td class="text-right">
                        {{ number_format($totalBebanPermanen ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

            @php
                $grandTotalBeban =
                    ($totalBebanTidakTerikat ?? 0) + ($totalBebanTemporer ?? 0) + ($totalBebanPermanen ?? 0);
            @endphp

            <tr class="summary-row">
                <td class="text-right"><strong>Total Seluruh Beban</strong></td>
                <td></td>
                <td class="text-right">
                    <strong>{{ number_format($grandTotalBeban, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ===================== PERUBAHAN ASET NETO ===================== --}}
    <h4 class="mt-4 mb-1">Ringkasan Perubahan Aset Neto</h4>

    <table>
        <tbody>
            <tr>
                <td>Perubahan Aset Neto Tidak Terikat</td>
                <td class="text-right">
                    {{ number_format($perubahanTidakTerikat ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Perubahan Aset Neto Terikat Temporer</td>
                <td class="text-right">
                    {{ number_format($perubahanTemporer ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Perubahan Aset Neto Terikat Permanen</td>
                <td class="text-right">
                    {{ number_format($perubahanPermanen ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr class="summary-row">
                <td><strong>Total Perubahan Aset Neto</strong></td>
                <td class="text-right">
                    <strong>{{ number_format($totalPerubahanAsetNeto ?? 0, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

</body>

</html>

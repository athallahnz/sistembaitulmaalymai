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

        .text-end {
            text-align: right;
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

        .mb-1 {
            margin-bottom: 4px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-3 {
            margin-bottom: 12px;
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
            padding: 6px;
        }

        .fw-bold {
            font-weight: bold;
        }

        .table-light {
            background: #f7f7f7;
        }

        .table-success {
            background: #e9f7ef;
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

    {{-- PENDAPATAN --}}
    <h4 class="mt-2">Pendapatan</h4>

    <table>
        <thead class="table-light">
            <tr>
                <th>Kelompok</th>
                <th>Nama Akun</th>
                <th class="text-end">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Tidak Terikat --}}
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
                    <tr class="table-light">
                        <td colspan="3" class="fw-bold">
                            {{ $labelPembatasan[$key] }}
                        </td>
                    </tr>

                    @foreach ($rows as $row)
                        <tr>
                            <td></td>
                            <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                            <td class="text-end">
                                Rp{{ number_format($row['saldo'], 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">Subtotal {{ $labelPembatasan[$key] }}</td>
                        <td class="text-end">
                            Rp{{ number_format($totalKelompok, 2, ',', '.') }}
                        </td>
                    </tr>
                @endif
            @endforeach

            {{-- Total Pendapatan --}}
            <tr class="fw-bold table-success">
                <td colspan="2" class="text-end">Total Pendapatan</td>
                <td class="text-end">
                    Rp{{ number_format(
                        ($totalPendapatan['tidak_terikat'] ?? 0) +
                            ($totalPendapatan['terikat_temporer'] ?? 0) +
                            ($totalPendapatan['terikat_permanen'] ?? 0),
                        2,
                        ',',
                        '.',
                    ) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- BEBAN --}}
    <h4 class="mt-3">Beban</h4>

    <table>
        <thead class="table-light">
            <tr>
                <th>Nama Akun</th>
                <th class="text-end">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bebanList as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                    <td class="text-end">
                        Rp{{ number_format($row['saldo'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center">Tidak ada beban pada periode ini.</td>
                </tr>
            @endforelse

            <tr class="fw-bold table-light">
                <td class="text-end">Total Beban</td>
                <td class="text-end">
                    Rp{{ number_format($totalBeban, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- RINGKASAN PERUBAHAN ASET NETO --}}
    <h4 class="mt-3">Ringkasan Perubahan Aset Neto</h4>

    <table>
        <tbody>
            <tr>
                <td>Perubahan Aset Neto Tidak Terikat</td>
                <td class="text-end">
                    Rp{{ number_format($perubahanTidakTerikat, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Perubahan Aset Neto Terikat Temporer</td>
                <td class="text-end">
                    Rp{{ number_format($perubahanTemporer, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Perubahan Aset Neto Terikat Permanen</td>
                <td class="text-end">
                    Rp{{ number_format($perubahanPermanen, 2, ',', '.') }}
                </td>
            </tr>
            <tr class="fw-bold table-success">
                <td>Total Perubahan Aset Neto</td>
                <td class="text-end">
                    Rp{{ number_format($totalPerubahanAsetNeto, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>

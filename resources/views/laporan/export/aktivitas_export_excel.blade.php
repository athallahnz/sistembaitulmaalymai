<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Aktivitas (Excel)</title>
</head>

<body>

    {{-- HEADER --}}
    <h2>Yayasan Masjid Al Iman</h2>
    <h3>Laporan Aktivitas</h3>
    <p>
        Periode:
        {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
        s/d
        {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
    </p>

    {{-- =========================== PENDAPATAN =========================== --}}
    <h3>Pendapatan</h3>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr style="background:#f2f2f2;">
                <th>Kelompok</th>
                <th>Nama Akun</th>
                <th>Jumlah (Rp)</th>
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
                    <tr style="background:#e8e8e8;">
                        <td colspan="3"><strong>{{ $labelPembatasan[$key] }}</strong></td>
                    </tr>

                    @foreach ($rows as $row)
                        <tr>
                            <td></td>
                            <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                            <td>{{ $row['saldo'] }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td colspan="2" align="right">
                            <strong>Subtotal {{ $labelPembatasan[$key] }}</strong>
                        </td>
                        <td><strong>{{ $totalKelompok }}</strong></td>
                    </tr>
                @endif
            @endforeach

            @php
                $totalPendapatanAll =
                    ($totalPendapatan['tidak_terikat'] ?? 0) +
                    ($totalPendapatan['terikat_temporer'] ?? 0) +
                    ($totalPendapatan['terikat_permanen'] ?? 0);
            @endphp

            <tr style="background:#d9ead3;font-weight:bold;">
                <td colspan="2" align="right">Total Pendapatan</td>
                <td>{{ $totalPendapatanAll }}</td>
            </tr>
        </tbody>
    </table>

    {{-- =========================== BEBAN =========================== --}}
    <h3 style="margin-top:20px;">Beban</h3>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr style="background:#f2f2f2;">
                <th>Nama Akun</th>
                <th>Kelompok</th>
                <th>Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            {{-- Beban Tidak Terikat --}}
            @php $bbTT = $bebanTidakTerikat ?? []; @endphp
            @if (!empty($bbTT))
                <tr style="background:#e8e8e8;">
                    <td colspan="3"><strong>Beban Tidak Terikat</strong></td>
                </tr>
                @foreach ($bbTT as $row)
                    <tr>
                        <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                        <td>Tidak Terikat</td>
                        <td>{{ $row['saldo'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td align="right"><strong>Subtotal Beban Tidak Terikat</strong></td>
                    <td></td>
                    <td><strong>{{ $totalBebanTidakTerikat ?? 0 }}</strong></td>
                </tr>
            @endif

            {{-- Beban Terikat Temporer --}}
            @php $bbTemp = $bebanTerikatTemporer ?? []; @endphp
            @if (!empty($bbTemp))
                <tr style="background:#e8e8e8;">
                    <td colspan="3"><strong>Beban Terikat Temporer</strong></td>
                </tr>
                @foreach ($bbTemp as $row)
                    <tr>
                        <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                        <td>Terikat Temporer</td>
                        <td>{{ $row['saldo'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td align="right"><strong>Subtotal Beban Terikat Temporer</strong></td>
                    <td></td>
                    <td><strong>{{ $totalBebanTemporer ?? 0 }}</strong></td>
                </tr>
            @endif

            {{-- Beban Terikat Permanen --}}
            @php $bbPerm = $bebanTerikatPermanen ?? []; @endphp
            @if (!empty($bbPerm))
                <tr style="background:#e8e8e8;">
                    <td colspan="3"><strong>Beban Terikat Permanen</strong></td>
                </tr>
                @foreach ($bbPerm as $row)
                    <tr>
                        <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                        <td>Terikat Permanen</td>
                        <td>{{ $row['saldo'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td align="right"><strong>Subtotal Beban Terikat Permanen</strong></td>
                    <td></td>
                    <td><strong>{{ $totalBebanPermanen ?? 0 }}</strong></td>
                </tr>
            @endif

            @php
                $grandTotalBeban =
                    ($totalBebanTidakTerikat ?? 0) + ($totalBebanTemporer ?? 0) + ($totalBebanPermanen ?? 0);
            @endphp

            <tr style="background:#f2f2f2;font-weight:bold;">
                <td align="right">Total Seluruh Beban</td>
                <td></td>
                <td>{{ $grandTotalBeban }}</td>
            </tr>
        </tbody>
    </table>

    {{-- =========================== PERUBAHAN ASET NETO =========================== --}}
    <h3 style="margin-top:20px;">Ringkasan Perubahan Aset Neto</h3>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <tbody>
            <tr>
                <td>Perubahan Aset Neto Tidak Terikat</td>
                <td>{{ $perubahanTidakTerikat }}</td>
            </tr>
            <tr>
                <td>Perubahan Aset Neto Terikat Temporer</td>
                <td>{{ $perubahanTemporer }}</td>
            </tr>
            <tr>
                <td>Perubahan Aset Neto Terikat Permanen</td>
                <td>{{ $perubahanPermanen }}</td>
            </tr>
            <tr style="background:#d9ead3;font-weight:bold;">
                <td>Total Perubahan Aset Neto</td>
                <td>{{ $totalPerubahanAsetNeto }}</td>
            </tr>
        </tbody>
    </table>

</body>

</html>

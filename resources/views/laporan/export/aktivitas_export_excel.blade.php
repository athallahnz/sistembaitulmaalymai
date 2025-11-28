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

    {{-- ===========================
        PENDAPATAN
    ============================ --}}
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
                        <td colspan="2" align="right"><strong>Subtotal {{ $labelPembatasan[$key] }}</strong></td>
                        <td><strong>{{ $totalKelompok }}</strong></td>
                    </tr>
                @endif
            @endforeach

            {{-- Total Pendapatan --}}
            <tr style="background:#d9ead3;font-weight:bold;">
                <td colspan="2" align="right">Total Pendapatan</td>
                <td>
                    <strong>
                        {{ ($totalPendapatan['tidak_terikat'] ?? 0) +
                            ($totalPendapatan['terikat_temporer'] ?? 0) +
                            ($totalPendapatan['terikat_permanen'] ?? 0) }}
                    </strong>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ===========================
        BEBAN
    ============================ --}}
    <h3 style="margin-top:20px;">Beban</h3>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr style="background:#f2f2f2;">
                <th>Nama Akun</th>
                <th>Jumlah (Rp)</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($bebanList as $row)
                <tr>
                    <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                    <td>{{ $row['saldo'] }}</td>
                </tr>
            @endforeach

            <tr style="background:#f2f2f2;font-weight:bold;">
                <td align="right">Total Beban</td>
                <td>{{ $totalBeban }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ===========================
        PERUBAHAN ASET NETO
    ============================ --}}
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

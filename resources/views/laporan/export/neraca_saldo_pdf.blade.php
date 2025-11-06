<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Neraca Saldo</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .table-light {
            background: #f7f7f7;
        }
    </style>
</head>

<body>
    <h3 style="margin:0 0 8px 0;">
        Neraca Saldo {{ $role === 'Bendahara' ? 'Yayasan' : 'Bidang ' . $bidangId }}
    </h3>
    <p style="margin:0 0 12px 0;">Posisi s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>

    {{-- PANGGIL PARTIAL SEKALI SAJA --}}
    @include('laporan.partials.neraca_table', [
        'saldoKas' => $saldoKas,
        'saldoBank' => $saldoBank,
        'piutang' => $piutang,
        'tanahBangunan' => $tanahBangunan,
        'inventaris' => $inventaris,
        'totalAset' => $totalAset,
        'hutang' => $hutang,
        'pendapatanBelumDiterima' => $pendapatanBelumDiterima,
        'totalLiabilitas' => $totalLiabilitas,
        'asetBersih' => $asetBersih,
    ])

    @if (!empty($saldoKasTotal) || !empty($saldoBankTotal))
        <h4 style="margin-top:16px;">Ringkasan (Bendahara – Semua Bidang)</h4>
        <table>
            <tr>
                <th>Total Kas</th>
                <td class="text-end">Rp{{ number_format($saldoKasTotal ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Bank</th>
                <td class="text-end">Rp{{ number_format($saldoBankTotal ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="table-light">
                <th>Total Keuangan</th>
                <td class="text-end">Rp{{ number_format($totalKeuanganSemuaBidang ?? 0, 2, ',', '.') }}</td>
            </tr>
        </table>
    @endif
</body>

</html>

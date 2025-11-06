<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Aktivitas</title>
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

        .table-success {
            background: #e9f7ef;
        }

        .table-danger {
            background: #fdecea;
        }

        h3,
        h4,
        p {
            margin: 0 0 8px 0;
        }
    </style>
</head>

<body>
    <h3>Laporan Aktivitas</h3>
    <p>Periode {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
        s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>

    {{-- PANGGIL PARTIAL SEKALI SAJA --}}
    @include('laporan.partials.aktivitas_table', [
        'aktivitas' => $aktivitas,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'totalPenerimaan' => $totalPenerimaan,
        'totalPengeluaran' => $totalPengeluaran,
        'surplusDefisit' => $surplusDefisit,
    ])
</body>

</html>

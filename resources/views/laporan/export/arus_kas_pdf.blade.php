<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Arus Kas</title>
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

        .text-right {
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
        <h3 class="mt-1">Laporan Arus Kas</h3>
        <p class="mt-1">
            Periode
            {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}
            s/d
            {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
        </p>
    </div>

    {{-- TABEL ARUS KAS PER KATEGORI --}}
    <table>
        <thead>
            <tr>
                <th style="width: 40%">Kategori</th>
                <th class="text-right" style="width: 30%">Arus Kas Masuk (Rp)</th>
                <th class="text-right" style="width: 30%">Arus Kas Keluar (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-header">
                <td colspan="3">Arus Kas dari Aktivitas Operasional</td>
            </tr>
            <tr>
                <td>Operasional</td>
                <td class="text-right">
                    {{ number_format($kasOperasionalMasuk, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    {{ number_format($kasOperasionalKeluar, 0, ',', '.') }}
                </td>
            </tr>

            <tr class="section-header">
                <td colspan="3">Arus Kas dari Aktivitas Investasi</td>
            </tr>
            <tr>
                <td>Investasi</td>
                <td class="text-right">
                    {{ number_format($kasInvestasiMasuk, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    {{ number_format($kasInvestasiKeluar, 0, ',', '.') }}
                </td>
            </tr>

            <tr class="section-header">
                <td colspan="3">Arus Kas dari Aktivitas Pendanaan</td>
            </tr>
            <tr>
                <td>Pendanaan</td>
                <td class="text-right">
                    {{ number_format($kasPendanaanMasuk, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    {{ number_format($kasPendanaanKeluar, 0, ',', '.') }}
                </td>
            </tr>

            {{-- TOTAL --}}
            <tr class="summary-row">
                <td>Total Arus Kas Masuk</td>
                <td class="text-right">
                    {{ number_format($totalKasMasuk, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    {{-- kosong / strip karena ini total masuk --}}
                    -
                </td>
            </tr>
            <tr class="summary-row">
                <td>Total Arus Kas Keluar</td>
                <td class="text-right">
                    -
                </td>
                <td class="text-right">
                    {{ number_format($totalKasKeluar, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>

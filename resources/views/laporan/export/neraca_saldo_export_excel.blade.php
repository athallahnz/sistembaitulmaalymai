<table>
    <thead>
        <tr>
            <th style="width:90px">ID</th>
            <th>Akun</th>
            <th style="text-align:right;width:220px">Amount</th>
        </tr>
    </thead>
    <tbody>
        {{-- ASET --}}
        <tr>
            <td colspan="3"><strong>Aset</strong></td>
        </tr>
        <tr>
            <td colspan="3">Aset Lancar</td>
        </tr>
        <tr>
            <td>101</td>
            <td>Kas</td>
            <td style="text-align:right">Rp{{ number_format($saldoKas, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>102</td>
            <td>Bank</td>
            <td style="text-align:right">Rp{{ number_format($saldoBank, 2, ',', '.') }}</td>
        </tr>

        <tr>
            <td colspan="3">Aset Tidak Lancar</td>
        </tr>
        <tr>
            <td>103</td>
            <td>Piutang</td>
            <td style="text-align:right">Rp{{ number_format($piutang, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>104</td>
            <td>Tanah Bangunan</td>
            <td style="text-align:right">Rp{{ number_format($tanahBangunan, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>105</td>
            <td>Inventaris</td>
            <td style="text-align:right">Rp{{ number_format($inventaris, 2, ',', '.') }}</td>
        </tr>

        <tr>
            <td colspan="2"><strong>Total Aset</strong></td>
            <td style="text-align:right"><strong>Rp{{ number_format($totalAset, 2, ',', '.') }}</strong></td>
        </tr>

        {{-- LIABILITAS & ASET BERSIH --}}
        <tr>
            <td colspan="3"><strong>Liabilitas &amp; Aset Bersih</strong></td>
        </tr>

        <tr>
            <td colspan="3">Kewajiban</td>
        </tr>
        <tr>
            <td>—</td>
            <td>Hutang</td>
            <td style="text-align:right">Rp{{ number_format($hutang, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>—</td>
            <td>Pendapatan Belum Diterima</td>
            <td style="text-align:right">Rp{{ number_format($pendapatanBelumDiterima, 2, ',', '.') }}</td>
        </tr>

        <tr>
            <td colspan="2"><strong>Total Liabilitas</strong></td>
            <td style="text-align:right"><strong>Rp{{ number_format($totalLiabilitas, 2, ',', '.') }}</strong></td>
        </tr>

        <tr>
            <td colspan="2"><strong>Aset Bersih</strong></td>
            <td style="text-align:right"><strong>Rp{{ number_format($asetBersih, 2, ',', '.') }}</strong></td>
        </tr>
    </tbody>
</table>

@if (!empty($saldoKasTotal) || !empty($saldoBankTotal))
    <table>
        <tr>
            <td colspan="2"><strong>Ringkasan (Bendahara – Semua Bidang)</strong></td>
        </tr>
        <tr>
            <td>Total Kas</td>
            <td>Rp{{ number_format($saldoKasTotal ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Bank</td>
            <td>Rp{{ number_format($saldoBankTotal ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Total Keuangan</strong></td>
            <td><strong>Rp{{ number_format($totalKeuanganSemuaBidang ?? 0, 2, ',', '.') }}</strong></td>
        </tr>
    </table>
@endif

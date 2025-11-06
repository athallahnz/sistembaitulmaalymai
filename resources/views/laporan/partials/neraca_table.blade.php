<table>
    <thead class="table-light">
        <tr>
            <th style="width:90px">ID</th>
            <th>Akun</th>
            <th class="text-end" style="width:220px">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr class="fw-bold">
            <td colspan="3">Aset</td>
        </tr>
        <tr>
            <td colspan="3" class="table-light">Aset Lancar</td>
        </tr>
        <tr>
            <td>101</td>
            <td>Kas</td>
            <td class="text-end">Rp{{ number_format($saldoKas, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>102</td>
            <td>Bank</td>
            <td class="text-end">Rp{{ number_format($saldoBank, 2, ',', '.') }}</td>
        </tr>

        <tr>
            <td colspan="3" class="table-light">Aset Tidak Lancar</td>
        </tr>
        <tr>
            <td>103</td>
            <td>Piutang</td>
            <td class="text-end">Rp{{ number_format($piutang, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>104</td>
            <td>Tanah Bangunan</td>
            <td class="text-end">Rp{{ number_format($tanahBangunan, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>105</td>
            <td>Inventaris</td>
            <td class="text-end">Rp{{ number_format($inventaris, 2, ',', '.') }}</td>
        </tr>

        <tr class="fw-bold table-light">
            <td colspan="2" class="text-center">Total Aset</td>
            <td class="text-end">Rp{{ number_format($totalAset, 2, ',', '.') }}</td>
        </tr>

        <tr class="fw-bold">
            <td colspan="3">Liabilitas &amp; Aset Bersih</td>
        </tr>
        <tr>
            <td colspan="3" class="table-light">Kewajiban</td>
        </tr>
        <tr>
            <td>—</td>
            <td>Hutang</td>
            <td class="text-end">Rp{{ number_format($hutang, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>—</td>
            <td>Pendapatan Belum Diterima</td>
            <td class="text-end">Rp{{ number_format($pendapatanBelumDiterima, 2, ',', '.') }}</td>
        </tr>

        <tr class="fw-bold table-light">
            <td colspan="2" class="text-center">Total Liabilitas</td>
            <td class="text-end">Rp{{ number_format($totalLiabilitas, 2, ',', '.') }}</td>
        </tr>

        <tr class="fw-bold">
            <td colspan="2" class="text-center">Aset Bersih</td>
            <td class="text-end">Rp{{ number_format($asetBersih, 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

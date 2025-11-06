<table>
    <tr>
        <td><strong>Laporan Aktivitas</strong></td>
    </tr>
    <tr>
        <td>Periode {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} –
            {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Deskripsi</th>
            <th style="text-align:right">Penerimaan</th>
            <th style="text-align:right">Pengeluaran</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($aktivitas as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->tanggal_transaksi)->format('d/m/Y') }}</td>
                <td>{{ $row->deskripsi }}</td>
                <td style="text-align:right">
                    {{ $row->type === 'penerimaan' ? 'Rp' . number_format($row->amount, 2, ',', '.') : 'Rp0,00' }}</td>
                <td style="text-align:right">
                    {{ $row->type === 'pengeluaran' ? 'Rp' . number_format($row->amount, 2, ',', '.') : 'Rp0,00' }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2" style="text-align:right"><strong>Total Penerimaan</strong></td>
            <td style="text-align:right"><strong>Rp{{ number_format($totalPenerimaan, 2, ',', '.') }}</strong></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:right"><strong>Total Pengeluaran</strong></td>
            <td style="text-align:right"><strong>Rp{{ number_format($totalPengeluaran, 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:right"><strong>Surplus/(Defisit)</strong></td>
            <td style="text-align:right"><strong>Rp{{ number_format($surplusDefisit, 2, ',', '.') }}</strong></td>
        </tr>
    </tbody>
</table>

<table>
    <thead class="table-light">
        <tr>
            <th style="width:130px">Tanggal</th>
            <th>Deskripsi</th>
            <th class="text-end" style="width:180px">Penerimaan</th>
            <th class="text-end" style="width:180px">Pengeluaran</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($aktivitas as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->tanggal_transaksi)->format('d/m/Y') }}</td>
                <td>{{ $row->deskripsi }}</td>
                <td class="text-end">
                    {{ $row->type === 'penerimaan' ? 'Rp' . number_format($row->amount, 2, ',', '.') : 'Rp0,00' }}
                </td>
                <td class="text-end">
                    {{ $row->type === 'pengeluaran' ? 'Rp' . number_format($row->amount, 2, ',', '.') : 'Rp0,00' }}
                </td>
            </tr>
        @endforeach

        <tr class="fw-bold table-light">
            <td colspan="2" class="text-end">Total Penerimaan</td>
            <td class="text-end">Rp{{ number_format($totalPenerimaan, 2, ',', '.') }}</td>
            <td></td>
        </tr>
        <tr class="fw-bold table-light">
            <td colspan="3" class="text-end">Total Pengeluaran</td>
            <td class="text-end">Rp{{ number_format($totalPengeluaran, 2, ',', '.') }}</td>
        </tr>
        <tr class="fw-bold {{ $surplusDefisit >= 0 ? 'table-success' : 'table-danger' }}">
            <td colspan="3" class="text-end">Surplus/(Defisit)</td>
            <td class="text-end">Rp{{ number_format($surplusDefisit, 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<table>
    <tr>
        <td colspan="3" align="center"><strong>Yayasan Masjid Al Iman</strong></td>
    </tr>
    <tr>
        <td colspan="3" align="center"><strong>Laporan Arus Kas</strong></td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            Periode
            {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}
            s/d
            {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
        </td>
    </tr>
    <tr>
        <td colspan="3"></td>
    </tr>
</table>

<table border="1" cellspacing="0" cellpadding="4">
    <thead>
        <tr>
            <th style="width: 40%">Kategori</th>
            <th style="width: 30%">Arus Kas Masuk (Rp)</th>
            <th style="width: 30%">Arus Kas Keluar (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Operasional</strong></td>
            <td align="right">{{ $kasOperasionalMasuk }}</td>
            <td align="right">{{ $kasOperasionalKeluar }}</td>
        </tr>
        <tr>
            <td><strong>Investasi</strong></td>
            <td align="right">{{ $kasInvestasiMasuk }}</td>
            <td align="right">{{ $kasInvestasiKeluar }}</td>
        </tr>
        <tr>
            <td><strong>Pendanaan</strong></td>
            <td align="right">{{ $kasPendanaanMasuk }}</td>
            <td align="right">{{ $kasPendanaanKeluar }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td><strong>Total</strong></td>
            <td align="right"><strong>{{ $totalKasMasuk }}</strong></td>
            <td align="right"><strong>{{ $totalKasKeluar }}</strong></td>
        </tr>
    </tfoot>
</table>

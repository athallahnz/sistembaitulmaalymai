<table>
    <tr>
        <td colspan="3" align="center"><strong>Yayasan Masjid Al Iman</strong></td>
    </tr>
    <tr>
        <td colspan="3" align="center"><strong>Laporan Posisi Keuangan</strong></td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            Per {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
        </td>
    </tr>
    <tr>
        <td colspan="3"></td>
    </tr>
</table>

{{-- ASET --}}
<table border="1" cellspacing="0" cellpadding="4">
    <tr>
        <th colspan="3" align="left">A. ASET</th>
    </tr>
    <tr>
        <th style="width: 15%">Kode</th>
        <th>Nama Akun</th>
        <th style="width: 25%">Jumlah (Rp)</th>
    </tr>

    {{-- Aset Lancar --}}
    <tr>
        <td colspan="3"><strong>Aset Lancar</strong></td>
    </tr>
    @php $totalAsetLancar = $total['aset_lancar'] ?? 0; @endphp
    @forelse($kelompok['aset_lancar'] as $row)
        <tr>
            <td>{{ $row['akun']->kode_akun }}</td>
            <td>{{ $row['akun']->nama_akun }}</td>
            <td align="right">{{ $row['saldo'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3">Tidak ada aset lancar.</td>
        </tr>
    @endforelse
    <tr>
        <td colspan="2"><strong>Jumlah Aset Lancar</strong></td>
        <td align="right"><strong>{{ $totalAsetLancar }}</strong></td>
    </tr>

    {{-- Aset Tidak Lancar --}}
    <tr>
        <td colspan="3"><strong>Aset Tidak Lancar</strong></td>
    </tr>
    @php $totalAsetTidakLancar = $total['aset_tidak_lancar'] ?? 0; @endphp
    @forelse($kelompok['aset_tidak_lancar'] as $row)
        <tr>
            <td>{{ $row['akun']->kode_akun }}</td>
            <td>{{ $row['akun']->nama_akun }}</td>
            <td align="right">{{ $row['saldo'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3">Tidak ada aset tidak lancar.</td>
        </tr>
    @endforelse
    <tr>
        <td colspan="2"><strong>Jumlah Aset Tidak Lancar</strong></td>
        <td align="right"><strong>{{ $totalAsetTidakLancar }}</strong></td>
    </tr>

    <tr>
        <td colspan="2"><strong>TOTAL ASET</strong></td>
        <td align="right"><strong>{{ $totalAset }}</strong></td>
    </tr>
</table>

<br>

{{-- LIABILITAS --}}
<table border="1" cellspacing="0" cellpadding="4">
    <tr>
        <th colspan="3" align="left">B. LIABILITAS</th>
    </tr>
    <tr>
        <th style="width: 15%">Kode</th>
        <th>Nama Akun</th>
        <th style="width: 25%">Jumlah (Rp)</th>
    </tr>

    {{-- Liabilitas Jangka Pendek --}}
    <tr>
        <td colspan="3"><strong>Liabilitas Jangka Pendek</strong></td>
    </tr>
    @php $totalLJP = $total['liabilitas_jangka_pendek'] ?? 0; @endphp
    @forelse($kelompok['liabilitas_jangka_pendek'] as $row)
        <tr>
            <td>{{ $row['akun']->kode_akun }}</td>
            <td>{{ $row['akun']->nama_akun }}</td>
            <td align="right">{{ $row['saldo'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3">Tidak ada liabilitas jangka pendek.</td>
        </tr>
    @endforelse
    <tr>
        <td colspan="2"><strong>Jumlah Liabilitas Jangka Pendek</strong></td>
        <td align="right"><strong>{{ $totalLJP }}</strong></td>
    </tr>

    {{-- Liabilitas Jangka Panjang --}}
    <tr>
        <td colspan="3"><strong>Liabilitas Jangka Panjang</strong></td>
    </tr>
    @php $totalLJPanjang = $total['liabilitas_jangka_panjang'] ?? 0; @endphp
    @forelse($kelompok['liabilitas_jangka_panjang'] as $row)
        <tr>
            <td>{{ $row['akun']->kode_akun }}</td>
            <td>{{ $row['akun']->nama_akun }}</td>
            <td align="right">{{ $row['saldo'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3">Tidak ada liabilitas jangka panjang.</td>
        </tr>
    @endforelse
    <tr>
        <td colspan="2"><strong>Jumlah Liabilitas Jangka Panjang</strong></td>
        <td align="right"><strong>{{ $totalLJPanjang }}</strong></td>
    </tr>

    <tr>
        <td colspan="2"><strong>TOTAL LIABILITAS</strong></td>
        <td align="right"><strong>{{ $totalLiabilitas }}</strong></td>
    </tr>
</table>

<br>

{{-- ASET NETO --}}
<table border="1" cellspacing="0" cellpadding="4">
    <tr>
        <th colspan="3" align="left">C. ASET NETO</th>
    </tr>
    <tr>
        <th style="width: 15%">Kode</th>
        <th>Nama Akun</th>
        <th style="width: 25%">Jumlah (Rp)</th>
    </tr>

    {{-- Tidak Terikat --}}
    <tr>
        <td colspan="3"><strong>Aset Neto Tidak Terikat</strong></td>
    </tr>
    @php $totalANTT = $total['aset_neto_tidak_terikat'] ?? 0; @endphp
    @forelse($kelompok['aset_neto_tidak_terikat'] as $row)
        <tr>
            <td>{{ $row['akun']->kode_akun }}</td>
            <td>{{ $row['akun']->nama_akun }}</td>
            <td align="right">{{ $row['saldo'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3">Tidak ada aset neto tidak terikat.</td>
        </tr>
    @endforelse
    <tr>
        <td colspan="2"><strong>Jumlah Aset Neto Tidak Terikat</strong></td>
        <td align="right"><strong>{{ $totalANTT }}</strong></td>
    </tr>

    {{-- Terikat Temporer --}}
    <tr>
        <td colspan="3"><strong>Aset Neto Terikat Temporer</strong></td>
    </tr>
    @php $totalANTemp = $total['aset_neto_terikat_temporer'] ?? 0; @endphp
    @forelse($kelompok['aset_neto_terikat_temporer'] as $row)
        <tr>
            <td>{{ $row['akun']->kode_akun }}</td>
            <td>{{ $row['akun']->nama_akun }}</td>
            <td align="right">{{ $row['saldo'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3">Tidak ada aset neto terikat temporer.</td>
        </tr>
    @endforelse
    <tr>
        <td colspan="2"><strong>Jumlah Aset Neto Terikat Temporer</strong></td>
        <td align="right"><strong>{{ $totalANTemp }}</strong></td>
    </tr>

    {{-- Terikat Permanen --}}
    <tr>
        <td colspan="3"><strong>Aset Neto Terikat Permanen</strong></td>
    </tr>
    @php $totalANPerm = $total['aset_neto_terikat_permanen'] ?? 0; @endphp
    @forelse($kelompok['aset_neto_terikat_permanen'] as $row)
        <tr>
            <td>{{ $row['akun']->kode_akun }}</td>
            <td>{{ $row['akun']->nama_akun }}</td>
            <td align="right">{{ $row['saldo'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3">Tidak ada aset neto terikat permanen.</td>
        </tr>
    @endforelse
    <tr>
        <td colspan="2"><strong>Jumlah Aset Neto Terikat Permanen</strong></td>
        <td align="right"><strong>{{ $totalANPerm }}</strong></td>
    </tr>

    <tr>
        <td colspan="2"><strong>TOTAL ASET NETO</strong></td>
        <td align="right"><strong>{{ $totalAsetNeto }}</strong></td>
    </tr>

    <tr>
        <td colspan="3">
            Selisih Aset - (Liabilitas + Aset Neto): {{ $selisih }}
        </td>
    </tr>
</table>

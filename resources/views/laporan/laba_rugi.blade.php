@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Laporan Laba Rugi</h3>
    <table class="table">
        <tr><th>Pendapatan</th><td>Rp {{ number_format($pendapatan->sum('saldo'), 0, ',', '.') }}</td></tr>
        <tr><th>Beban</th><td>Rp {{ number_format($beban->sum('saldo'), 0, ',', '.') }}</td></tr>
        <tr><th>Keuntungan Bersih</th><td>Rp {{ number_format($pendapatan->sum('saldo') - $beban->sum('saldo'), 0, ',', '.') }}</td></tr>
    </table>
</div>
@endsection

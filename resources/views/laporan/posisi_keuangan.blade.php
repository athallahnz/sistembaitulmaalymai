@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Laporan Posisi Keuangan</h3>
    <table class="table">
        <tr><th>Aset</th><td>Rp {{ number_format($assets->sum('saldo'), 0, ',', '.') }}</td></tr>
        <tr><th>Kewajiban</th><td>Rp {{ number_format($liabilities->sum('saldo'), 0, ',', '.') }}</td></tr>
        <tr><th>Ekuitas</th><td>Rp {{ number_format($equity->sum('saldo'), 0, ',', '.') }}</td></tr>
    </table>
</div>
@endsection

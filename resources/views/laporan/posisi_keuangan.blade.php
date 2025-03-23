@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-2">
        @if (auth()->user()->hasRole('Bidang'))
            Laporan Posisi Keuangan <strong>Bidang {{ auth()->user()->bidang_name }}</strong>
        @elseif(auth()->user()->hasRole('Bendahara'))
            Laporan Posisi Keuangan <strong>Yayasan</strong>
        @endif
    </h1>
    <table class="table">
        <tr><th>Aset</th><td>Rp {{ number_format($assets->sum('saldo'), 0, ',', '.') }}</td></tr>
        <tr><th>Kewajiban</th><td>Rp {{ number_format($liabilities->sum('saldo'), 0, ',', '.') }}</td></tr>
        <tr><th>Aset Neto</th><td>Rp {{ number_format($equity->sum('saldo'), 0, ',', '.') }}</td></tr>
    </table>
</div>
@endsection

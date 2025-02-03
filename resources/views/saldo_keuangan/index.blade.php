@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Saldo Keuangan</h2>
    <table class="table mt-3">
        <thead>
            <tr>
                <th>Periode</th>
                <th>Akun</th>
                <th>Saldo Awal</th>
                <th>Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($saldo_keuangan as $saldo)
                <tr>
                    <td>{{ date(format: 'F Y', timestamp: strtotime($saldo->periode)) }}</td>
                    <td>{{ $saldo->akunKeuangan->nama_akun }}</td>
                    <td>{{ number_format($saldo->saldo_awal, 0, ',', '.') }}</td>
                    <td>{{ number_format($saldo->saldo_akhir, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Laporan Arus Kas <strong>Bidang {{ auth()->user()->bidang_name }}</strong></h1>

        <form method="GET" action="{{ route('laporan.arus-kas') }}" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <label>Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control mt-2 " value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control mt-2" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('laporan.arus-kas.pdf', request()->all()) }}" class="btn btn-danger ms-2">
                        <i class="bi bi-filetype-pdf"></i> Unduh PDF</a>
                </div>
            </div>
        </form>

        <table class="table table-bordered">
            <tr>
                <th>Penerimaan</th>
                <th>Pengeluaran</th>
            </tr>
            <tr>
                <td>Rp {{ number_format($penerimaan, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($pengeluaran, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
@endsection

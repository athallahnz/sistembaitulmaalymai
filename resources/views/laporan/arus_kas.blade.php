@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-2">
            @if (auth()->user()->hasRole('Bidang'))
                Laporan Arus Kas <strong>Bidang {{ auth()->user()->bidang->name }}</strong>
            @elseif(auth()->user()->hasRole('Bendahara'))
                Laporan Arus Kas <strong>Yayasan</strong>
            @endif
        </h1>
        <form method="GET" action="{{ route('laporan.arus-kas') }}" class="mb-4">
            <div class="row">
                <div class="col-md-4 mt-3">
                    <label>Dari Tanggal:</label>
                    <input type="date" name="start_date" class="form-control mt-2 "
                        value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4 mt-3">
                    <label>Sampai Tanggal:</label>
                    <input type="date" name="end_date" class="form-control mt-2"
                        value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4 mt-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('laporan.arus-kas.pdf', request()->all()) }}" class="btn btn-danger ms-2">
                        <i class="bi bi-filetype-pdf"></i> Unduh PDF</a>
                </div>
            </div>
        </form>
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Kategori</th>
                                <th>Arus Kas Masuk</th>
                                <th>Arus Kas Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Operasional</strong></td>
                                <td>Rp {{ number_format($kasOperasionalMasuk, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($kasOperasionalKeluar, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Investasi</strong></td>
                                <td>Rp {{ number_format($kasInvestasiMasuk, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($kasInvestasiKeluar, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Pendanaan</strong></td>
                                <td>Rp {{ number_format($kasPendanaanMasuk, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($kasPendanaanKeluar, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>Total</td>
                                <td>Rp {{ number_format($totalKasMasuk, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($totalKasKeluar, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

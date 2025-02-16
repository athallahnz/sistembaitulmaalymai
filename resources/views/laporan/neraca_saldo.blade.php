@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Neraca Saldo <strong>Bidang {{ auth()->user()->bidang_name }}</strong></h1>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('laporan.neraca-saldo') }}" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <label for="start_date">Dari Tanggal:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control mt-2"
                        value="{{ old('start_date', $startDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date">Sampai Tanggal:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control mt-2"
                        value="{{ old('end_date', $endDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Saldo Debit</th>
                            <th>Saldo Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($akunKeuangan as $akun)
                            <tr>
                                <td>{{ $akun->kode_akun }}</td>
                                <td>{{ $akun->nama_akun }}</td>
                                <td>
                                    @if ($akun->saldo_normal == 'debit')
                                        {{ number_format($akun->transaksis->sum('amount'), 2) }}
                                    @else
                                        --
                                    @endif
                                </td>
                                <td>
                                    @if ($akun->saldo_normal == 'kredit')
                                        {{ number_format($akun->transaksis->sum('amount'), 2) }}
                                    @else
                                        --
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data tersedia</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

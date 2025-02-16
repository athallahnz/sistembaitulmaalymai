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
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>101</td>
                            <td>Kas</td>
                            <td>Rp {{ number_format($lastSaldo101, 0, ',', '.') }}</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>102</td>
                            <td>Bank</td>
                            <td>Rp {{ number_format($lastSaldo102, 0, ',', '.') }}</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>103</td>
                            <td>Piutang</td>
                            <td>Rp {{ number_format($jumlahPiutang, 0, ',', '.') }}</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>104</td>
                            <td>Tanah Bangunan</td>
                            <td>Rp.0,-</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>105</td>
                            <td>Inventaris</td>
                            <td>Rp.0,-</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>201</td>
                            <td>Hutang</td>
                            <td>-</td>
                            <td>Rp {{ number_format($jumlahHutang, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>202</td>
                            <td>Donasi</td>
                            <td>-</td>
                            <td>Rp {{ number_format($jumlahDonasi, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>301</td>
                            <td>Beban Penyusutan</td>
                            <td>Rp.0,-</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>302</td>
                            <td>Beban Gaji dan Upah</td>
                            <td>Rp {{ number_format($jumlahBebanGaji, 0, ',', '.') }}</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>303</td>
                            <td>Biaya Operasional</td>
                            <td>Rp {{ number_format($jumlahBiayaOperasional, 0, ',', '.') }}</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>304</td>
                            <td>Biaya Kegiatan Siswa</td>
                            <td>Rp {{ number_format($jumlahBiayaKegiatan, 0, ',', '.') }}</td>
                            <td>-</td>
                        </tr>
                        <tr class="fw-bold">
                            <td></td>
                            <td>Total</td>
                            <td>Rp {{ number_format($lastSaldo101 + $lastSaldo102 + $jumlahPiutang + $jumlahBebanGaji + $jumlahBiayaOperasional + $jumlahBiayaKegiatan, 0, ',', '.') }}</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

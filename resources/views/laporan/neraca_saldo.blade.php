@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-2">
            @if (auth()->user()->hasRole('Bidang'))
                Neraca Saldo <strong>Bidang {{ auth()->user()->bidang->name }}</strong>
            @elseif(auth()->user()->hasRole('Bendahara'))
                Neraca Saldo <strong>Yayasan</strong>
            @endif
        </h1>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('laporan.neraca-saldo') }}" class="mb-4">
            <div class="row">
                <div class="col-md-4 mt-3">
                    <label for="start_date">Dari Tanggal:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control mt-2"
                        value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4 mt-3">
                    <label for="end_date">Sampai Tanggal:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control mt-2"
                        value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4 mt-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
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
                            @if (Auth::user()->hasRole('Bidang'))
                                <tr>
                                    <td>101</td>
                                    <td>Kas</td>
                                    <td>Rp{{ number_format($saldoKas, 2, ',', '.') }}</td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td>102</td>
                                    <td>Bank</td>
                                    <td>Rp{{ number_format($saldoBank, 2, ',', '.') }}</td>
                                    <td>-</td>
                                </tr>
                            @elseif(Auth::user()->hasRole('Bendahara'))
                                <tr>
                                    <td>101</td>
                                    <td>Kas</td>
                                    <td>Rp{{ number_format($totalseluruhKas, 2, ',', '.') }}</td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td>102</td>
                                    <td>Bank</td>
                                    <td>Rp{{ number_format($totalSeluruhBank, 2, ',', '.') }}</td>
                                    <td>-</td>
                                </tr>
                            @endif
                            <tr>
                                <td>103</td>
                                <td>Piutang</td>
                                <td>Rp{{ number_format($jumlahPiutang, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>104</td>
                                <td>Tanah Bangunan</td>
                                <td>Rp0,-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>105</td>
                                <td>Inventaris</td>
                                <td>Rp0,-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>201</td>
                                <td>Hutang</td>
                                <td>-</td>
                                <td>Rp{{ number_format($jumlahHutang, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>202</td>
                                <td>Donasi</td>
                                <td>-</td>
                                <td>Rp{{ number_format($jumlahDonasi, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>203</td>
                                <td>Pendapatan Belum Diterima</td>
                                <td>-</td>
                                <td>Rp{{ number_format($jumlahPendapatanBelumDiterima, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>301</td>
                                <td>Beban Penyusutan</td>
                                <td>Rp0,-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>302</td>
                                <td>Beban Gaji dan Upah</td>
                                <td>Rp{{ number_format($jumlahBebanGaji, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>303</td>
                                <td>Biaya Operasional</td>
                                <td>Rp{{ number_format($jumlahBiayaOperasional, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>304</td>
                                <td>Biaya Kegiatan Siswa</td>
                                <td>Rp{{ number_format($jumlahBiayaKegiatanSiswa, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>305</td>
                                <td>Biaya Pemeliharaan</td>
                                <td>Rp{{ number_format($jumlahBiayaPemeliharaan, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>306</td>
                                <td>Biaya Sosial</td>
                                <td>Rp{{ number_format($jumlahBiayaSosial, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>307</td>
                                <td>Biaya Perlengkapan Extra</td>
                                <td>Rp{{ number_format($jumlahBiayaPerlengkapanExtra, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>308</td>
                                <td>Biaya Seragam</td>
                                <td>Rp{{ number_format($jumlahBiayaSeragam, 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-center">Total</td>
                                @if (Auth::user()->hasRole('Bidang'))
                                    <td>Rp
                                        {{ number_format($saldoKas + $saldoBank + $jumlahPiutang + $jumlahBebanGaji + $jumlahBiayaOperasional + $jumlahBiayaKegiatanSiswa + $jumlahBiayaPemeliharaan + $jumlahBiayaSosial + $jumlahBiayaSeragam + $jumlahBiayaPerlengkapanExtra, 2, ',', '.') }}
                                    </td>
                                @elseif(Auth::user()->hasRole('Bendahara'))
                                    <td>Rp
                                        {{ number_format($totalseluruhKas + $totalSeluruhBank + $jumlahPiutang + $jumlahBebanGaji + $jumlahBiayaOperasional + $jumlahBiayaKegiatanSiswa + $jumlahBiayaPemeliharaan + $jumlahBiayaSosial + $jumlahBiayaSeragam + $jumlahBiayaPerlengkapanExtra, 2, ',', '.') }}
                                    </td>
                                @endif
                                <td>Rp
                                    {{ number_format($jumlahDonasi + $jumlahPendapatanBelumDiterima + $jumlahHutang, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

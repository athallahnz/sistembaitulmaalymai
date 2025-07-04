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
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (Auth::user()->hasRole('Bidang'))
                                <tr>
                                    <td colspan="3"><strong>Aset</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3">Aset Lancar</td>
                                </tr>
                                <tr>
                                    <td>101</td>
                                    <td>Kas</td>
                                    <td>Rp{{ number_format($saldoKas, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>102</td>
                                    <td>Bank</td>
                                    <td>Rp{{ number_format($saldoBank, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3">Aset Tidak Lancar</td>
                                </tr>
                                <tr>
                                    <td>103</td>
                                    <td>Piutang</td>
                                    <td>Rp{{ number_format($jumlahPiutang, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>104</td>
                                    <td>Tanah Bangunan</td>
                                    <td>Rp0,-</td>
                                </tr>
                                <tr>
                                    <td>105</td>
                                    <td>Inventaris</td>
                                    <td>Rp0,-</td>
                                </tr>
                            @elseif(Auth::user()->hasRole('Bendahara'))
                                <tr>
                                    <td>101</td>
                                    <td>Kas</td>
                                    <td>Rp{{ number_format($saldoKasTotal, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>102</td>
                                    <td>Bank</td>
                                    <td>Rp{{ number_format($saldoBankTotal, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>103</td>
                                    <td>Piutang</td>
                                    <td>Rp{{ number_format($jumlahPiutang, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>104</td>
                                    <td>Tanah Bangunan</td>
                                    <td>Rp0,-</td>
                                </tr>
                                <tr>
                                    <td>105</td>
                                    <td>Inventaris</td>
                                    <td>Rp0,-</td>
                                </tr>
                            @endif

                            <body class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="2" class="text-center">Total</td>
                                    @if (Auth::user()->hasRole('Bidang'))
                                        <td>Rp
                                            {{ number_format($saldoKas + $saldoBank + $jumlahPiutang, 2, ',', '.') }}
                                        </td>
                                    @elseif(Auth::user()->hasRole('Bendahara'))
                                        <td>Rp
                                            {{ number_format($saldoKasTotal + $saldoBankTotal + $jumlahPiutang, 2, ',', '.') }}
                                        </td>
                                    @endif
                                </tr>
                            </body>
                            <tr>
                                <td colspan="3"><strong>Liabilitas & Aset Bersih</strong></td>
                            </tr>
                            <tr>
                                <td colspan="3">Kewajiban</td>
                            </tr>
                            <tr>
                                <td>2021</td>
                                <td>SPP (Pendidikan)</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2022</td>
                                <td>Uang Gedung (Pendidikan)</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2023</td>
                                <td>Uang Kegiatan & Ekstra (Pendidikan)</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2024</td>
                                <td>Uang Pendaftaran (Pendidikan)</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2025</td>
                                <td>Uang Catering (Pendidikan)</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2026</td>
                                <td>Infaq</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2027</td>
                                <td>Wakaf</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2028</td>
                                <td>Sumbangan/Donasi</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2031</td>
                                <td>Pembangunan Masjid</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td>2032</td>
                                <td>Pembangunan Day Care</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td colspan="3">Aset Netto</td>
                            </tr>
                            <tr>
                                <td colspan="3">Aset Tidak Terikat</td>
                            </tr>
                            <tr>
                                <td>500</td>
                                <td>Hasil Aktivitas</td>
                                <td>Rp.0,-</td>
                            </tr>
                            <tr>
                                <td colspan="2">Aset Terikat/ Dana Abadi</td>
                                <td>Rp.0,-</td>
                            </tr>
                        </tbody>

                        <body class="table-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-center">Total</td>
                                @if (Auth::user()->hasRole('Bidang'))
                                    <td>Rp
                                        {{ number_format($saldoKas + $saldoBank + $jumlahPiutang + $jumlahBebanGaji + $jumlahBiayaOperasional + $jumlahBiayaKegiatanSiswa + $jumlahBiayaPemeliharaan + $jumlahBiayaSosial + $jumlahBiayaSeragam + $jumlahBiayaPerlengkapanExtra + $jumlahBiayaPeningkatanSDM, 2, ',', '.') }}
                                    </td>
                                @elseif(Auth::user()->hasRole('Bendahara'))
                                    <td>Rp
                                        {{ number_format($saldoKasTotal + $saldoBankTotal + $jumlahPiutang + $jumlahBebanGaji + $jumlahBiayaOperasional + $jumlahBiayaKegiatanSiswa + $jumlahBiayaPemeliharaan + $jumlahBiayaSosial + $jumlahBiayaSeragam + $jumlahBiayaPerlengkapanExtra + $jumlahBiayaPeningkatanSDM, 2, ',', '.') }}
                                    </td>
                                @endif
                            </tr>
                        </body>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

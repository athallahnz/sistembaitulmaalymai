@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">
            @if (auth()->user()->hasRole('Bidang'))
                Laporan Posisi Keuangan <strong>Bidang {{ auth()->user()->bidang->name }}</strong>
            @elseif(auth()->user()->hasRole('Bendahara'))
                Laporan Posisi Keuangan <strong>Yayasan</strong>
            @endif
        </h1>
        <form method="GET" class="row g-2 mb-2">
            <div class="col-auto">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', $startDate->toDateString()) }}">
            </div>
            <div class="col-auto">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', $endDate->toDateString()) }}">
            </div>
            <div class="col-auto align-self-end">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </div>
        </form>
        <div class="table-responsive">
            @foreach ($data as $tipe => $info)
                <h4 class="mt-4 text-capitalize">{{ $tipe }}</h4>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama Akun</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($info['akuns'] as $akun)
                            <tr>
                                <td>{{ $akun->nama_akun }}</td>
                                <td class="text-end">Rp {{ number_format($akun->saldo, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold">
                            <td>Total {{ ucfirst($tipe) }}</td>
                            <td class="text-end">Rp {{ number_format($info['total'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        </div>


        {{-- <h5 class="mt-4">Aset</h5>
        <table class="table table-bordered table-responsive">
            @foreach ($akunAset as $akun)
                <tr>
                    <td>{{ $akun->nama_akun }}</td>
                    <td class="text-end">Rp {{ number_format($akun->saldo, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="fw-bold">
                <td>Total Aset</td>
                <td class="text-end">Rp {{ number_format($totalAset, 0, ',', '.') }}</td>
            </tr>
        </table>

        <h5 class="mt-4">Kewajiban</h5>
        <table class="table table-bordered table-responsive">
            @foreach ($akunKewajiban as $akun)
                <tr>
                    <td>{{ $akun->nama_akun }}</td>
                    <td class="text-end">Rp {{ number_format($akun->saldo, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="fw-bold">
                <td>Total Kewajiban</td>
                <td class="text-end">Rp {{ number_format($totalKewajiban, 0, ',', '.') }}</td>
            </tr>
        </table>

        <h5 class="mt-4">Aset Neto</h5>
        <table class="table table-bordered table-responsive">
            @foreach ($akunEkuitas as $akun)
                <tr>
                    <td>{{ $akun->nama_akun }}</td>
                    <td class="text-end">Rp {{ number_format($akun->saldo, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="fw-bold">
                <td>Total Aset Neto</td>
                <td class="text-end">Rp {{ number_format($totalEkuitas, 0, ',', '.') }}</td>
            </tr>
        </table> --}}

    </div>
@endsection

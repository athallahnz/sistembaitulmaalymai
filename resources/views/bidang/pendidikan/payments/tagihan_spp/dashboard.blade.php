@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Dashboard <strong>Pembayaran SPP Siswa</strong></h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('tagihan-spp.dashboard') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="tahun" class="form-label">Tahun</label>
                <input type="number" name="tahun" id="tahun" class="form-control" value="{{ $tahun }}">
            </div>
            <div class="col-md-3">
                <label for="bulan" class="form-label">Bulan</label>
                <select name="bulan" id="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label for="kelas" class="form-label">Kelas</label>
                <select name="kelas" id="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasList as $kelas)
                        <option value="{{ $kelas->id }}" {{ $kelasId == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>

        <div class="table-responsive shadow p-3 rounded">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Murid</th>
                        <th>Kelas</th>
                        <th>Total Tagihan</th>
                        <th>Total Dibayar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $student)
                        <tr>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->kelas }}</td>
                            <td>Rp {{ number_format($student->total_tagihan, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($student->total_bayar, 0, ',', '.') }}</td>
                            <td>
                                @if ($student->total_bayar >= $student->total_tagihan && $student->total_tagihan > 0)
                                    <span class="badge bg-success">Lunas</span>
                                @elseif($student->total_tagihan == 0)
                                    <span class="badge bg-secondary">Belum Ada Tagihan</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Lunas</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('payment.show', $student->id) }}" class="btn btn-sm btn-info">Lihat
                                    Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

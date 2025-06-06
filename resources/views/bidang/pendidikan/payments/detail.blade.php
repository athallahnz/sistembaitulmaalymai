@extends('layouts.app')

@section('content')
<div class="container">
    <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('payment.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
            </ol>
        </nav>
    <h1 class="mb-4">Detail Pembayaran - <strong>{{ $student->name }} / {{ $student->eduClass->name}} - {{$student->eduClass->tahun_ajaran}}</strong></h1>

    {{-- Ringkasan Pembayaran --}}
    <div class="row mb-2">
        <div class="col-md-4">
            <div class="card border-primary shadow-sm">
                <div class="card-body text-center">
                    <h4>Total Biaya</h4>
                    <h2 class="text-primary"><strong>Rp {{ number_format($totalBiaya, 0, ',', '.') }}</strong></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success shadow-sm">
                <div class="card-body text-center">
                    <h4>Total Dibayar</h4>
                    <h2 class="text-success"><strong>Rp {{ number_format($totalBayar, 0, ',', '.') }}</strong></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger shadow-sm">
                <div class="card-body text-center">
                    <h4>Sisa Tanggungan</h4>
                    <h2 class="text-danger"><strong>Rp {{ number_format($sisa, 0, ',', '.') }}</strong></h2>
                    @if($sisa <= 0)
                        <span class="position-absolute top-100 start-50 translate-middle badge rounded-pill bg-success px-3 py-2">Lunas</span>
                    @else
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">Belum Lunas</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat Pembayaran --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <strong>Riwayat Pembayaran</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($student->payments as $index => $bayar)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($bayar->tanggal)->format('d-m-Y') }}</td>
                            <td>Rp {{ number_format($bayar->jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Belum ada pembayaran</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

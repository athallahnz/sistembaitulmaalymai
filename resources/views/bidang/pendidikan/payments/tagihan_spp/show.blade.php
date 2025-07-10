@extends('layouts.app')

@section('content')
    @php
        $jumlahLunas = $student->tagihanSpps->where('status', 'lunas')->count();
        $persentaseLunas = round(($jumlahLunas / 12) * 100);
    @endphp
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('payment.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a>Detail</a></li>
            </ol>
        </nav>
        <h1 class="mb-4">Detail Pembayaran SPP - <strong>{{ $student->name }} / {{ $student->eduClass->name }} -
                {{ $student->eduClass->tahun_ajaran }}</strong></h1>

        <div class="mb-4">
            <h1 class="mb-3">Progress Pembayaran SPP ({{ $jumlahLunas }}/12 bulan)</h1>
            <div class="progress">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $persentaseLunas }}%;"
                    aria-valuenow="{{ $persentaseLunas }}" aria-valuemin="0" aria-valuemax="100">{{ $persentaseLunas }}%
                </div>
            </div>
        </div>

        <h1 class="mb-4">Riwayat Pembayaran</h1>
        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped mb-0 ">
                    <thead class="table-light">
                        <tr>
                            <th>Tahun</th>
                            <th>Bulan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Tanggal Dibayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->tagihanSpps as $tagihan)
                            <tr>
                                <td>{{ $tagihan->tahun }}</td>
                                <td>{{ \Carbon\Carbon::create()->month($tagihan->bulan)->translatedFormat('F') }}</td>
                                <td>Rp {{ number_format($tagihan->jumlah, 0, ',', '.') }}</td>
                                <td>
                                    @if ($tagihan->status == 'lunas')
                                        <span class="badge bg-success">Lunas</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Lunas</span>
                                    @endif
                                </td>
                                <td>{{ $tagihan->updated_at ? \Carbon\Carbon::parse($tagihan->updated_at)->format('d M Y') : '-' }}
                                </td>
                                <td>
                                    @if ($tagihan->status == 'lunas')
                                        <a href="{{ route('tagihan-spp.kwitansi.per', $tagihan->id) }}" target="_blank"
                                            class="btn btn-sm btn-primary">
                                            <i class="bi bi-printer"></i> Cetak
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada pembayaran</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <a href="{{ route('tagihan-spp.dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>
@endsection

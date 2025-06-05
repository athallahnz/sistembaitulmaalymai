@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Detail <strong>Tagihan SPP Siswa</strong></h1>

    <div class="mb-4">
        <h5>Informasi Siswa</h5>
        <ul class="list-group">
            <li class="list-group-item"><strong>Nama:</strong> {{ $student->name }}</li>
            <li class="list-group-item"><strong>Kelas:</strong> {{ $student->eduClass->name ?? '-' }}</li>
            <li class="list-group-item"><strong>NIS:</strong> {{ $student->nis ?? '-' }}</li>
        </ul>
    </div>

    <div class="mb-4">
        <h5>Riwayat Tagihan</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tahun</th>
                    <th>Bulan</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Tanggal Dibayar</th>
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
                        <td>{{ $tagihan->updated_at ? \Carbon\Carbon::parse($tagihan->updated_at)->format('d M Y') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Belum ada tagihan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('tagihan-spp.dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>
@endsection

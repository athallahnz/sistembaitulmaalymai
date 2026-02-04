@extends('layouts.public-warga')

@section('title', 'Tracking Infaq')

@section('content')
    <div class="container py-4">

        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <h4 class="mb-2">{{ $warga->nama }}</h4>
                <p class="mb-1"><strong>Nomor HP:</strong> {{ $warga->hp }}</p>
                <p class="mb-1"><strong>Alamat:</strong> {{ $warga->alamat }}</p>
                <p class="mb-0"><strong>RT / No:</strong> {{ $warga->rt }} / {{ $warga->no }}</p>
            </div>
        </div>

        {{-- Filter Tahun --}}
        <div class="card mb-3 shadow-sm">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <form method="GET" action="{{ route('warga.dashboard') }}" class="d-flex gap-2 align-items-center">
                    <label class="fw-semibold">Tahun</label>
                    <input type="number" name="tahun" class="form-control" style="width:130px" min="2020"
                        max="2100" value="{{ (int) ($tahun ?? now()->year) }}">
                    <button class="btn btn-outline-primary" type="submit">Terapkan</button>
                </form>

                <div class="text-end">
                    <div class="text-muted small">Total Infaq Tahun {{ (int) ($tahun ?? now()->year) }}</div>
                    <div class="fw-bold">Rp {{ number_format($total ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <h5 class="mb-3">Status Infaq Bulanan</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:160px;">Bulan</th>
                        <th>Nominal</th>
                        <th style="width:140px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(1, 12) as $b)
                        @php
                            $row = $status[$b] ?? null;
                            $nom = (float) ($row['nominal'] ?? 0);
                            $lunas = (bool) ($row['lunas'] ?? false);
                            $bulanNama = $bulanMap[$b] ?? 'Bulan ' . $b;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $bulanNama }}</td>
                            <td>Rp {{ number_format($nom, 0, ',', '.') }}</td>
                            <td>
                                @if ($lunas)
                                    <span class="badge bg-success">Lunas</span>
                                @else
                                    <span class="badge bg-danger">Belum Lunas</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="table-light">
                        <td class="fw-semibold">Total</td>
                        <td class="fw-semibold">Rp {{ number_format($total ?? 0, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info mt-4">
            Jika ada ketidaksesuaian data, silakan hubungi
            <strong>Bidang Sosial Yayasan Masjid Al Iman Sutorejo Indah</strong>.
        </div>
    </div>
@endsection

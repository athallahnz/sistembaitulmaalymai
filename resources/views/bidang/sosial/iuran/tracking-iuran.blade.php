@extends('layouts.public-warga')

@section('title', 'Tracking Iuran Sosial')

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
                <form method="GET" action="{{ route('warga.iuran') }}" class="d-flex gap-2 align-items-center">
                    <label class="fw-semibold">Tahun</label>
                    <input type="number" name="tahun" class="form-control" style="width:130px" min="2020"
                        max="2100" value="{{ (int) ($tahun ?? now()->year) }}">
                    <button class="btn btn-outline-primary" type="submit">Terapkan</button>
                </form>

                <div class="text-end">
                    <div class="text-muted small">Ringkasan Tahun {{ (int) ($tahun ?? now()->year) }}</div>
                    <div class="fw-semibold">Tagihan: Rp {{ number_format($totalTagihan ?? 0, 0, ',', '.') }}</div>
                    <div class="fw-bold">Terbayar: Rp {{ number_format($totalBayar ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <h5 class="mb-3">Status Iuran Sosial Bulanan</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:160px;">Bulan</th>
                        <th>Tagihan</th>
                        <th>Terbayar</th>
                        <th style="width:140px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(1, 12) as $b)
                        @php
                            $row = $status[$b] ?? [];
                            $bulanNama = $bulanMap[$b] ?? 'Bulan ' . $b;
                            $tagihan = (int) ($row['nominal_tagihan'] ?? 0);
                            $bayar = (int) ($row['nominal_bayar'] ?? 0);
                            $st = (string) ($row['status'] ?? 'belum');
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $bulanNama }}</td>
                            <td>Rp {{ number_format($tagihan, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($bayar, 0, ',', '.') }}</td>
                            <td>
                                @if ($st === 'lunas')
                                    <span class="badge bg-success">Lunas</span>
                                @elseif ($st === 'sebagian')
                                    <span class="badge bg-warning text-dark">Sebagian</span>
                                @else
                                    <span class="badge bg-danger">Belum</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    <tr class="table-light">
                        <td class="fw-semibold">Total</td>
                        <td class="fw-semibold">Rp {{ number_format($totalTagihan ?? 0, 0, ',', '.') }}</td>
                        <td class="fw-semibold">Rp {{ number_format($totalBayar ?? 0, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info mt-4">
            Jika ada ketidaksesuaian data, silakan hubungi <strong>Yayasan Masjid Al Iman Sutorejo Indah</strong>.
        </div>

    </div>
@endsection

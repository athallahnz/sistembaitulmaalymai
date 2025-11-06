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

        <h5 class="mb-3">Status Infaq Bulanan</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Bulan</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bulanList as $b)
                        @php
                            $nom = $status[$b]['nominal'];
                            $lunas = $status[$b]['lunas'];
                        @endphp
                        <tr>
                            <td class="text-capitalize">{{ $b }}</td>
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
                        <td class="fw-semibold">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info mt-4">
            Jika ada ketidaksesuaian data, silakan hubungi <strong>Bidang Sosial Yayasan Masjid Al Iman Sutorejo Indah</strong>.
        </div>
    </div>
@endsection

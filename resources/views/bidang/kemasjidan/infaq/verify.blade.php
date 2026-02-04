@extends('layouts.app')

@section('title', 'Verifikasi Kwitansi')

@section('content')
    <div class="container py-4">
        <h3 class="mb-3">Verifikasi Kwitansi Infaq Kemasjidan</h3>

        <div class="card">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <div><strong>Kode Kwitansi</strong></div>
                        <div class="text-monospace">{{ $kode ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div><strong>Status</strong></div>
                        @if (!empty($valid) && $valid)
                            <span class="badge bg-success">Valid</span>
                        @else
                            <span class="badge bg-secondary">Tidak Valid / Tidak Ditemukan</span>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <div><strong>Nama</strong></div>
                        <div>{{ $warga->nama ?? '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <div><strong>Bulan</strong></div>
                        <div>{{ $bulanNama ?? ($bulan ?? '-') }}</div>
                    </div>

                    <div class="col-md-3">
                        <div><strong>Tahun</strong></div>
                        <div>{{ $tahun ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div><strong>Nominal</strong></div>
                        <div>Rp {{ number_format((float) ($nominal ?? 0), 0, ',', '.') }}</div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

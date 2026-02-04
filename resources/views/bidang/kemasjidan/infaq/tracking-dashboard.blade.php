@extends('layouts.public-warga')

@section('title', 'Dashboard Warga')

@section('content')
    <div class="container py-4">

        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <h4 class="mb-2">{{ $warga->nama }}</h4>
                <div class="text-muted small">
                    HP: {{ $warga->hp ?? '-' }} • RT/No: {{ $warga->rt ?? '-' }}/{{ $warga->no ?? '-' }}
                </div>
            </div>
        </div>

        <div class="card mb-3 shadow-sm">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <form method="GET" action="{{ route('warga.dashboard') }}" class="d-flex gap-2 align-items-center">
                    <label class="fw-semibold">Tahun</label>
                    <input type="number" name="tahun" class="form-control" style="width:130px" min="2020"
                        max="2100" value="{{ (int) ($tahun ?? now()->year) }}">
                    <button class="btn btn-outline-primary" type="submit">Terapkan</button>
                </form>
            </div>
        </div>

        <div class="row g-3">
            {{-- INFAQ --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-2">Infaq Kemasjidan</h5>
                        <div class="text-muted small mb-3">Ringkasan tahun {{ (int) ($tahun ?? now()->year) }}</div>

                        <div class="d-flex justify-content-between">
                            <div>Total</div>
                            <div class="fw-bold">Rp {{ number_format($infaqTotal ?? 0, 0, ',', '.') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <div>Bulan terbayar</div>
                            <div class="fw-semibold">{{ (int) ($infaqLunasCount ?? 0) }} / 12</div>
                        </div>

                        <a href="{{ route('warga.infaq', ['tahun' => (int) ($tahun ?? now()->year)]) }}"
                            class="btn btn-outline-primary mt-3 w-100">
                            Lihat Tracking Infaq
                        </a>
                    </div>
                </div>
            </div>

            {{-- IURAN --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-2">Iuran Sosial</h5>
                        <div class="text-muted small mb-3">Ringkasan tahun {{ (int) ($tahun ?? now()->year) }}</div>

                        <div class="d-flex justify-content-between">
                            <div>Total Tagihan</div>
                            <div class="fw-semibold">Rp {{ number_format($iuranTotalTagihan ?? 0, 0, ',', '.') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <div>Total Terbayar</div>
                            <div class="fw-bold">Rp {{ number_format($iuranTotalBayar ?? 0, 0, ',', '.') }}</div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <div>Bulan lunas</div>
                            <div class="fw-semibold">{{ (int) ($iuranLunasCount ?? 0) }} / 12</div>
                        </div>

                        <a href="{{ route('warga.iuran', ['tahun' => (int) ($tahun ?? now()->year)]) }}"
                            class="btn btn-outline-primary mt-3 w-100">
                            Lihat Tracking Iuran
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

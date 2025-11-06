@extends('layouts.app')

@section('title', 'Detail Infaq Warga')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4">Detail Infaq untuk {{ $warga->nama }}</h3>

        {{-- ALERT --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM UPDATE BULANAN --}}
        <form method="POST" action="{{ route('sosial.infaq.update', $warga->id) }}" id="form-infaq-update">
            @csrf
            @method('PUT')

            <div class="row g-3">
                @php
                    $bulanList = [
                        'januari',
                        'februari',
                        'maret',
                        'april',
                        'mei',
                        'juni',
                        'juli',
                        'agustus',
                        'september',
                        'oktober',
                        'november',
                        'desember',
                    ];
                @endphp

                @foreach ($bulanList as $bulan)
                    <div class="col-md-3">
                        <label class="form-label text-capitalize">{{ $bulan }}</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="{{ $bulan }}" class="form-control nominal-bulan"
                                value="{{ old($bulan, $infaq->$bulan ?? 0) }}" step="1000" min="0">
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <label class="form-label fw-semibold">Total</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="total" id="total-infaq" class="form-control"
                        value="{{ old('total', $infaq->total ?? 0) }}" readonly>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success">Simpan</button>
                <a href="{{ route('sosial.infaq.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </form>

        {{-- STATUS PER BULAN + CETAK KWITANSI --}}
        <hr class="my-5">

        <h5 class="mb-3">Status Pembayaran Per Bulan</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Bulan</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Kwitansi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bulanList as $bulan)
                        @php
                            $nom = (float) ($infaq->$bulan ?? 0);
                            $lunas = $nom > 0;
                        @endphp
                        <tr>
                            <td class="text-capitalize">{{ $bulan }}</td>
                            <td>Rp {{ number_format($nom, 0, ',', '.') }}</td>
                            <td>
                                @if ($lunas)
                                    <span class="badge bg-success">Lunas</span>
                                @else
                                    <span class="badge bg-secondary">Belum</span>
                                @endif
                            </td>
                            <td>
                                @if ($lunas)
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('sosial.infaq.receipt', ['warga' => $warga->id, 'bulan' => $bulan]) }}"
                                        target="_blank">
                                        Cetak Kwitansi
                                    </a>
                                    <a class="btn btn-sm btn-outline-success"
                                        href="{{ route('sosial.infaq.open-wa', ['warga' => $warga->id, 'bulan' => $bulan]) }}"
                                        target="_blank">
                                        Kirim via WhatsApp
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Belum tersedia</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.nominal-bulan');
            const totalEl = document.getElementById('total-infaq');

            function hitungTotal() {
                let t = 0;
                inputs.forEach(i => {
                    const v = parseFloat(i.value || '0');
                    if (!isNaN(v)) t += v;
                });
                totalEl.value = Math.round(t);
            }

            inputs.forEach(i => {
                i.addEventListener('input', hitungTotal);
                i.addEventListener('change', hitungTotal);
            });
        });
    </script>
@endpush

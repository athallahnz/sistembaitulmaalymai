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
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-3">Detail Pembayaran SPP</h1>
                <p class="text-muted">
                    <strong>{{ $student->name }}</strong> /
                    {{ $student->eduClass->name }} -
                    {{ $student->eduClass->tahun_ajaran }}
                </p>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Progress Pembayaran SPP</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ $jumlahLunas }}/12 bulan</span>
                    <span class="badge bg-info">{{ $persentaseLunas }}%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $persentaseLunas }}%;"
                        aria-valuenow="{{ $persentaseLunas }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <form id="form-recognize-spp-student" action="{{ route('tagihan-spp.recognize.student', $student->id) }}"
                method="POST" class="d-inline">
                @csrf

                <input type="hidden" name="bulan" value="{{ now()->month }}">
                <input type="hidden" name="tahun" value="{{ now()->year }}">

                <button type="submit" class="btn btn-primary mb-3" id="btn-recognize-spp-student">
                    <i class="bi bi-check-circle"></i> Recognize SPP Bulan Ini
                </button>
            </form>
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
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-recognize-spp-student');
            const btn = document.getElementById('btn-recognize-spp-student');

            btn.addEventListener('click', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Konfirmasi Pengakuan Pendapatan',
                    html: `
                <div class="text-start">
                    <p>Anda akan melakukan <strong>pengakuan pendapatan SPP</strong> untuk:</p>
                    <table class="table table-sm mt-2">
                        <tr>
                            <td>Nama Siswa</td>
                            <td>: <strong>{{ $student->name }}</strong></td>
                        </tr>
                        <tr>
                            <td>Periode</td>
                            <td>: <strong>{{ now()->month }}/{{ now()->year }}</strong></td>
                        </tr>
                    </table>
                    <p class="text-danger fw-semibold mt-2 mb-0">
                        Transaksi ini tidak dapat dibatalkan.
                    </p>
                </div>
            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        btn.disabled = true;
                        btn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Memproses...
                `;
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush

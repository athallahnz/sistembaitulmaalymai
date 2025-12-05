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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2">Detail Pembayaran PMB</h1>
                <p class="text-muted mb-0">
                    <strong>{{ $student->name }}</strong> /
                    {{ $student->eduClass->name }} -
                    {{ $student->eduClass->tahun_ajaran }}
                </p>
            </div>
            <form id="form-recognize-pmb-{{ $student->id }}" action="{{ route('payment.recognize_pmb', $student->id) }}"
                method="POST">
                @csrf
                <button type="button" class="btn btn-success"
                    onclick="confirmRecognizePMB('{{ $student->name }}', {{ $student->id }})">
                    <i class="bi bi-check-circle"></i> Recognize Pendapatan PMB
                </button>
            </form>
        </div>

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
                        @if ($sisa <= 0)
                            <span
                                class="position-absolute top-100 start-50 translate-middle badge rounded-pill bg-success px-3 py-2">Lunas</span>
                        @else
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">Belum
                                Lunas</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Riwayat Pembayaran --}}
        <h1 class="mb-4">Riwayat Pembayaran</h1>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $index => $bayar)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($bayar->tanggal)->format('d-m-Y') }}</td>
                                <td>Rp {{ number_format($bayar->jumlah, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('payments.kwitansi.per', $bayar->id) }}" target="_blank"
                                        class="btn btn-sm btn-primary">
                                        <i class="bi bi-printer"></i> Cetak
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada pembayaran</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <a href="{{ route('payment.dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>
@endsection
@push('scripts')
    <script>
        function confirmRecognizePMB(studentName, studentId) {
            Swal.fire({
                title: 'Konfirmasi Pengakuan Pendapatan',
                html: `Proses pengakuan pendapatan PMB untuk <b>${studentName}</b>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, proses',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('form-recognize-pmb-' + studentId);
                    if (!form) {
                        console.error('Form recognize PMB tidak ditemukan untuk student id:', studentId);
                        return;
                    }
                    form.submit();
                }
            });
        }
    </script>
@endpush

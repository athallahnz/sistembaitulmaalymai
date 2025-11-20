@extends('layouts.app')

@section('title', 'Detail Pengajuan')

@section('content')
    <div class="container py-4">
        {{-- Header Nav --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Detail Pengajuan</h1>
            <a href="{{ route('pengajuan.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="row g-4">
            {{-- Kolom Kiri: Informasi Utama --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title mb-1">{{ $pengajuan->judul }}</h4>
                                <p class="text-muted mb-0">
                                    Diajukan oleh: <strong>{{ $pengajuan->pembuat->name }}</strong>
                                    ({{ $pengajuan->bidang->name ?? '-' }})
                                </p>
                                <small class="text-muted">{{ $pengajuan->created_at->format('d F Y, H:i') }}</small>
                            </div>
                            <div class="text-end">
                                @php
                                    $badgeClass = match ($pengajuan->status) {
                                        'Menunggu Verifikasi' => 'bg-warning text-dark',
                                        'Disetujui' => 'bg-primary',
                                        'Dicairkan' => 'bg-success',
                                        'Ditolak' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} fs-6 px-3 py-2">{{ $pengajuan->status }}</span>
                            </div>
                        </div>

                        @if ($pengajuan->deskripsi)
                            <div class="alert alert-light border mt-3 mb-0">
                                <strong>Catatan:</strong><br>
                                {{ $pengajuan->deskripsi }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tabel Rincian Item --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">Rincian Item Anggaran</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Akun (CoA) / Keterangan</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pengajuan->details as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $item->akunKeuangan->nama_akun }}</div>
                                            <div class="small text-muted">{{ $item->keterangan_item }}</div>
                                        </td>
                                        <td class="text-center">{{ $item->kuantitas + 0 }}</td> {{-- +0 hack utk hilangkan .00 desimal --}}
                                        <td class="text-end">Rp {{ number_format($item->harga_pokok, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold">Rp
                                            {{ number_format($item->jumlah_dana, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold text-uppercase">Total Pengajuan</td>
                                    <td class="text-end fw-bold text-primary fs-5">Rp
                                        {{ number_format($pengajuan->total_jumlah, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Panel Aksi (Approval) --}}
            <div class="col-md-4">

                {{-- Info Validator (Jika sudah diapprove/tolak) --}}
                @if ($pengajuan->validator_id)
                    <div
                        class="card shadow-sm border-0 mb-3 bg-opacity-10 {{ $pengajuan->status == 'Ditolak' ? 'bg-danger border-danger' : 'bg-success border-success' }}">
                        <div class="card-body">
                            <h6 class="fw-bold {{ $pengajuan->status == 'Ditolak' ? 'text-danger' : 'text-success' }}">
                                <i class="bi bi-info-circle"></i> Status Verifikasi
                            </h6>
                            <p class="mb-1 small">
                                Diproses oleh: <strong>{{ $pengajuan->validator->name ?? '-' }}</strong>
                            </p>
                            <p class="mb-0 small">
                                Tanggal: {{ \Carbon\Carbon::parse($pengajuan->tgl_verifikasi)->format('d M Y H:i') }}
                            </p>
                            @if ($pengajuan->status == 'Ditolak' && $pengajuan->alasan_tolak)
                                <hr>
                                <p class="mb-0 small text-danger"><strong>Alasan
                                        Ditolak:</strong><br>{{ $pengajuan->alasan_tolak }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- ===== 2. PANEL PENCAIRAN (Oleh Bendahara) (NEW) ===== --}}
                @if ($pengajuan->status == 'Dicairkan' && $pengajuan->treasurer_id)
                    <div class="card shadow-sm border-0 mb-3 bg-opacity-10 bg-success border-success">
                        <div class="card-body">
                            <h6 class="fw-bold text-success">
                                <i class="bi bi-bank"></i> Status Pencairan
                            </h6>
                            <p class="mb-1 small">
                                Dicairkan oleh: <strong>{{ $pengajuan->treasurer->name ?? '-' }}</strong>
                            </p>
                            <p class="mb-0 small">
                                Tanggal Pencairan:
                                {{ \Carbon\Carbon::parse($pengajuan->tgl_pencairan)->format('d M Y H:i') }}
                            </p>
                            <hr>
                            <p class="mb-0 small text-success">
                                **Dana sudah ditransfer ke Kas/Bank Bidang.**
                            </p>
                        </div>
                    </div>
                @endif

                {{-- PANEL AKSI KHUSUS MANAJER KEUANGAN --}}
                @if (auth()->user()->role == 'Manajer Keuangan' && $pengajuan->status == 'Menunggu Verifikasi')
                    <div class="card shadow border-0">
                        <div class="card-header bg-primary">
                            <h6 class="mb-0 text-white fw-bold"><i class="bi bi-shield-lock"></i> Panel Validasi</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">Silakan periksa rincian anggaran sebelum menyetujui.</p>

                            {{-- 1. Form Approve (Setuju) --}}
                            <form id="form-approve" action="{{ route('pengajuan.approve', $pengajuan->id) }}"
                                method="POST" class="d-grid gap-2 mb-2">
                                @csrf
                                {{-- Hapus onclick return confirm, ganti dengan type="button" dan event handler JS --}}
                                <button type="button" class="btn btn-success btn-lg" onclick="confirmApprove()">
                                    <i class="bi bi-check-circle"></i> Setujui Pengajuan
                                </button>
                            </form>

                            {{-- 2. Form Reject (Tolak) - Hidden --}}
                            {{-- Kita buat form tersembunyi untuk menangani submit penolakan dari SweetAlert --}}
                            <form id="form-reject" action="{{ route('pengajuan.reject', $pengajuan->id) }}" method="POST"
                                style="display: none;">
                                @csrf
                                <input type="hidden" name="alasan_tolak" id="input-alasan-tolak">
                            </form>

                            {{-- Tombol Tolak (Trigger SweetAlert, bukan Modal Bootstrap) --}}
                            <button type="button" class="btn btn-outline-danger w-100" onclick="confirmReject()">
                                <i class="bi bi-x-circle"></i> Tolak Pengajuan
                            </button>
                        </div>
                    </div>
                @endif

                {{-- PANEL AKSI KHUSUS BENDAHARA (PENCAIRAN) --}}
                @if (auth()->user()->role == 'Bendahara' && $pengajuan->status == 'Disetujui')
                    <div class="card shadow border-0 mt-3">
                        <div class="card-header bg-success">
                            <h6 class="mb-0 text-white"><i class="bi bi-cash-stack"></i> Panel Pencairan Dana</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Pastikan saldo mencukupi sebelum mencairkan dana sebesar
                                <strong>Rp {{ number_format($pengajuan->total_jumlah, 0, ',', '.') }}</strong>.
                            </p>

                            <form id="form-cairkan" action="{{ route('pengajuan.cairkan', $pengajuan->id) }}"
                                method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Tanggal Transaksi</label>
                                    <input type="date" name="tanggal_cair" class="form-control"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>

                                {{-- Input Sumber Dana (Kredit) --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Sumber Dana (Kredit)</label>
                                    <select name="sumber_dana_id" class="form-select" id="sumber_dana_id" required>
                                        <option value="">-- Pilih Kas / Bank --</option>
                                        @foreach ($akunKasBank as $akun)
                                            <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text small">Akun ini akan berkurang (Kredit).</div>

                                    {{-- ELEMEN TAMPILAN SALDO --}}
                                    <div class="mt-2 p-2 rounded bg-light small">
                                        Saldo Tersedia: <strong id="saldo_display" class="text-primary">-- Pilih Akun
                                            --</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Kode Referensi (Opsional)</label>
                                    <input type="text" name="kode_referensi" class="form-control"
                                        placeholder="Auto generate if empty">
                                </div>

                                <button type="button" class="btn btn-success w-100" onclick="confirmCairkan()">
                                    <i class="bi bi-check-circle"></i> Proses Pencairan
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            // Logika Saldo Real-time
            $('#sumber_dana_id').on('change', function() {
                const akunId = $(this).val();
                const saldoDisplay = $('#saldo_display');

                saldoDisplay.html(
                    '<i class="bi bi-arrow-clockwise spinner-border spinner-border-sm"></i> Memuat Saldo...'
                );

                if (akunId) {
                    // Total yang akan dikeluarkan dari akun ini
                    const totalPengajuan = parseFloat({{ $pengajuan->total_jumlah }});

                    $.ajax({
                        // Panggil route yang baru dibuat (api.get-saldo)
                        url: '{{ route('api.get-saldo', ['akunId' => '__akun_id__']) }}'.replace(
                            '__akun_id__', akunId),
                        method: 'GET',
                        success: function(response) {
                            const saldo = parseFloat(response.saldo);

                            // Logika Warna Saldo
                            let colorClass = 'text-primary';
                            if (saldo < totalPengajuan) {
                                colorClass = 'text-danger'; // Saldo kurang
                            } else if (saldo === 0) {
                                colorClass = 'text-muted';
                            } else {
                                colorClass = 'text-success'; // Saldo cukup
                            }

                            saldoDisplay.html(
                                `<span class="${colorClass}">${response.formatted}</span>`);
                        },
                        error: function() {
                            saldoDisplay.html(
                                '<span class="text-danger">Gagal mengambil data saldo.</span>'
                            );
                        }
                    });
                } else {
                    saldoDisplay.html('<strong>-- Pilih Akun --</strong>');
                }
            });

            // PENTING: Trigger change event saat halaman dimuat jika ada nilai default
            $('#sumber_dana_id').trigger('change');
        });

        // Fungsi Konfirmasi Setuju
        function confirmApprove() {
            Swal.fire({
                title: 'Setujui Anggaran?',
                text: "Status pengajuan akan berubah menjadi Disetujui.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754', // Warna Hijau Bootstrap (success)
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form secara programmatik
                    document.getElementById('form-approve').submit();
                }
            })
        }

        // Fungsi Konfirmasi Tolak (Dengan Input Textarea)
        function confirmReject() {
            Swal.fire({
                title: 'Tolak Pengajuan',
                text: 'Silakan masukkan alasan penolakan:',
                input: 'textarea', // Tipe input area
                inputPlaceholder: 'Contoh: Anggaran terlalu besar atau tidak relevan...',
                inputAttributes: {
                    'aria-label': 'Tuliskan alasan penolakan'
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545', // Warna Merah Bootstrap (danger)
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Kirim Penolakan',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Anda wajib menuliskan alasannya!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // 1. Masukkan nilai dari SweetAlert ke input hidden form
                    document.getElementById('input-alasan-tolak').value = result.value;

                    // 2. Submit form reject
                    document.getElementById('form-reject').submit();
                }
            })
        }

        // Fungsi Konfirmasi Pencairan (Bendahara)
        function confirmCairkan() {
            // AKSES FORM DI SINI MENGGUNAKAN ID (SOLUSI REFERENCE ERROR)
            const formCairkan = document.getElementById('form-cairkan');

            // Safety check jika elemen tidak ditemukan
            if (!formCairkan) {
                console.error("Form 'form-cairkan' tidak ditemukan.");
                return;
            }

            // 1. Cek validasi form HTML5 standard sebelum SweetAlert muncul
            if (!formCairkan.checkValidity()) {
                formCairkan.reportValidity();
                return; // Hentikan jika ada input required yang kosong
            }

            // 2. Tampilkan SweetAlert Konfirmasi
            Swal.fire({
                title: 'Proses Pencairan?',
                text: "Transaksi akan dicatat otomatis ke Buku Besar dan status menjadi Dicairkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Cairkan Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // 3. Submit form jika user menekan 'Ya'
                    formCairkan.submit();
                }
            });
        }
    </script>
@endpush

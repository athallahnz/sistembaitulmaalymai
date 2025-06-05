@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Dashboard <strong>Pembayaran SPP Siswa</strong></h1>
        <!-- Tombol Trigger Modal -->
        <div class="mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                + Tambah Pembayaran
            </button>
        </div>

        <!-- Modal Form Pembayaran -->
        <div class="modal fade" id="modalPembayaran" tabindex="-1" aria-labelledby="modalPembayaranLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('tagihan-spp.bayar') }}"
                    class="modal-content border rounded shadow-sm bg-light">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPembayaranLabel">Form Pembayaran Murid</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rfid_uid_input" class="form-label">Tempelkan ID Card Murid</label>
                            <input type="text" id="rfid_uid_input" name="rfid_uid" class="form-control text-center"
                                placeholder="Tempelkan Kartu RFID..." required autofocus>
                        </div>

                        <!-- Card loading dan hasil siswa -->
                        <div id="student-card" class="mt-4 d-none">
                            <div id="student-card-body"></div>
                        </div>

                        <div class="mb-3 mt-4">
                            <label for="jumlah" class="form-label">Jumlah Bayar</label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control"
                                placeholder="Masukkan jumlah" required disabled>
                        </div>
                        <input type="hidden" name="student_id" id="student_id" value="">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" id="submitBtn" class="btn btn-primary" disabled>Bayar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Chart SPP per Siswa -->
        <div class="shadow p-3 mb-4 bg-white rounded">
            <canvas id="sppChart" height="120"></canvas>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="filterForm" method="GET" action="{{ route('tagihan-spp.dashboard') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="tahun" class="form-label">Tahun</label>
                <input type="number" name="tahun" id="tahun" class="form-control" value="{{ $tahun }}">
            </div>
            <div class="col-md-3">
                <label for="bulan" class="form-label">Bulan</label>
                <select name="bulan" id="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label for="kelas" class="form-label">Kelas</label>
                <select name="kelas" id="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasList as $kelas)
                        <option value="{{ $kelas->id }}" {{ $kelasId == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100" type="submit" id="submitBtn">Tampilkan</button>
                <div id="loadingSpinner" class="text-center mt-2 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive shadow p-3 rounded">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Murid</th>
                        <th>Kelas</th>
                        <th>Total Tagihan</th>
                        <th>Total Dibayar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $student)
                        <tr>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->kelas }}</td>
                            <td>Rp {{ number_format($student->total_tagihan, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($student->total_bayar, 0, ',', '.') }}</td>
                            <td>
                                @if ($student->total_bayar >= $student->total_tagihan && $student->total_tagihan > 0)
                                    <span class="badge bg-success">Lunas</span>
                                @elseif($student->total_tagihan == 0)
                                    <span class="badge bg-secondary">Belum Ada Tagihan</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Lunas</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tagihan-spp.show', $student->id) }}" class="btn btn-sm btn-info">Lihat
                                    Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rfidInput = document.getElementById('rfid_uid_input');
            const studentCard = document.getElementById('student-card');
            const studentCardBody = document.getElementById('student-card-body');
            const submitBtn = document.getElementById('submitBtn');
            const jumlahInput = document.getElementById('jumlah');
            const studentIdInput = document.getElementById('student_id');
            const studentModal = document.getElementById('modalPembayaran');

            studentModal.addEventListener('hidden.bs.modal', () => {
                // Kosongkan semua field
                rfidInput.value = '';
                studentCardBody.innerHTML = '';
                studentIdInput.value = '';
                jumlahInput.value = '';
                jumlahInput.disabled = true;
                submitBtn.disabled = true;
            });

            if (!rfidInput || !submitBtn || !studentCard || !jumlahInput || !studentIdInput) return;

            submitBtn.disabled = true;
            jumlahInput.disabled = true;

            rfidInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') e.preventDefault();
            });

            rfidInput.addEventListener('input', function() {
                const uid = this.value.trim();

                if (!uid) {
                    studentCard.classList.add('d-none');
                    studentCardBody.innerHTML = '';
                    submitBtn.disabled = true;
                    jumlahInput.disabled = true;
                    studentIdInput.value = '';
                    return;
                }

                studentCard.classList.remove('d-none');
                studentCardBody.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary me-2" role="status" style="width: 1.5rem; height: 1.5rem;">
                <span class="visually-hidden">Loading...</span>
                </div>
                <span class="text-muted">Mencari data tagihan siswa...</span>
            </div>
        `;

                clearTimeout(window.fetchTimeout);
                window.fetchTimeout = setTimeout(() => {
                    fetch(`/api/spp-tagihan-by-rfid/${uid}`)
                        .then(res => {
                            if (!res.ok) throw new Error('Siswa tidak ditemukan');
                            return res.json();
                        })
                        .then(data => {
                            if (data && data.name) {
                                if (data.total === 0) {
                                    studentCardBody.innerHTML = `
                            <div class="alert alert-success">✅ Semua tagihan sudah lunas.</div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" value="${data.name}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <input type="text" class="form-control" value="${data.edu_class ?? '-'}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Tagihan</label>
                        <input type="text" class="form-control" value="Rp 0" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sisa Tagihan</label>
                        <input type="text" class="form-control" value="Rp 0" readonly>
                    </div>
                `;
                                    submitBtn.disabled = true;
                                    jumlahInput.disabled = true;
                                    studentIdInput.value = '';
                                    jumlahInput.value = '';
                                    return;
                                } // Jika masih ada tagihan belum lunas
                                studentCardBody.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" value="${data.name}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kelas</label>
                    <input type="text" class="form-control" value="${data.edu_class ?? '-'}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Tagihan</label>
                    <input type="text" class="form-control" value="Rp ${Number(data.total).toLocaleString()}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sisa Tagihan</label>
                    <input type="text" class="form-control" value="Rp ${Number(data.sisa).toLocaleString()}" readonly>
                </div>
            `;
                                studentIdInput.value = data.id;
                                submitBtn.disabled = false;
                                jumlahInput.disabled = false;
                                jumlahInput.value = data.sisa; // default bayar sisa tagihan
                            } else {
                                studentCardBody.innerHTML =
                                    `<p class="text-danger mb-0">❌ Siswa tidak ditemukan!</p>`;
                                submitBtn.disabled = true;
                                jumlahInput.disabled = true;
                                studentIdInput.value = '';
                            }
                        })
                        .catch(() => {
                            studentCardBody.innerHTML =
                                `<p class="text-danger">⚠️ Terjadi kesalahan saat mengambil data.</p>`;
                            submitBtn.disabled = true;
                            jumlahInput.disabled = true;
                            studentIdInput.value = '';
                        });
                }, 300);
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const sppCtx = document.getElementById('sppChart').getContext('2d');
        const sppChart = new Chart(sppCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                        label: 'Total Tagihan',
                        data: {!! json_encode($chartTagihan) !!},
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Pembayaran',
                        data: {!! json_encode($chartPembayaran) !!},
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(
                                    context.raw);
                            }
                        }
                    }
                }
            }
        });
    </script>
@endpush

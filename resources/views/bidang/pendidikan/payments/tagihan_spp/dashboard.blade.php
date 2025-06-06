@extends('layouts.app')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
        <h1 class="mb-4">Dashboard <strong>Pembayaran SPP Siswa</strong></h1>
        <!-- Tombol Trigger Modal -->
        <div class="mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                + Tambah Pembayaran
            </button>
        </div>

        <!-- Modal Form Pembayaran -->
        <div class="modal fade" id="modalPembayaran" tabindex="-1" aria-labelledby="modalPembayaranLabel"
            aria-hidden="true">
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

        <form id="filterForm" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="tahun" class="form-label">Tahun</label>
                <input type="number" name="tahun" id="tahun" class="form-control" value="{{ date('Y') }}">
            </div>
            <div class="col-md-3">
                <label for="bulan" class="form-label">Bulan</label>
                <select name="bulan" id="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label for="kelas" class="form-label">Kelas</label>
                <select name="kelas" id="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasList as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
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

        <div class="shadow p-3 mb-3 table-responsive rounded">
            <table class="table table-bordered yajra-datatable">
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
                <tbody></tbody>
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
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script> <!-- Kalau pakai axios -->
    <script>
        const sppCtx = document.getElementById('sppChart').getContext('2d');
        const sppChart = new Chart(sppCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                        label: 'Total Tagihan',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Pembayaran',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                indexAxis: 'x',
                scales: {
                    y: {
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
                                return context.dataset.label + ': Rp ' +
                                    new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                }
            }
        });

        // Ambil data chart dari backend
        function fetchChartData(tahun, bulan, kelas = null) {
            axios.get('/chart-bulanan', {
                    params: {
                        tahun: tahun, // contoh tahun
                        kelas: kelas // contoh kelas
                    }
                })
                .then(response => {
                    const data = response.data;
                    sppChart.data.labels = data.labels;
                    sppChart.data.datasets[0].data = data.tagihan;
                    sppChart.data.datasets[1].data = data.pembayaran;
                    sppChart.update();
                    console.log(response.data);
                })
                .catch(error => {
                    console.error('Error fetching chart data:', error);
                });
        }

        // Contoh panggil dengan tahun ini dan tanpa filter kelas
        fetchChartData(new Date().getFullYear());
    </script>
    <script>
        $(document).ready(function() {
            const table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('tagihan-spp.data') }}",
                    data: function(d) {
                        d.tahun = $('#tahun').val();
                        d.bulan = $('#bulan').val();
                        d.kelas = $('#kelas').val();
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'kelas',
                        name: 'kelas'
                    },
                    {
                        data: 'total_tagihan',
                        name: 'total_tagihan',
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
                    },
                    {
                        data: 'total_bayar',
                        name: 'total_bayar',
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data, type, row) {
                            if (data === 'lunas')
                                return '<span class="badge bg-success">Lunas</span>';
                            if (data === 'belum_lunas')
                                return '<span class="badge bg-warning text-dark">Belum Lunas</span>';
                            return '<span class="badge bg-secondary">Belum Ada Tagihan</span>';
                        }
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        render: function(data, type, row) {
                            return `<a href="/tagihan-spp/${data}" class="btn btn-sm btn-info">Lihat Detail</a>`;
                        },
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // 🔁 Jalankan ulang DataTables saat tombol filter diklik
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });
        });

        function number_format(number, decimals = 0, dec_point = ',', thousands_sep = '.') {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                s = '',
                toFixedFix = function(n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }
    </script>
@endpush

@extends('layouts.app')

@section('content')
    <style>
        .btn-outline-success,
        .btn-outline-warning {
            transition: all 0.2s ease-in-out;
        }

        #tunai:not(:checked)+.btn-outline-success:hover {
            background-color: #198754;
            /* Bootstrap success */
            color: #fff;
            border-color: #198754;
            box-shadow: 0 0 0.3rem rgba(25, 135, 84, 0.4);
        }

        #transfer:not(:checked)+.btn-outline-warning:hover {
            background-color: #ffc107;
            /* Bootstrap warning */
            color: #fff;
            border-color: #ffc107;
            box-shadow: 0 0 0.3rem rgba(255, 193, 7, 0.4);
        }

        #tunai:checked+.btn-outline-success {
            background-color: #198754;
            color: #fff;
            border-color: #198754;
            box-shadow: 0 0 0.4rem rgba(25, 135, 84, 0.5);
        }

        #transfer:checked+.btn-outline-warning {
            background-color: #ffc107;
            color: #fff;
            border-color: #ffc107;
            box-shadow: 0 0 0.4rem rgba(255, 193, 7, 0.5);
        }
    </style>

    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
        <h1 class="mb-4">Dashboard <strong>Pembayaran PMB Siswa</strong></h1>
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
                <form method="POST" action="{{ route('payment.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPembayaranLabel">Form Pembayaran PMB Murid</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="rfid_uid_input" class="form-label">Tempelkan ID Card Murid</label>
                            <input type="text" id="rfid_uid_input" name="rfid_uid" class="form-control text-center"
                                placeholder="Tempelkan Kartu RFID..." required autofocus>
                        </div>

                        <div id="student-card" class="mt-4 d-none">
                            <div id="student-card-body">
                                <!-- Konten siswa dimuat via JS -->
                            </div>
                        </div>

                        <div class="mb-3 mt-4">
                            <label for="jumlah" class="form-label">Jumlah Bayar</label>
                            <input type="text" id="formattedJumlah" class="form-control" oninput="formatInput(this)"
                                placeholder="Masukkan jumlah" required>
                            <input type="number" name="jumlah" id="jumlah" class="form-control d-none">
                        </div>

                        <div class="mb-3">
                            <label class="form-label mb-2">Metode Pembayaran</label>
                            <div class="d-flex gap-2" id="payment-method-buttons">
                                <input type="radio" class="btn-check" name="metode" id="tunai" value="tunai"
                                    autocomplete="off" required>
                                <label class="btn btn-outline-success" for="tunai">Tunai (Cash)</label>

                                <input type="radio" class="btn-check" name="metode" id="transfer" value="transfer"
                                    autocomplete="off" required>
                                <label class="btn btn-outline-warning" for="transfer">Transfer</label>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="submitBtn">Bayar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grafik Tren Pembayaran -->
        <div class="shadow p-3 mb-3">
            <canvas id="paymentChart" height="100"></canvas>
        </div>

        <div class="shadow p-3 mb-3 table-responsive rounded">
            <table class="table table-bordered yajra-datatable">
                <thead>
                    <tr>
                        <th>Nama Murid</th>
                        <th>Total Dibayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $index => $student)
                        <tr>
                            <td>{{ $student->name }}</td>
                            <td>Rp {{ number_format($student->total_bayar, 0, ',', '.') }}</td>
                            <td>
                                <a href="{{ route('payment.show', $student->id) }}" class="btn btn-sm btn-info">Lihat
                                    Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data pembayaran</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalPembayaran');
            const rfidInput = document.getElementById('rfid_uid_input');

            if (modal && rfidInput) {
                modal.addEventListener('shown.bs.modal', function() {
                    rfidInput.focus();
                });
            }
        });

        const rfidInput = document.getElementById('rfid_uid_input');
        const studentCard = document.getElementById('student-card');
        const studentCardBody = document.getElementById('student-card-body');
        const submitBtn = document.querySelector('#submitBtn');
        const jumlahInput = document.querySelector('#formattedJumlah');

        rfidInput?.addEventListener('input', function() {
            const uid = this.value.trim();

            if (!uid) {
                studentCard.classList.add('d-none');
                studentCardBody.innerHTML = '';
                return;
            }

            studentCard.classList.remove('d-none');
            studentCardBody.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary me-2" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span class="text-muted">Sedang mencari data siswa...</span>
            </div>
        `;

            clearTimeout(window.fetchTimeout);
            window.fetchTimeout = setTimeout(() => {
                fetch(`/api/student-by-rfid/${uid}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.name) {
                            const isLunas = Number(data.sisa) <= 0;
                            studentCardBody.innerHTML = `
                <div class="alert ${isLunas ? 'alert-success' : 'alert-warning'}">
                    ${isLunas ? '✅ Semua tagihan sudah lunas.' : '⚠️ Masih ada tanggungan yang belum dibayar.'}
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" value="${data.name}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kelas</label>
                    <input type="text" class="form-control" value="${data.edu_class} (${data.tahun_ajaran})" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Biaya</label>
                    <input type="text" class="form-control" value="${Number(data.total_biaya).toLocaleString()}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sisa Tanggungan</label>
                    <input type="text" class="form-control" value="${Number(data.sisa).toLocaleString()}" readonly>
                </div>
                <input type="hidden" name="student_id" value="${data.id}">
            `;

                            // Nonaktifkan jika lunas
                            submitBtn.disabled = isLunas;
                            document.getElementById('jumlah').disabled = isLunas;
                            document.getElementById('formattedJumlah').disabled = isLunas;
                            document.getElementById('tunai').disabled = isLunas;
                            document.getElementById('transfer').disabled = isLunas;

                            document.getElementById('jumlah').value = isLunas ? '' : data.sisa;

                        } else {
                            setTimeout(() => {
                                if (rfidInput.value.trim() === uid) {
                                    studentCardBody.innerHTML =
                                        `<p class="text-danger">❌ Siswa tidak ditemukan!</p>`;
                                    document.querySelector('button[type="submit"]').disabled =
                                        true;
                                    document.getElementById('jumlah').disabled = true;
                                    document.getElementById('formattedJumlah').disabled = true;
                                    document.getElementById('jumlah').value = '';
                                }
                            }, 1000);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        studentCardBody.innerHTML =
                            `<p class="text-danger">⚠️ Terjadi kesalahan saat mengambil data.</p>`;
                    });
            }, 300);
        });

        const ctx = document.getElementById('paymentChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($labels) !!},
                datasets: [{
                    label: 'Total Pembayaran per Bulan',
                    data: {!! json_encode($values) !!},
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
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
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                }
            }
        });

        $(document).ready(function() {
            $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('payments.data') }}",
                columns: [{
                        data: 'name',
                        name: 'students.name'
                    },
                    {
                        data: 'total_bayar',
                        name: 'total_bayar',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return 'Rp ' + number_format(data);
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });

        function formatInput(input) {
            let rawValue = input.value.replace(/\D/g, ""); // Hanya angka
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);

            input.value = formatted; // Tampilkan angka dengan separator
            document.getElementById("jumlah").value = rawValue; // Simpan angka asli tanpa separator
        }

        function number_format(number, decimals = 0, dec_point = '.', thousands_sep = '.') {
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

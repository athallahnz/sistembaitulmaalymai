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
        <h1 class="mb-4">Dashboard <strong>Pembayaran SPP Siswa</strong></h1>

        <!-- Tombol Trigger Modal -->
        <div class="mb-3 d-flex gap-2 align-items-end">
            <form id="form-recognize-spp-bulk" action="{{ route('tagihan-spp.recognize.bulk') }}" method="POST"
                class="mb-0">
                @csrf
                <div class="d-flex gap-2 align-items-end">
                    <div>
                        <label for="bulk_bulan" class="form-label">Bulan</label>
                        <select name="bulan" id="bulk_bulan" class="form-select" required>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" @selected($i == now()->month)>
                                    {{ \Carbon\Carbon::createFromDate(null, $i, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label for="bulk_tahun" class="form-label">Tahun</label>
                        <input type="number" name="tahun" id="bulk_tahun" class="form-control"
                            value="{{ now()->year }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-recognize-spp-bulk">
                        Proses Pengakuan SPP Bulanan
                    </button>
                </div>
            </form>

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
                        <h5 class="modal-title" id="modalPembayaranLabel">Form Pembayaran SPP Murid</h5>
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

                        {{-- Pilihan periode tagihan (muncul kalau unpaid_items > 0) --}}
                        <div id="tagihan-picker" class="mt-3 d-none">
                            <label class="form-label">Pilih Pembayaran</label>

                            <select id="tagihan_mode" class="form-select">
                                <option value="" selected disabled>-- Pilih --</option>
                                <option value="single">Bayar 1 Bulan</option>
                                <option value="all">Bayar Semua Tagihan</option>
                            </select>

                            <div id="tagihan_single_wrap" class="mt-3 d-none">
                                <label class="form-label mb-1">Pilih Bulan</label>
                                <select id="tagihan_single" class="form-select">
                                    <option value="" selected disabled>-- Pilih bulan --</option>
                                </select>
                            </div>

                            {{-- Hidden input untuk dikirim ke backend --}}
                            <input type="hidden" name="payment_scope" id="payment_scope" value="">
                            <div id="tagihan_ids_wrap"></div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="jumlah" class="form-label">Jumlah Bayar</label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control"
                                placeholder="Jumlah otomatis dari tagihan" required readonly disabled>
                        </div>

                        <input type="hidden" name="student_id" id="student_id" value="">
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
                <input type="number" name="tahun" id="tahun" class="form-control" value="{{ $tahun }}">
            </div>
            <div class="col-md-3">
                <label for="bulan" class="form-label">Bulan</label>
                <select name="bulan" id="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ (int) $bulan === $i ? 'selected' : '' }}>
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
                        <option value="{{ $kelas->id }}" {{ (int) $kelasId === (int) $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100" type="submit" id="filterSubmitBtn">Tampilkan</button>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        /* =========================================================
         * MERGED SCRIPT (RFID + MODAL PAYMENT + CHART + CRUD + DATATABLE)
         * Replace seluruh isi <script> lama dengan ini.
         * ========================================================= */

        document.addEventListener('DOMContentLoaded', function() {
            // =========================
            // RFID / MODAL PEMBAYARAN
            // =========================
            const modal = document.getElementById('modalPembayaran');
            const rfidInput = document.getElementById('rfid_uid_input');

            const studentCard = document.getElementById('student-card');
            const studentCardBody = document.getElementById('student-card-body');

            const jumlahInput = document.getElementById('jumlah');
            const studentIdInput = document.getElementById('student_id');
            const submitBtn = document.getElementById('submitBtn');

            const tagihanPicker = document.getElementById('tagihan-picker');
            const tagihanMode = document.getElementById('tagihan_mode');
            const tagihanSingleWrap = document.getElementById('tagihan_single_wrap');
            const tagihanSingle = document.getElementById('tagihan_single');

            const paymentScope = document.getElementById('payment_scope');
            const tagihanIdsWrap = document.getElementById('tagihan_ids_wrap');

            const form = document.getElementById('form-recognize-spp-bulk');
            const btn = document.getElementById('btn-recognize-spp-bulk');

            let unpaidItems = [];

            function resetFormAfterScan() {
                unpaidItems = [];

                if (studentCard) studentCard.classList.add('d-none');
                if (studentCardBody) studentCardBody.innerHTML = '';

                if (tagihanPicker) tagihanPicker.classList.add('d-none');
                if (tagihanMode) tagihanMode.value = '';
                if (tagihanSingleWrap) tagihanSingleWrap.classList.add('d-none');
                if (tagihanSingle) tagihanSingle.innerHTML =
                    '<option value="" selected disabled>-- Pilih bulan --</option>';

                if (paymentScope) paymentScope.value = '';
                if (tagihanIdsWrap) tagihanIdsWrap.innerHTML = '';

                if (jumlahInput) {
                    jumlahInput.value = '';
                    jumlahInput.disabled = true;
                }

                if (studentIdInput) studentIdInput.value = '';
                if (submitBtn) submitBtn.disabled = true;
            }

            function enablePaymentUI() {
                const metodeChecked = document.querySelector('input[name="metode"]:checked');
                const ok = !!(studentIdInput && studentIdInput.value && paymentScope && paymentScope.value &&
                    metodeChecked && jumlahInput && !jumlahInput.disabled && Number(jumlahInput.value) > 0);
                if (submitBtn) submitBtn.disabled = !ok;
            }

            function renderLoading() {
                if (!studentCard || !studentCardBody) return;
                studentCard.classList.remove('d-none');
                studentCardBody.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border text-primary me-2" role="status" style="width: 1.5rem; height: 1.5rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-muted">Mencari data tagihan siswa...</span>
                </div>
            `;
            }

            function renderNoTagihan(data) {
                if (!studentCardBody) return;
                studentCardBody.innerHTML = `
                <div class="alert alert-danger">Saat ini belum ada tagihan yang belum lunas.</div>
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" value="${data.name ?? '-'}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kelas</label>
                    <input type="text" class="form-control" value="${data.edu_class ?? '-'}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Tagihan</label>
                    <input type="text" class="form-control" value="0" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sisa Tagihan</label>
                    <input type="text" class="form-control" value="0" readonly>
                </div>
            `;

                // kunci pembayaran
                if (studentIdInput) studentIdInput.value = '';
                if (jumlahInput) {
                    jumlahInput.value = '';
                    jumlahInput.disabled = true;
                }
                if (submitBtn) submitBtn.disabled = true;
                if (tagihanPicker) tagihanPicker.classList.add('d-none');
                if (paymentScope) paymentScope.value = '';
                if (tagihanIdsWrap) tagihanIdsWrap.innerHTML = '';
            }

            function renderStudentCard(data) {
                if (!studentCard || !studentCardBody) return;

                // kompatibel: kalau backend belum mengirim total_unpaid, fallback ke sisa/total
                const totalUnpaid = Number(
                    (data.total_unpaid != null) ? data.total_unpaid :
                    (data.sisa != null) ? data.sisa :
                    (data.total != null) ? data.total : 0
                );

                studentCardBody.innerHTML = `
                <div class="alert alert-warning">⚠️ Tagihan belum lunas, silahkan melakukan pelunasan.</div>
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" value="${data.name ?? '-'}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kelas</label>
                    <input type="text" class="form-control" value="${data.edu_class ?? '-'}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Belum Lunas</label>
                    <input type="text" class="form-control" value="${totalUnpaid.toLocaleString('id-ID')}" readonly>
                </div>
            `;
                studentCard.classList.remove('d-none');
            }

            function setHiddenTagihanIds(ids) {
                if (!tagihanIdsWrap) return;
                tagihanIdsWrap.innerHTML = '';
                ids.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tagihan_ids[]';
                    input.value = id;
                    tagihanIdsWrap.appendChild(input);
                });
            }

            function applySingle(tagihanId) {
                const t = unpaidItems.find(x => String(x.id) === String(tagihanId));
                if (!t) return;

                if (paymentScope) paymentScope.value = 'single';
                setHiddenTagihanIds([t.id]);

                if (jumlahInput) {
                    jumlahInput.disabled = false;
                    jumlahInput.readOnly = true; // aman
                    jumlahInput.value = Number(t.jumlah);
                }
                enablePaymentUI();
            }

            function applyAll() {
                if (paymentScope) paymentScope.value = 'all';
                const ids = unpaidItems.map(x => x.id);
                setHiddenTagihanIds(ids);

                const total = unpaidItems.reduce((acc, x) => acc + Number(x.jumlah), 0);
                if (jumlahInput) {
                    jumlahInput.disabled = false;
                    jumlahInput.readOnly = true; // aman
                    jumlahInput.value = total;
                }
                enablePaymentUI();
            }

            function renderTagihanPicker(data) {
                if (!tagihanPicker || !tagihanMode || !tagihanSingleWrap || !tagihanSingle) return;

                unpaidItems = Array.isArray(data.unpaid_items) ? data.unpaid_items : [];

                if (unpaidItems.length === 0) {
                    tagihanPicker.classList.add('d-none');
                    return;
                }

                tagihanPicker.classList.remove('d-none');

                tagihanSingle.innerHTML = '<option value="" selected disabled>-- Pilih bulan --</option>';
                unpaidItems.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;

                    // fallback label kalau backend belum menyediakan
                    const label = t.label ?? ((t.bulan_label && t.tahun) ? `${t.bulan_label} ${t.tahun}` :
                        (t.bulan && t.tahun) ? `${String(t.bulan).padStart(2,'0')}/${t.tahun}` :
                        `Tagihan #${t.id}`);

                    opt.textContent = `${label} (Rp ${Number(t.jumlah).toLocaleString('id-ID')})`;
                    tagihanSingle.appendChild(opt);
                });

                if (unpaidItems.length === 1) {
                    tagihanMode.value = 'single';
                    tagihanSingleWrap.classList.remove('d-none');
                    tagihanSingle.value = unpaidItems[0].id;
                    applySingle(unpaidItems[0].id);
                } else {
                    tagihanMode.value = '';
                    tagihanSingleWrap.classList.add('d-none');

                    // reset pembayaran sampai user pilih mode
                    if (paymentScope) paymentScope.value = '';
                    if (tagihanIdsWrap) tagihanIdsWrap.innerHTML = '';
                    if (jumlahInput) {
                        jumlahInput.value = '';
                        jumlahInput.disabled = true;
                    }
                    enablePaymentUI();
                }
            }

            async function fetchByRfid(uid) {
                const url = '/api/spp-tagihan-by-rfid/' + encodeURIComponent(uid) + '?t=' + Date.now();
                const res = await fetch(url);
                if (!res.ok) return null;
                return await res.json();
            }

            // binding metode bayar
            document.querySelectorAll('input[name="metode"]').forEach(r => {
                r.addEventListener('change', enablePaymentUI);
            });

            // modal: focus & reset
            if (modal && rfidInput) {
                modal.addEventListener('shown.bs.modal', () => rfidInput.focus());

                modal.addEventListener('hidden.bs.modal', () => {
                    // clear checked metode
                    document.querySelectorAll('input[name="metode"]').forEach(r => r.checked = false);
                    if (rfidInput) rfidInput.value = '';
                    resetFormAfterScan();
                });
            }

            // cegah enter submit
            // RFID: langsung fetch saat scan (debounce)
            if (rfidInput) {
                // tetap cegah Enter submit form
                rfidInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') e.preventDefault();
                });

                let scanTimer = null;
                let lastUid = '';

                rfidInput.addEventListener('input', function() {
                    const uid = this.value.trim();

                    // jika kosong, reset tampilan
                    if (!uid) {
                        resetFormAfterScan();
                        return;
                    }

                    // optimisasi: kalau UID sama persis, tidak usah fetch ulang
                    if (uid === lastUid) return;

                    // loading cepat langsung muncul
                    renderLoading();

                    clearTimeout(scanTimer);
                    scanTimer = setTimeout(async () => {
                        // kunci: set lastUid di sini supaya tidak spam request
                        lastUid = uid;

                        // reset state dulu (tapi jangan sembunyikan card karena kita lagi loading)
                        unpaidItems = [];
                        if (tagihanPicker) tagihanPicker.classList.add('d-none');
                        if (tagihanMode) tagihanMode.value = '';
                        if (tagihanSingleWrap) tagihanSingleWrap.classList.add('d-none');
                        if (tagihanSingle) tagihanSingle.innerHTML =
                            '<option value="" selected disabled>-- Pilih bulan --</option>';
                        if (paymentScope) paymentScope.value = '';
                        if (tagihanIdsWrap) tagihanIdsWrap.innerHTML = '';
                        if (jumlahInput) {
                            jumlahInput.value = '';
                            jumlahInput.disabled = true;
                        }
                        if (studentIdInput) studentIdInput.value = '';
                        if (submitBtn) submitBtn.disabled = true;

                        const data = await fetchByRfid(uid);

                        if (!data || !data.name) {
                            if (studentCard && studentCardBody) {
                                studentCard.classList.remove('d-none');
                                studentCardBody.innerHTML =
                                    `<p class="text-danger mb-0">❌ Siswa tidak ditemukan!</p>`;
                            }
                            // reset total state tapi biarkan lastUid supaya tidak loop
                            if (studentIdInput) studentIdInput.value = '';
                            if (jumlahInput) {
                                jumlahInput.value = '';
                                jumlahInput.disabled = true;
                            }
                            if (submitBtn) submitBtn.disabled = true;
                            return;
                        }

                        // set student_id
                        if (studentIdInput) studentIdInput.value = data.id;

                        // fallback backend lama
                        const legacyNoTagihan = (data.tagihan_count === 0 || Number(data
                            .total) === 0);
                        const hasNewItems = Array.isArray(data.unpaid_items) && data
                            .unpaid_items.length > 0;

                        if (!hasNewItems && legacyNoTagihan) {
                            renderNoTagihan(data);
                            return;
                        }

                        // render
                        renderStudentCard(data);
                        renderTagihanPicker(data);

                        enablePaymentUI();
                    }, 250); // 200-300ms ideal untuk scanner
                });

                // Jika user manual hapus/ubah UID, reset lastUid supaya bisa fetch lagi
                rfidInput.addEventListener('focus', () => {
                    // tidak reset value; hanya reset marker supaya scan ulang tetap jalan
                    lastUid = '';
                });
            }

            // mode change
            if (tagihanMode) {
                tagihanMode.addEventListener('change', function() {
                    if (this.value === 'single') {
                        tagihanSingleWrap.classList.remove('d-none');

                        if (paymentScope) paymentScope.value = '';
                        if (tagihanIdsWrap) tagihanIdsWrap.innerHTML = '';
                        if (jumlahInput) {
                            jumlahInput.value = '';
                            jumlahInput.disabled = true;
                        }
                    } else if (this.value === 'all') {
                        tagihanSingleWrap.classList.add('d-none');
                        applyAll();
                    }
                    enablePaymentUI();
                });
            }

            if (tagihanSingle) {
                tagihanSingle.addEventListener('change', function() {
                    applySingle(this.value);
                });
            }

            // init
            resetFormAfterScan();


            // ================= CHART =================
            const sppCanvas = document.getElementById('sppChart');
            if (sppCanvas) {
                const sppCtx = sppCanvas.getContext('2d');
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
                                        return ' ' + new Intl.NumberFormat('id-ID').format(value);
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ':  ' +
                                            new Intl.NumberFormat('id-ID').format(context.raw);
                                    }
                                }
                            }
                        }
                    }
                });

                // 🔥 Ambil data chart pakai nilai form
                window.fetchChartData = function() {
                    const tahun = $('#tahun').val() || new Date().getFullYear();
                    const kelas = $('#kelas').val() || null;

                    axios.get("{{ route('tagihan-spp.chart-bulanan') }}", {
                            params: {
                                tahun: tahun,
                                kelas: kelas
                            }
                        })
                        .then(response => {
                            const data = response.data;
                            sppChart.data.labels = data.labels;
                            sppChart.data.datasets[0].data = data.tagihan;
                            sppChart.data.datasets[1].data = data.pembayaran;
                            sppChart.update();
                        })
                        .catch(error => {
                            console.error('Error fetching chart data:', error);
                        });
                };
            }

            // ================= UTIL =================
            function csrfToken() {
                return $('meta[name="csrf-token"]').attr('content');
            }

            function reloadTableAndChart() {
                $('.yajra-datatable').DataTable().ajax.reload(null, false);
                if (window.fetchChartData) window.fetchChartData();
            }

            // Delete periode (wajib tahun+bulan)
            $(document).on('click', '.btn-delete-periode', function() {
                const url = $(this).data('url');
                const name = $(this).data('name');
                const tahun = $('#tahun').val();
                const bulan = $('#bulan').val();

                if (!tahun || !bulan) {
                    return Swal.fire({
                        icon: 'warning',
                        title: 'Periode belum dipilih',
                        text: 'Silakan pilih tahun dan bulan terlebih dahulu.',
                    });
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Tagihan Periode?',
                    html: `
                    <p>Anda akan menghapus tagihan:</p>
                    <strong>${name}</strong><br>
                    <small>Periode ${bulan}-${tahun}</small>
                `,
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            tahun,
                            bulan
                        },
                        headers: {
                            'X-CSRF-TOKEN': csrfToken()
                        },
                        success: (res) => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            reloadTableAndChart();
                        },
                        error: (xhr) => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ??
                                    'Gagal menghapus tagihan periode.'
                            });
                        }
                    });
                });
            });

            $(document).on('click', '.btn-delete-student', function() {
                const url = $(this).data('url');
                const name = $(this).data('name');

                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Semua Tagihan?',
                    html: `
                    <p>Tindakan ini akan menghapus <strong>SEMUA tagihan</strong> untuk:</p>
                    <strong>${name}</strong>
                    <hr>
                    <p class="text-danger mb-1">
                        Ketik <strong>HAPUS</strong> untuk melanjutkan
                    </p>
                `,
                    input: 'text',
                    inputPlaceholder: 'Ketik HAPUS',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Hapus Permanen',
                    cancelButtonText: 'Batal',
                    preConfirm: (value) => {
                        if (value !== 'HAPUS') {
                            Swal.showValidationMessage('Ketik HAPUS dengan benar');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken()
                        },
                        success: (res) => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: res.message,
                                timer: 1600,
                                showConfirmButton: false
                            });
                            reloadTableAndChart();
                        },
                        error: (xhr) => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ??
                                    'Gagal menghapus tagihan siswa.'
                            });
                        }
                    });
                });
            });

            if (form && btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Konfirmasi Pengakuan Pendapatan',
                        html: `
                <div class="text-start">
                    <p>Anda akan memproses <strong>pengakuan pendapatan SPP BULK</strong>.</p>
                    <ul>
                        <li>Bulan & tahun yang dipilih akan diproses</li>
                        <li>Hanya tagihan dengan status <strong>LUNAS</strong></li>
                        <li>Transaksi <strong>tidak dapat dibatalkan</strong></li>
                    </ul>
                    <p class="text-danger fw-semibold mt-2">
                        Pastikan data sudah benar.
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
                            btn.innerHTML =
                                '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
                            form.submit();
                        }
                    });
                });
            }

            // ================ DATATABLES + FILTER ================
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
                            render: function(data) {
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
                            orderable: false,
                            searchable: false
                        }
                    ]
                });

                // Saat form filter di-submit → reload tabel & chart
                $('#filterForm').on('submit', function(e) {
                    e.preventDefault();
                    table.ajax.reload();
                    if (window.fetchChartData) window.fetchChartData();
                });

                // Panggil pertama kali pakai nilai default form
                if (window.fetchChartData) window.fetchChartData();
            });
        });
    </script>
@endpush

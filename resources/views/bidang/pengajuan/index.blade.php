@extends('layouts.app')

@section('title', 'Daftar Pengajuan Dana')

@section('content')
    <div class="container py-4">
        {{-- ===== Header ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <h1 class="mb-1">Pengajuan <strong>Dana</strong></h1>
                <div class="small text-muted">Manajemen Anggaran & Pencairan Dana</div>
            </div>

            <div class="d-flex gap-2">
                {{-- Tombol Trigger Modal --}}
                @if (auth()->user()->role == 'Bidang')
                    <button type="button" class="btn btn-primary shadow mt-3" data-bs-toggle="modal"
                        data-bs-target="#modalCreatePengajuan">
                        <i class="bi bi-plus-circle"></i> Buat Pengajuan Baru
                    </button>
                @endif
            </div>
        </div>

        {{-- ===== Summary Cards ===== --}}
        <div class="row g-3 mb-4">
            {{-- 1. Menunggu Verifikasi --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-warning">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Menunggu</div>
                            <h2 class="mb-0 fw-bold text-warning">{{ $summary->menunggu }}</h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-hourglass-split text-warning fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Disetujui --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-primary">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Disetujui</div>
                            <h2 class="mb-0 fw-bold text-primary">{{ $summary->disetujui }}</h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-check-lg text-primary fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Dicairkan --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-success">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Dicairkan</div>
                            <h2 class="mb-0 fw-bold text-success">{{ $summary->dicairkan }}</h2>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-cash-stack text-success fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Ditolak --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 border-start border-4 border-danger">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Ditolak</div>
                            <h2 class="mb-0 fw-bold text-danger">{{ $summary->ditolak }}</h2>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-x-lg text-danger fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Tabel Data ===== --}}
        <div class="card shadow border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pengajuan-table" class="table table-striped table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Pembuat</th>
                                <th>Judul Pengajuan</th>
                                <th class="text-end">Total Dana (Rp)</th>
                                <th class="text-center">Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Include Modal Partial --}}
    @include('bidang.pengajuan._modal_add_pengajuan')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // 1. DataTables
            $('#pengajuan-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('pengajuan.index') }}",
                columns: [{
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'user_name', // Ganti dari user_name ke pembuat.name
                        name: 'pembuat.name',
                        defaultContent: '-'
                    },
                    {
                        data: 'judul',
                        name: 'judul'
                    },
                    {
                        data: 'total_jumlah',
                        name: 'total_jumlah',
                        className: 'text-end'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            // ==========================================
            // 2. LOGIC MODAL (Dynamic Rows & CRUD)
            // ==========================================

            const tbody = document.getElementById('tbody-items');
            const grandTotalDisplay = document.getElementById('grand-total-display');
            const btnAddRow = document.getElementById('btn-add-row');
            const formPengajuan = document.getElementById('form-pengajuan');
            const modalEl = document.getElementById('modalCreatePengajuan');
            const modalTitle = modalEl.querySelector('.modal-title'); // Tambahkan selector judul

            let rowIdx = 1;

            // Variabel Global yang berisi opsi SELECT Akun Keuangan (DIAMBIL DARI PHP)
            // Ini agar bisa diakses di fungsi Tambah Baris dan di Modal Edit
            const listAkun = @json($akunKeuangans);
            let optionsAkun = '<option value="">-- Pilih Akun --</option>';
            listAkun.forEach(function(akun) {
                optionsAkun += `<option value="${akun.id}">${akun.nama_akun}</option>`;
            });


            // Helper Format Rupiah
            const formatRupiah = (number) => {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(number);
            }

            // Fungsi Kalkulasi Total
            const calculateTotal = () => {
                let grandTotal = 0;
                if (tbody) {
                    tbody.querySelectorAll('.item-row').forEach(row => {
                        // Pastikan input-harga dibaca sebagai float
                        const qty = parseFloat(row.querySelector('.input-qty').value) || 0;
                        const harga = parseFloat(row.querySelector('.input-harga').value) || 0;
                        const subtotal = qty * harga;

                        // Update tampilan subtotal per baris (dengan format, tapi nilai dihitung dari float)
                        const subtotalInput = row.querySelector('.input-subtotal');
                        if (subtotalInput) {
                            subtotalInput.value = subtotal.toLocaleString('id-ID');
                        }
                        grandTotal += subtotal;
                    });
                }
                if (grandTotalDisplay) {
                    grandTotalDisplay.innerText = formatRupiah(grandTotal);
                }
            };

            // Event Listener Input Qty/Harga (Delegation)
            if (tbody) {
                tbody.addEventListener('input', function(e) {
                    if (e.target.classList.contains('input-qty') || e.target.classList.contains(
                            'input-harga')) {
                        calculateTotal();
                    }
                });

                // Event Listener Hapus Baris
                tbody.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-remove')) {
                        const row = e.target.closest('tr');
                        // ... (Logic SweetAlert Hapus tetap sama) ...
                        if (tbody.querySelectorAll('.item-row').length > 1) {
                            Swal.fire({
                                title: 'Hapus baris ini?',
                                text: "Item akan dihapus dari daftar.",
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'Ya, Hapus!',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    row.remove();
                                    calculateTotal();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Gagal',
                                text: 'Minimal harus ada satu item pengajuan.',
                            });
                        }
                    }
                });
            }

            // Event Listener Tambah Baris
            if (btnAddRow) {
                btnAddRow.addEventListener('click', function() {
                    const html = `
                        <tr class="item-row">
                            <td>
                                <select name="details[${rowIdx}][akun_keuangan_id]" class="form-select form-select-sm" required>
                                    ${optionsAkun}
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[${rowIdx}][keterangan_item]" class="form-control form-select-sm" required>
                            </td>
                            <td>
                                <input type="number" step="1" name="details[${rowIdx}][kuantitas]" class="form-control form-select-sm text-center input-qty" value="1" required>
                            </td>
                            <td>
                                <input type="number" name="details[${rowIdx}][harga_pokok]" class="form-control form-select-sm text-end input-harga" placeholder="0" required>
                            </td>
                            <td>
                                <input type="text" class="form-control form-select-sm text-end input-subtotal bg-light fw-bold" value="0" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>`;

                    tbody.insertAdjacentHTML('beforeend', html);
                    rowIdx++;
                });
            }

            // ==========================================
            // LOGIKA HANDLE MODAL CREATE & EDIT
            // ==========================================

            // Event listener saat Modal dibuka
            modalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Tombol yang men-trigger modal
                const pengajuanId = button ? button.getAttribute('data-id') : null;

                // Clear any previous method override input
                formPengajuan.querySelector('input[name="_method"]')?.remove();

                // --- MODE EDIT ---
                if (pengajuanId) {
                    modalTitle.innerHTML = `<i class="bi bi-pencil-square"></i> Edit Pengajuan Dana`;

                    // 1. Atur Form Action ke route UPDATE
                    const updateUrl = `/pengajuan/${pengajuanId}`; // Asumsi path: /pengajuan/{id}
                    formPengajuan.setAttribute('action', updateUrl);

                    // 2. Tambahkan input METHOD PUT
                    const methodInput = document.createElement('input');
                    methodInput.setAttribute('type', 'hidden');
                    methodInput.setAttribute('name', '_method');
                    methodInput.setAttribute('value', 'PUT');
                    formPengajuan.appendChild(methodInput);

                    // 3. Ambil data via AJAX
                    // Gunakan ID 0 sebagai placeholder di Blade, dan ganti di JavaScript
                    const baseAjaxUrl = "{{ route('pengajuan.json', ['id' => 0]) }}";
                    const ajaxUrl = baseAjaxUrl.replace('/0', '/' +
                        pengajuanId); // Ganti /0 dengan ID yang sebenarnya

                    $.ajax({
                        // Gunakan URL yang sudah di-generate dan diperbaiki
                        url: ajaxUrl,
                        method: 'GET',
                        success: function(data) {
                            // PASTIKAN: Update Form Action juga menggunakan logika yang sama
                            const baseUpdateUrl =
                            "{{ route('pengajuan.update', ['id' => 0]) }}";
                            const updateUrl = baseUpdateUrl.replace('/0', '/' + pengajuanId);
                            formPengajuan.setAttribute('action', updateUrl);

                            // Isi data header
                            formPengajuan.querySelector('input[name="judul"]').value = data
                                .judul;
                            formPengajuan.querySelector('textarea[name="deskripsi"]').value =
                                data.deskripsi || '';

                            // Isi detail items
                            const tbody = document.getElementById('tbody-items');
                            tbody.innerHTML = ''; // Kosongkan baris default

                            data.details.forEach((item, index) => {
                                // Ganti dengan logic item-by-item
                                const html = `
                                    <tr class="item-row">
                                        <td><select name="details[${index}][akun_keuangan_id]" class="form-select form-select-sm" required>${optionsAkun}</select></td>
                                        <td><input type="text" name="details[${index}][keterangan_item]" class="form-control form-select-sm" required value="${item.keterangan_item}"></td>
                                        <td><input type="number" step="1" name="details[${index}][kuantitas]" class="form-control form-select-sm text-center input-qty" value="${item.kuantitas}" required></td>
                                        <td><input type="number" step="1000" name="details[${index}][harga_pokok]" class="form-control form-select-sm text-end input-harga" placeholder="0" required value="${item.harga_pokok}"></td>
                                        <td><input type="text" class="form-control form-select-sm text-end input-subtotal bg-light fw-bold" value="${item.jumlah_dana}" readonly></td>
                                        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove"><i class="bi bi-trash"></i></button></td>
                                    </tr>`;
                                tbody.insertAdjacentHTML('beforeend', html);

                                // Set nilai SELECT (membutuhkan DOM manipulation setelah insert HTML)
                                tbody.querySelector(
                                    `select[name="details[${index}][akun_keuangan_id]"]`
                                ).value = item.akun_keuangan_id;
                            });

                            rowIdx = data.details.length; // Update index untuk baris baru
                            calculateTotal();
                        },
                        error: function(xhr) {
                            // Tampilkan status error
                            console.error("AJAX Error Status:", xhr.status);
                            console.error("AJAX Error Response:", xhr.responseText);

                            // Tampilkan status error di SweetAlert
                            Swal.fire('Error', 'Gagal memuat data pengajuan. Status: ' + xhr
                                .status, 'error');

                            // KOREKSI INI: Sembunyikan modal menggunakan instance Bootstrap
                            // Pastikan library Bootstrap JavaScript ter-load!
                            const modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }
                    });

                } else {
                    // --- MODE CREATE ---
                    modalTitle.innerHTML = `<i class="bi bi-file-earmark-plus"></i> Form Pengajuan Dana`;
                    formPengajuan.setAttribute('action', '{{ route('pengajuan.store') }}');

                    // Logic reset form dipindahkan ke 'hidden.bs.modal'
                }
            });

            // Event listener saat Modal DITUTUP (Reset Form)
            modalEl.addEventListener('hidden.bs.modal', function() {
                if (formPengajuan) formPengajuan.reset();

                // Hapus semua baris dinamis
                const rows = tbody.querySelectorAll('.item-row');
                for (let i = 0; i < rows.length; i++) {
                    rows[i].remove();
                }

                // Tambahkan kembali baris default CREATE (index 0)
                const defaultHtml = `
                    <tr class="item-row">
                        <td><select name="details[0][akun_keuangan_id]" class="form-select form-select-sm" required>${optionsAkun}</select></td>
                        <td><input type="text" name="details[0][keterangan_item]" class="form-control form-select-sm" required></td>
                        <td><input type="number" step="1" name="details[0][kuantitas]" class="form-control form-select-sm text-center input-qty" value="1" required></td>
                        <td><input type="number" step="1000" name="details[0][harga_pokok]" class="form-control form-select-sm text-end input-harga" placeholder="0" required></td>
                        <td><input type="text" class="form-control form-select-sm text-end input-subtotal bg-light fw-bold" value="0" readonly></td>
                        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove"><i class="bi bi-trash"></i></button></td>
                    </tr>`;
                tbody.insertAdjacentHTML('beforeend', defaultHtml);

                if (grandTotalDisplay) grandTotalDisplay.innerText = formatRupiah(0);
                rowIdx = 1; // Reset counter
            });
        });
    </script>
@endpush

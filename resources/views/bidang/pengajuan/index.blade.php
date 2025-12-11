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
                    <button type="button" class="btn shadow mt-3"
                        style="background-color: #8B4513; color: white; border-color: #8B4513;" data-bs-toggle="modal"
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
                            <h2 class="mb-0 fw-bold" style="color: #8B4513;">{{ $summary->menunggu }}</h2>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px; background-color: #D2B48C;">
                            <i class="bi bi-hourglass-split fs-2" style="color: #8B4513;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Disetujui --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 border-start border-4" style="border-color: #A0522D;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Disetujui</div>
                            <h2 class="mb-0 fw-bold" style="color: #A0522D;">{{ $summary->disetujui }}</h2>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px; background-color: #DEB887;">
                            <i class="bi bi-check-lg fs-2" style="color: #A0522D;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Dicairkan --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 border-start border-4" style="border-color: #CD853F;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Dicairkan</div>
                            <h2 class="mb-0 fw-bold" style="color: #CD853F;">{{ $summary->dicairkan }}</h2>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px; background-color: #F5DEB3;">
                            <i class="bi bi-cash-stack fs-2" style="color: #CD853F;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Ditolak --}}
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100 border-start border-4" style="border-color: #704214;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Ditolak</div>
                            <h2 class="mb-0 fw-bold" style="color: #704214;">{{ $summary->ditolak }}</h2>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px; background-color: #C9B8A3;">
                            <i class="bi bi-x-lg fs-2" style="color: #704214;"></i>
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
            // ==========================================
            // 1. DATATABLES
            // ==========================================
            $('#pengajuan-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('pengajuan.index') }}",
                columns: [{
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'user_name',
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
            // 2. VARIABEL GLOBAL FORM PENGAJUAN
            // ==========================================
            const tbody = document.getElementById('tbody-items');
            const grandTotalDisplay = document.getElementById('grand-total-display');
            const btnAddRow = document.getElementById('btn-add-row');
            const formPengajuan = document.getElementById('form-pengajuan');
            const modalEl = document.getElementById('modalCreatePengajuan');
            const modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;

            let rowIdx = 1;

            // List Akun dari backend (Blade → JS)
            const listAkun = @json($akunKeuangans);
            let optionsAkun = '<option value="">-- Pilih Akun --</option>';
            listAkun.forEach(function(akun) {
                optionsAkun += `<option value="${akun.id}">${akun.nama_akun}</option>`;
            });

            // ==========================================
            // 3. HELPER FORMAT & PERHITUNGAN TOTAL
            // ==========================================
            const formatRupiah = (number) => {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(number);
            };

            const calculateTotal = () => {
                let grandTotal = 0;

                if (tbody) {
                    tbody.querySelectorAll('.item-row').forEach(row => {
                        const qty = parseFloat(row.querySelector('.input-qty').value) || 0;
                        const harga = parseFloat(row.querySelector('.input-harga').value) || 0;
                        const subtotal = qty * harga;

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

            // ==========================================
            // 4. EVENT DI TABEL ITEM (QTY/HARGA & HAPUS BARIS)
            // ==========================================
            if (tbody) {
                // Hitung ulang jika qty/harga berubah
                tbody.addEventListener('input', function(e) {
                    if (e.target.classList.contains('input-qty') ||
                        e.target.classList.contains('input-harga')) {
                        calculateTotal();
                    }
                });

                // Hapus baris item
                tbody.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-remove')) {
                        const row = e.target.closest('tr');

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

            // ==========================================
            // 5. TAMBAH BARIS ITEM BARU
            // ==========================================
            if (btnAddRow) {
                btnAddRow.addEventListener('click', function() {
                    const html = `
                    <tr class="item-row">
                        <td>
                            <select name="details[${rowIdx}][akun_keuangan_id]"
                                    class="form-select form-select-sm" required>
                                ${optionsAkun}
                            </select>
                        </td>
                        <td>
                            <input type="text"
                                   name="details[${rowIdx}][keterangan_item]"
                                   class="form-control form-select-sm" required>
                        </td>
                        <td>
                            <input type="number"
                                   step="1"
                                   min="0"
                                   name="details[${rowIdx}][kuantitas]"
                                   class="form-control form-select-sm text-center input-qty"
                                   value="1" required>
                        </td>
                        <td>
                            <input type="number"
                                   min="0"
                                   name="details[${rowIdx}][harga_pokok]"
                                   class="form-control form-select-sm text-end input-harga"
                                   placeholder="0" required>
                        </td>
                        <td>
                            <input type="text"
                                   class="form-control form-select-sm text-end input-subtotal bg-light fw-bold"
                                   value="0" readonly>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm border-0 btn-remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;

                    tbody.insertAdjacentHTML('beforeend', html);
                    rowIdx++;
                });
            }

            // ==========================================
            // 6. BLOKIR ANGKA NEGATIF PADA QTY & HARGA
            // ==========================================
            // Blokir ketikan tanda minus
            $(document).on('keydown', '.input-qty, .input-harga', function(e) {
                if (e.key === '-' || e.keyCode === 189) {
                    e.preventDefault();
                }
            });

            // Jika somehow nilainya sudah negatif (paste dll) → paksa jadi 0
            $(document).on('input', '.input-qty, .input-harga', function() {
                let val = parseFloat($(this).val());
                if (isNaN(val) || val < 0) {
                    $(this).val(0);
                }
            });

            // ==========================================
            // 7. LOGIKA MODAL CREATE & EDIT
            // ==========================================
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const pengajuanId = button ? button.getAttribute('data-id') : null;

                    // Bersihkan input _method sebelumnya (jika ada)
                    formPengajuan.querySelector('input[name="_method"]')?.remove();

                    // MODE EDIT
                    if (pengajuanId) {
                        if (modalTitle) {
                            modalTitle.innerHTML =
                            `<i class="bi bi-pencil-square"></i> Edit Pengajuan Dana`;
                        }

                        // Action update (pakai route helper dengan placeholder 0)
                        const baseUpdateUrl = "{{ route('pengajuan.update', ['id' => 0]) }}";
                        const updateUrl = baseUpdateUrl.replace('/0', '/' + pengajuanId);
                        formPengajuan.setAttribute('action', updateUrl);

                        // Tambah _method PUT
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'PUT';
                        formPengajuan.appendChild(methodInput);

                        // Ambil data JSON pengajuan
                        const baseAjaxUrl = "{{ route('pengajuan.json', ['id' => 0]) }}";
                        const ajaxUrl = baseAjaxUrl.replace('/0', '/' + pengajuanId);

                        $.ajax({
                            url: ajaxUrl,
                            method: 'GET',
                            success: function(data) {
                                // Header
                                formPengajuan.querySelector('input[name="judul"]').value = data
                                    .judul;
                                formPengajuan.querySelector('textarea[name="deskripsi"]')
                                    .value = data.deskripsi || '';

                                // Detail items
                                tbody.innerHTML = '';
                                data.details.forEach((item, index) => {
                                    const html = `
                                    <tr class="item-row">
                                        <td>
                                            <select name="details[${index}][akun_keuangan_id]"
                                                    class="form-select form-select-sm" required>
                                                ${optionsAkun}
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text"
                                                    name="details[${index}][keterangan_item]"
                                                    class="form-control form-select-sm"
                                                    required
                                                    value="${item.keterangan_item}">
                                        </td>
                                        <td>
                                            <input type="number"
                                                    step="1"
                                                    min="0"
                                                    name="details[${index}][kuantitas]"
                                                    class="form-control form-select-sm text-center input-qty"
                                                    value="${item.kuantitas}"
                                                    required>
                                        </td>
                                        <td>
                                            <input type="number"
                                                    min="0"
                                                    step="1000"
                                                    name="details[${index}][harga_pokok]"
                                                    class="form-control form-select-sm text-end input-harga"
                                                    placeholder="0"
                                                    value="${item.harga_pokok}"
                                                    required>
                                        </td>
                                        <td>
                                            <input type="text"
                                                    class="form-control form-select-sm text-end input-subtotal bg-light fw-bold"
                                                    value="${item.jumlah_dana}"
                                                    readonly>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm border-0 btn-remove">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>`;

                                    tbody.insertAdjacentHTML('beforeend', html);

                                    // Set selected CoA
                                    tbody
                                        .querySelector(
                                            `select[name="details[${index}][akun_keuangan_id]"]`
                                            )
                                        .value = item.akun_keuangan_id;
                                });

                                rowIdx = data.details.length;
                                calculateTotal();
                            },
                            error: function(xhr) {
                                console.error("AJAX Error Status:", xhr.status);
                                console.error("AJAX Error Response:", xhr.responseText);

                                Swal.fire(
                                    'Error',
                                    'Gagal memuat data pengajuan. Status: ' + xhr.status,
                                    'error'
                                );

                                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                            }
                        });

                    } else {
                        // MODE CREATE
                        if (modalTitle) {
                            modalTitle.innerHTML =
                                `<i class="bi bi-file-earmark-plus"></i> Form Pengajuan Dana`;
                        }
                        formPengajuan.setAttribute('action', '{{ route('pengajuan.store') }}');
                        // Reset form dan baris akan di-handle di event hidden.bs.modal
                    }
                });

                // Saat modal ditutup → reset form & baris item
                modalEl.addEventListener('hidden.bs.modal', function() {
                    if (formPengajuan) {
                        formPengajuan.reset();
                    }

                    // Hapus semua baris item
                    tbody.innerHTML = '';

                    // Tambah kembali baris default
                    const defaultHtml = `
                    <tr class="item-row">
                        <td>
                            <select name="details[0][akun_keuangan_id]"
                                    class="form-select form-select-sm" required>
                                ${optionsAkun}
                            </select>
                        </td>
                        <td>
                            <input type="text"
                                   name="details[0][keterangan_item]"
                                   class="form-control form-select-sm" required>
                        </td>
                        <td>
                            <input type="number"
                                   step="1"
                                   min="0"
                                   name="details[0][kuantitas]"
                                   class="form-control form-select-sm text-center input-qty"
                                   value="1" required>
                        </td>
                        <td>
                            <input type="number"
                                   min="0"
                                   step="1000"
                                   name="details[0][harga_pokok]"
                                   class="form-control form-select-sm text-end input-harga"
                                   placeholder="0" required>
                        </td>
                        <td>
                            <input type="text"
                                    class="form-control form-select-sm text-end input-subtotal bg-light fw-bold"
                                    value="0" readonly>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm border-0 btn-remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                    tbody.insertAdjacentHTML('beforeend', defaultHtml);

                    if (grandTotalDisplay) {
                        grandTotalDisplay.innerText = formatRupiah(0);
                    }
                    rowIdx = 1;
                });
            }

            // ==========================================
            // 8. BADGE JUMLAH APPROVAL (HEADER)
            // ==========================================
            const approvalBadge = $('#approval-badge');

            function fetchApprovalCount() {
                $.ajax({
                    url: "{{ route('pengajuan.api.approval.count') }}",
                    method: 'GET',
                    success: function(response) {
                        const count = response.count || 0;
                        if (count > 0) {
                            approvalBadge.text(count).show();
                        } else {
                            approvalBadge.hide();
                        }
                    },
                    error: function(xhr) {
                        console.error("Gagal mengambil jumlah approval.", xhr.status);
                        approvalBadge.hide();
                    }
                });
            }

            fetchApprovalCount();
            setInterval(fetchApprovalCount, 60000);

            // ==========================================
            // 9. SWEETALERT DELETE PENGAJUAN (ROW UTAMA)
            // ==========================================
            $(document).on('click', '.btn-delete-pengajuan', function(e) {
                e.preventDefault();

                const button = $(this);
                const url = button.data('url');

                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Data pengajuan yang dihapus tidak dapat dikembalikan.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'Berhasil',
                                    text: response.message ||
                                        'Pengajuan berhasil dihapus.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                if ($.fn.DataTable.isDataTable('#pengajuan-table')) {
                                    $('#pengajuan-table').DataTable().ajax.reload(null,
                                        false);
                                }
                            },
                            error: function(xhr) {
                                let msg = 'Terjadi kesalahan saat menghapus data.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    title: 'Gagal',
                                    text: msg,
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush

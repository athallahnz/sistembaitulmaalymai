@extends('layouts.app')

@section('content')
    <div class="container">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a>Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tagihan SPP Murid</li>
                    </ol>
                </nav>
                <h1 class="mb-2">Tagihan <strong>SPP</strong></h1>
                <div class="text-muted small mt-1">Buat tagihan, export Excel, dan kelola history per kelas.</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('tagihan-spp.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            </div>
        </div>

        @php
            $currentYear = now()->year;
            // range tahun untuk dropdown (silakan sesuaikan)
            $years = range($currentYear - 2, $currentYear + 10);

            $bulanList = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];
        @endphp

        {{-- ROW 1: CREATE + EXPORT (sejajar di desktop) --}}
        <div class="row g-3 g-md-4">

            {{-- CREATE --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 rounded-4" id="cardCreate">
                    <div class="card-body p-3 p-md-4">

                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold">Buat Tagihan</div>
                                <div class="text-muted small">Buat tagihan SPP untuk siswa berdasarkan kelas dan periode.
                                </div>
                            </div>
                            <span class="badge text-bg-primary">Create</span>
                        </div>

                        <form method="POST" action="{{ route('tagihan-spp.store') }}" class="needs-validation" novalidate>
                            @csrf

                            {{-- Pilih Kelas --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label mb-0 fw-semibold">Pilih Kelas</label>

                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" id="selectAllCreate">
                                        <label class="form-check-label small" for="selectAllCreate">Pilih Semua</label>
                                    </div>
                                </div>

                                <div class="border rounded-3 p-2 p-md-3" style="max-height: 240px; overflow:auto;">
                                    <div class="row g-2">
                                        @foreach ($classes as $class)
                                            <div class="col-12 col-sm-6">
                                                <label
                                                    class="d-flex align-items-start gap-2 p-2 rounded-3 border bg-white hover-shadow-sm"
                                                    style="cursor:pointer;">
                                                    <input class="form-check-input mt-1 create-check" type="checkbox"
                                                        name="edu_class_ids[]" value="{{ $class->id }}"
                                                        id="class-{{ $class->id }}">
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold small">{{ $class->name }}</div>
                                                        <div class="text-muted small">TA: {{ $class->tahun_ajaran }}</div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="text-muted small mt-1">Gunakan “Pilih Semua” jika tagihan berlaku untuk semua
                                    kelas yang ditampilkan.</div>
                            </div>

                            {{-- Tahun / Bulan --}}
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Tahun</label>
                                    <select name="tahun" class="form-select" required>
                                        <option value="" selected disabled>Pilih Tahun</option>
                                        @foreach ($years as $y)
                                            <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>
                                                {{ $y }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Tahun wajib dipilih.</div>
                                </div>

                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Bulan</label>
                                    <select name="bulan" class="form-select" required>
                                        <option value="" selected disabled>Pilih Bulan</option>
                                        @foreach ($bulanList as $num => $label)
                                            <option value="{{ $num }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Bulan wajib dipilih.</div>
                                </div>
                            </div>

                            {{-- Nominal / Tanggal --}}
                            <div class="row g-2 mt-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Jumlah Tagihan / Siswa</label>
                                    <input type="text" id="formattedJumlah" class="form-control" inputmode="numeric"
                                        oninput="formatInput(this)" placeholder="Masukkan nominal..." required>
                                    <input type="number" name="jumlah" id="jumlah" class="form-control d-none">
                                    <div class="form-text">Nominal disimpan tanpa pemisah titik.</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold">Tanggal Aktif</label>
                                    <input type="date" name="tanggal_aktif" required class="form-control">
                                    <div class="invalid-feedback">Tanggal aktif wajib diisi.</div>
                                </div>
                            </div>

                            <div class="d-grid d-md-flex justify-content-md-end mt-3">
                                <button type="submit" class="btn btn-primary btn-lg px-md-4">
                                    <i class="bi bi-rocket-takeoff me-1"></i> Buat Tagihan
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            {{-- EXPORT --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 rounded-4" id="cardExport">
                    <div class="card-body p-3 p-md-4">

                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold">Export Excel</div>
                                <div class="text-muted small">Unduh tagihan berdasarkan kelas dan periode.</div>
                            </div>
                            <span class="badge text-bg-success">Excel</span>
                        </div>

                        <form action="{{ route('tagihan-spp.export') }}" method="GET">

                            {{-- Pilih Kelas --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label mb-0 fw-semibold">Pilih Kelas</label>

                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" id="selectAllExport">
                                        <label class="form-check-label small" for="selectAllExport">Pilih Semua</label>
                                    </div>
                                </div>

                                <div class="border rounded-3 p-2 p-md-3" style="max-height: 240px; overflow:auto;">
                                    <div class="row g-2">
                                        @foreach ($classes as $class)
                                            <div class="col-12 col-sm-6">
                                                <label
                                                    class="d-flex align-items-start gap-2 p-2 rounded-3 border bg-white hover-shadow-sm"
                                                    style="cursor:pointer;">
                                                    <input class="form-check-input mt-1 export-check" type="checkbox"
                                                        name="edu_class_ids[]" value="{{ $class->id }}"
                                                        id="export-class-{{ $class->id }}">
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold small">{{ $class->name }}</div>
                                                        <div class="text-muted small">TA: {{ $class->tahun_ajaran }}</div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="text-muted small mt-1">Export akan membuat file Excel untuk kelas terpilih.
                                </div>
                            </div>

                            {{-- Tahun/Bulan --}}
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Tahun</label>
                                    <select name="tahun" class="form-select" required>
                                        <option value="" selected disabled>Pilih Tahun</option>
                                        @foreach ($years as $y)
                                            <option value="{{ $y }}"
                                                {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Bulan</label>
                                    <select name="bulan" class="form-select" required>
                                        <option value="" selected disabled>Pilih Bulan</option>
                                        @foreach ($bulanList as $num => $label)
                                            <option value="{{ $num }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-grid mt-3">
                                <button class="btn btn-success btn-lg">
                                    <i class="bi bi-download me-1"></i> Export Excel
                                </button>
                            </div>

                            <div class="text-muted small mt-2">
                                Pastikan kelas dan periode sudah sesuai sebelum export.
                            </div>

                        </form>

                    </div>
                </div>
            </div>

        </div>

        {{-- ROW 2: HISTORY FULL WIDTH --}}
        <div class="row g-3 g-md-4 mt-0 mt-md-1">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4" id="cardHistory">
                    <div class="card-body p-3 p-md-4">

                        <div
                            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                            <div>
                                <div class="fw-semibold">History Tagihan</div>
                                <div class="text-muted small">Tab per kelas. Di desktop pakai DataTables, di mobile pakai
                                    card list.</div>
                            </div>
                        </div>

                        {{-- Filter history --}}
                        <div class="p-2 p-md-3 border rounded-3 bg-light mb-3">
                            <div class="row g-2 align-items-end">
                                @php
                                    // Tahun range dinamis (ubah sesuai kebutuhan)
                                    $currentYear = now()->year;
                                    $years = range($currentYear - 2, $currentYear + 10);

                                    $bulanList = [
                                        1 => 'Januari',
                                        2 => 'Februari',
                                        3 => 'Maret',
                                        4 => 'April',
                                        5 => 'Mei',
                                        6 => 'Juni',
                                        7 => 'Juli',
                                        8 => 'Agustus',
                                        9 => 'September',
                                        10 => 'Oktober',
                                        11 => 'November',
                                        12 => 'Desember',
                                    ];
                                @endphp

                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Tahun</label>
                                    <select id="fTahun" class="form-select form-select-sm">
                                        <option value="">Semua</option>
                                        @foreach ($years as $y)
                                            <option value="{{ $y }}"
                                                {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1">Bulan</label>
                                    <select id="fBulan" class="form-select form-select-sm">
                                        <option value="">Semua</option>
                                        @foreach ($bulanList as $num => $label)
                                            <option value="{{ $num }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label small mb-1">Status (child)</label>
                                    <select id="fStatus" class="form-select form-select-sm">
                                        <option value="">Semua</option>
                                        <option value="belum_lunas">Belum Lunas</option>
                                        <option value="lunas">Lunas</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-5 d-grid d-md-flex gap-2 justify-content-md-end">
                                    <button id="btnApplyFilter" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-funnel"></i> Terapkan
                                    </button>
                                    <button id="btnResetFilter" class="btn btn-outline-secondary btn-sm">Reset</button>
                                </div>
                            </div>
                        </div>

                        {{-- Tabs per kelas (scroll horizontal di mobile) --}}
                        <div class="overflow-auto">
                            <ul class="nav nav-tabs flex-nowrap" role="tablist" style="white-space:nowrap;">
                                @foreach ($classes as $i => $k)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $i === 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                            data-bs-target="#tab-{{ $k->id }}" type="button" role="tab">
                                            {{ $k->name }} <span
                                                class="text-muted small">({{ $k->tahun_ajaran }})</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="tab-content border border-top-0 p-2 p-md-3 rounded-bottom-4">
                            @foreach ($classes as $i => $k)
                                <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}"
                                    id="tab-{{ $k->id }}" role="tabpanel">

                                    <div
                                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                                        <div class="text-muted small">
                                            Kelas: <strong>{{ $k->name }}</strong> | TA:
                                            <strong>{{ $k->tahun_ajaran }}</strong>
                                        </div>

                                        <div class="d-grid d-md-block">
                                            <button class="btn btn-outline-primary btn-sm btn-bulk-edit"
                                                data-kelas-id="{{ $k->id }}"
                                                data-kelas-label="{{ e($k->name . ' (' . $k->tahun_ajaran . ')') }}">
                                                <i class="bi bi-pencil-square me-1"></i> Edit Massal Periode
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm btn-delete-kelas-periode"
                                                data-kelas-id="{{ $k->id }}"
                                                data-kelas-label="{{ e($k->name . ' (' . $k->tahun_ajaran . ')') }}">
                                                <i class="bi bi-trash3 me-1"></i> Hapus Periode Kelas
                                            </button>
                                        </div>
                                    </div>

                                    {{-- DESKTOP: DataTables --}}
                                    <div class="d-none d-md-block">
                                        <div class="table-responsive">
                                            <table class="table table-striped align-middle w-100 dt-students mb-0"
                                                id="studentsTable-{{ $k->id }}"
                                                data-kelas-id="{{ $k->id }}">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 140px;">Aksi</th>
                                                        <th>Nama Siswa</th>
                                                        <th>Kelas</th>
                                                        <th>Total Tagihan</th>
                                                        <th>Total Lunas</th>
                                                        <th>Total Belum Lunas</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- MOBILE: Card List --}}
                                    <div class="d-md-none">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="text-muted small">Tampilan mobile: daftar siswa (card).</div>
                                            <button class="btn btn-outline-secondary btn-sm btn-refresh-mobile"
                                                data-kelas-id="{{ $k->id }}">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>

                                        <div id="mobileCards-{{ $k->id }}" class="d-flex flex-column gap-2">
                                            <div class="text-center text-muted small py-3">Memuat data...</div>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Modal Bulk Edit --}}
    <div class="modal fade" id="bulkEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Massal Tagihan (Periode) - <span id="bulkKelasLabel"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="bulkEduClassId">

                    <div class="alert alert-warning small mb-3">
                        Bulk edit akan mengubah/menambahkan tagihan untuk <strong>1 periode (bulan/tahun)</strong> pada
                        seluruh siswa di kelas.
                        Rekomendasi: gunakan scope <strong>Belum Lunas Saja</strong> agar tagihan lunas tidak ikut berubah.
                    </div>

                    <div class="row g-2 g-md-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold">Tahun</label>
                            <input type="number" class="form-control" id="bulkTahun" min="2000" max="2100"
                                required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold">Bulan</label>
                            <input type="number" class="form-control" id="bulkBulan" min="1" max="12"
                                required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold">Jumlah</label>
                            <input type="number" class="form-control" id="bulkJumlah" min="1000" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold">Tanggal Aktif</label>
                            <input type="date" class="form-control" id="bulkTanggalAktif" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Scope</label>
                            <select class="form-select" id="bulkScope">
                                <option value="belum_lunas_saja" selected>Belum Lunas Saja (disarankan)</option>
                                <option value="semua">Semua (termasuk lunas)</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" id="btnDoBulkEdit">
                        <i class="bi bi-save me-1"></i> Proses Bulk Edit
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // ================================
        // Basic UI helpers (Create/Export)
        // ================================
        (function() {
            const selectAllCreate = document.getElementById('selectAllCreate');
            if (selectAllCreate) {
                selectAllCreate.addEventListener('change', function() {
                    document.querySelectorAll('.create-check').forEach(cb => cb.checked = this.checked);
                });
            }

            const selectAllExport = document.getElementById('selectAllExport');
            if (selectAllExport) {
                selectAllExport.addEventListener('change', function() {
                    document.querySelectorAll('.export-check').forEach(cb => cb.checked = this.checked);
                });
            }

            window.formatInput = function(input) {
                let rawValue = String(input.value || '').replace(/\D/g, "");
                if (!rawValue) rawValue = "0";
                let formatted = new Intl.NumberFormat("id-ID").format(rawValue);
                input.value = formatted;
                const jumlahEl = document.getElementById("jumlah");
                if (jumlahEl) jumlahEl.value = rawValue;
            };

            // Bootstrap validation (optional)
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>

    <script>
        (function() {
            // ========= State =========
            const tables = {}; // { tableId: DataTable instance }
            const bulkModalEl = document.getElementById('bulkEditModal');
            const bulkModal = bulkModalEl ? new bootstrap.Modal(bulkModalEl) : null;

            function filters() {
                return {
                    tahun: document.getElementById('fTahun')?.value || '',
                    bulan: document.getElementById('fBulan')?.value || '',
                    status: document.getElementById('fStatus')?.value || '',
                };
            }

            function resetMobileChildCache() {
                document.querySelectorAll('[id^="mChildBody-"]').forEach(tb => {
                    tb.removeAttribute('data-loaded');
                });
            }

            // ===============================
            // DESKTOP: DataTables + child row
            // ===============================
            function childTableHtml(studentId) {
                return `
                    <div class="p-2 border rounded bg-white">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" id="child-${studentId}">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:110px;">Periode</th>
                                        <th>Jumlah</th>
                                        <th style="width:120px;">Status</th>
                                        <th style="width:140px;">Tanggal Aktif</th>
                                        <th style="width:90px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="5" class="text-center text-muted small">Memuat...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            async function loadChildItems(studentId) {
                const f = filters();
                const url = new URL(
                    `{{ url('/pendidikan/tagihan-spp/history/student') }}/${studentId}/items`,
                    window.location.origin
                );

                if (f.tahun) url.searchParams.set('tahun', f.tahun);
                if (f.bulan) url.searchParams.set('bulan', f.bulan);
                if (f.status) url.searchParams.set('status', f.status);

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json();

                const tbody = document.querySelector(`#child-${studentId} tbody`);
                if (!tbody) return;

                if (!json.items || json.items.length === 0) {
                    tbody.innerHTML =
                        `<tr><td colspan="5" class="text-center text-muted small">Tidak ada data.</td></tr>`;
                    return;
                }

                tbody.innerHTML = json.items.map(it => `
                    <tr>
                        <td>${it.periode}</td>
                        <td>${it.jumlah_label}</td>
                        <td>${it.status}</td>
                        <td>${it.tanggal_aktif ?? '-'}</td>
                        <td class="text-center">
                            ${
                                it.can_delete
                                    ? `<button class="btn btn-sm btn-outline-danger btn-delete-tagihan-item"
                                                                                                    data-url="${it.delete_url}" data-id="${it.id}">
                                                                                                <i class="bi bi-trash"></i>
                                                                                            </button>`
                                    : `<span class="text-muted small">-</span>`
                            }
                        </td>
                    </tr>
                `).join('');
            }

            function initStudentsTable(tableEl) {
                const kelasId = tableEl.getAttribute('data-kelas-id');
                const tableId = tableEl.getAttribute('id');

                if (!kelasId || !tableId) return;
                if (tables[tableId]) return; // prevent double init

                const dt = $('#' + tableId).DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50],
                    ajax: {
                        url: "{{ route('tagihan-spp.history.data') }}",
                        data: function(d) {
                            const f = filters();
                            d.kelas_id = kelasId;
                            d.tahun = f.tahun;
                            d.bulan = f.bulan;
                            d.status = f.status; // kalau endpoint Anda pakai status
                        }
                    },
                    columns: [{
                            data: 'aksi',
                            name: 'aksi',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'kelas',
                            name: 'kelas',
                            orderable: false
                        },
                        {
                            data: 'total_tagihan',
                            name: 'total_tagihan',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'total_lunas',
                            name: 'total_lunas',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'total_belum_lunas',
                            name: 'total_belum_lunas',
                            orderable: false,
                            searchable: false
                        },
                    ],
                    createdRow: function(row, data) {
                        $(row).attr('data-student-id', data.id);
                    },
                    rawColumns: ['aksi'],
                });

                tables[tableId] = dt;

                // toggle child row
                $('#' + tableId + ' tbody').on('click', '.btn-toggle-child', async function() {
                    const tr = $(this).closest('tr');
                    const row = dt.row(tr);
                    const studentId = $(tr).attr('data-student-id');

                    if (!studentId) return;

                    if (row.child.isShown()) {
                        row.child.hide();
                        $(this).html('<i class="bi bi-chevron-down"></i> Detail');
                        return;
                    }

                    row.child(childTableHtml(studentId)).show();
                    $(this).html('<i class="bi bi-chevron-up"></i> Tutup');

                    await loadChildItems(studentId);
                });
            }

            // init desktop tables
            document.querySelectorAll('.dt-students').forEach(initStudentsTable);

            // ===============================
            // MOBILE: Cards + collapse detail
            // ===============================
            function escapeHtml(str) {
                return String(str ?? '').replace(/[&<>"']/g, function(m) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    } [m]);
                });
            }

            async function loadMobileCards(kelasId) {
                const wrap = document.getElementById(`mobileCards-${kelasId}`);
                if (!wrap) return;

                wrap.innerHTML = `<div class="text-center text-muted small py-3">Memuat data...</div>`;

                const f = filters();
                const url = new URL(`{{ url('/pendidikan/tagihan-spp/history/mobile') }}`, window.location.origin);

                url.searchParams.set('kelas_id', kelasId);
                if (f.tahun) url.searchParams.set('tahun', f.tahun);
                if (f.bulan) url.searchParams.set('bulan', f.bulan);
                if (f.status) url.searchParams.set('status', f.status);

                try {
                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json?.message || 'Gagal memuat.');

                    const items = json.items || [];
                    if (items.length === 0) {
                        wrap.innerHTML = `<div class="text-center text-muted small py-3">Tidak ada siswa.</div>`;
                        return;
                    }

                    wrap.innerHTML = items.map(s => {
                        const sid = s.id;
                        const collapseId = `mCollapse-${kelasId}-${sid}`;
                        const tbodyId = `mChildBody-${kelasId}-${sid}`;

                        return `
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold">${escapeHtml(s.name)}</div>
                                            <div class="text-muted small">${escapeHtml(s.kelas)}</div>
                                        </div>

                                        <button class="btn btn-outline-primary btn-sm btn-mobile-toggle"
                                                data-student-id="${sid}"
                                                data-kelas-id="${kelasId}"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#${collapseId}">
                                            Detail
                                        </button>
                                    </div>

                                    <div class="row g-2 mt-2">
                                        <div class="col-4">
                                            <div class="text-muted small">Tagihan</div>
                                            <div class="fw-semibold small">${s.total_tagihan_label}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">Lunas</div>
                                            <div class="fw-semibold small">${s.total_lunas_label}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">Belum</div>
                                            <div class="fw-semibold small">${s.total_belum_lunas_label}</div>
                                        </div>
                                    </div>

                                    <div class="collapse mt-2" id="${collapseId}">
                                        <div class="border rounded-3 p-2 bg-light">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered bg-white mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width:110px;">Periode</th>
                                                            <th>Jumlah</th>
                                                            <th style="width:110px;">Status</th>
                                                            <th style="width:140px;">Aktif</th>
                                                            <th style="width:70px;">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="${tbodyId}">
                                                        <tr><td colspan="5" class="text-center text-muted small">Memuat...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        `;
                    }).join('');

                } catch (e) {
                    wrap.innerHTML =
                        `<div class="text-center text-danger small py-3">${escapeHtml(e.message)}</div>`;
                }
            }

            // ===============================
            // COMMON EVENTS
            // ===============================

            // delete tagihan item (desktop & mobile)
            $(document).on('click', '.btn-delete-tagihan-item', async function() {
                const url = $(this).data('url');
                if (!url) return;

                const confirmResult = await Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Tagihan?',
                    html: `
            <div class="text-start">
                <p class="mb-2">Anda akan menghapus <strong>1 item tagihan SPP</strong>.</p>
                <div class="alert alert-danger p-2 mb-0">
                    Dampak:
                    <ul class="mb-0 ps-3">
                        <li>Rollback <strong>jurnal/ledger</strong></li>
                        <li>Update <strong>piutang</strong></li>
                        <li>Update <strong>pendapatan belum diterima (PBD)</strong></li>
                    </ul>
                </div>
            </div>
        `,
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    focusCancel: true
                });

                if (!confirmResult.isConfirmed) return;

                try {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang menghapus tagihan dan melakukan rollback accounting',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        // 422 biasanya validasi bisnis (mis. sudah lunas)
                        throw new Error(json?.message || 'Gagal menghapus tagihan.');
                    }

                    // reload desktop DT
                    Object.values(tables).forEach(dt => dt.ajax.reload(null, false));

                    // reset + reload mobile tab aktif
                    resetMobileChildCache();
                    const active = document.querySelector('.tab-pane.active.show');
                    if (active) {
                        const kelasId = active.getAttribute('id')?.replace('tab-', '');
                        if (kelasId) loadMobileCards(kelasId);
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: json.message || 'Tagihan berhasil dihapus.',
                        timer: 1800,
                        showConfirmButton: false
                    });

                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.message || 'Terjadi kesalahan.'
                    });
                }
            });

            // mobile: load child once when collapse opened
            $(document).on('click', '.btn-mobile-toggle', async function() {
                const studentId = $(this).data('student-id');
                const kelasId = $(this).data('kelas-id');
                const tbodyId = `mChildBody-${kelasId}-${studentId}`;
                const tbody = document.getElementById(tbodyId);
                if (!tbody) return;

                if (tbody.getAttribute('data-loaded') === '1') return;

                const f = filters();
                const url = new URL(
                    `{{ url('/pendidikan/tagihan-spp/history/student') }}/${studentId}/items`, window
                    .location.origin);
                if (f.tahun) url.searchParams.set('tahun', f.tahun);
                if (f.bulan) url.searchParams.set('bulan', f.bulan);
                if (f.status) url.searchParams.set('status', f.status);

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json();

                if (!json.items || json.items.length === 0) {
                    tbody.innerHTML =
                        `<tr><td colspan="5" class="text-center text-muted small">Tidak ada data.</td></tr>`;
                    tbody.setAttribute('data-loaded', '1');
                    return;
                }

                tbody.innerHTML = json.items.map(it => `
                    <tr>
                        <td>${it.periode}</td>
                        <td>${it.jumlah_label}</td>
                        <td>${it.status}</td>
                        <td>${it.tanggal_aktif ?? '-'}</td>
                        <td class="text-center">
                            ${
                                it.can_delete
                                    ? `<button class="btn btn-sm btn-outline-danger btn-delete-tagihan-item"
                                                                                                    data-url="${it.delete_url}" data-id="${it.id}">
                                                                                                <i class="bi bi-trash"></i>
                                                                                           </button>`
                                    : `<span class="text-muted small">-</span>`
                            }
                        </td>
                    </tr>
                `).join('');

                tbody.setAttribute('data-loaded', '1');
            });

            // refresh mobile cards
            $(document).on('click', '.btn-refresh-mobile', function() {
                const kelasId = $(this).data('kelas-id');
                resetMobileChildCache();
                loadMobileCards(kelasId);
            });

            // apply filter
            document.getElementById('btnApplyFilter')?.addEventListener('click', function() {
                Object.values(tables).forEach(dt => dt.ajax.reload());
                resetMobileChildCache();

                ['fTahun', 'fBulan', 'fStatus'].forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('change', function() {
                        // sama seperti btnApplyFilter
                        Object.values(tables).forEach(dt => dt.ajax.reload());
                        resetMobileChildCache();

                        const active = document.querySelector('.tab-pane.active.show');
                        if (active) {
                            const kelasId = active.getAttribute('id')?.replace('tab-', '');
                            if (kelasId) loadMobileCards(kelasId);
                        }
                    });
                });

                const active = document.querySelector('.tab-pane.active.show');
                if (active) {
                    const kelasId = active.getAttribute('id')?.replace('tab-', '');
                    if (kelasId) loadMobileCards(kelasId);
                }
            });

            // reset filter
            document.getElementById('btnResetFilter')?.addEventListener('click', function() {
                document.getElementById('fTahun').value = '';
                document.getElementById('fBulan').value = '';
                document.getElementById('fStatus').value = '';

                Object.values(tables).forEach(dt => dt.ajax.reload());
                resetMobileChildCache();

                const active = document.querySelector('.tab-pane.active.show');
                if (active) {
                    const kelasId = active.getAttribute('id')?.replace('tab-', '');
                    if (kelasId) loadMobileCards(kelasId);
                }
            });

            // tab change: load mobile cards
            document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
                btn.addEventListener('shown.bs.tab', function(e) {
                    const target = e.target.getAttribute('data-bs-target');
                    if (!target) return;
                    const kelasId = target.replace('#tab-', '');
                    resetMobileChildCache();
                    loadMobileCards(kelasId);
                });
            });

            // initial mobile load
            document.addEventListener('DOMContentLoaded', function() {
                const first = document.querySelector('.tab-pane.show.active');
                if (first) {
                    const kelasId = first.getAttribute('id')?.replace('tab-', '');
                    if (kelasId) loadMobileCards(kelasId);
                }
            });

            // ===============================
            // BULK EDIT (modal)
            // ===============================
            $(document).on('click', '.btn-bulk-edit', function() {
                if (!bulkModal) return;

                const kelasId = $(this).data('kelas-id');
                const kelasLabel = $(this).data('kelas-label');

                $('#bulkEduClassId').val(kelasId);
                $('#bulkKelasLabel').text(kelasLabel);

                const f = filters();
                $('#bulkTahun').val(f.tahun || new Date().getFullYear());
                $('#bulkBulan').val(f.bulan || '');
                $('#bulkScope').val('belum_lunas_saja');
                $('#bulkJumlah').val('');
                $('#bulkTanggalAktif').val('');

                bulkModal.show();
            });

            document.getElementById('btnDoBulkEdit')?.addEventListener('click', async function() {
                const payload = {
                    edu_class_id: $('#bulkEduClassId').val(),
                    tahun: $('#bulkTahun').val(),
                    bulan: $('#bulkBulan').val(),
                    jumlah: $('#bulkJumlah').val(),
                    tanggal_aktif: $('#bulkTanggalAktif').val(),
                    scope: $('#bulkScope').val(),
                };

                if (!payload.edu_class_id || !payload.tahun || !payload.bulan || !payload.jumlah || !payload
                    .tanggal_aktif) {
                    alert('Lengkapi semua field bulk edit.');
                    return;
                }

                if (!confirm('Yakin memproses bulk edit untuk kelas ini?')) return;

                try {
                    const res = await fetch("{{ route('tagihan-spp.history.bulk-edit') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json();
                    if (!res.ok) throw new Error(json?.message || 'Bulk edit gagal.');

                    bulkModal.hide();

                    Object.values(tables).forEach(dt => dt.ajax.reload(null, false));
                    resetMobileChildCache();

                    const active = document.querySelector('.tab-pane.active.show');
                    if (active) {
                        const kelasId = active.getAttribute('id')?.replace('tab-', '');
                        if (kelasId) loadMobileCards(kelasId);
                    }

                    alert(json.message);
                } catch (e) {
                    alert(e.message);
                }
            });

            // delete kelas periode
            $(document).on('click', '.btn-delete-kelas-periode', async function() {
                const kelasId = $(this).data('kelas-id');
                const kelasLabel = $(this).data('kelas-label');

                const tahun = document.getElementById('fTahun')?.value;
                const bulan = document.getElementById('fBulan')?.value;

                if (!tahun || !bulan) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Filter belum lengkap',
                        text: 'Pilih Tahun dan Bulan pada filter terlebih dahulu.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                const confirmResult = await Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Tagihan Periode Kelas?',
                    html: `
            <div class="text-start">
                <p class="mb-2">Anda akan menghapus <strong>SEMUA tagihan</strong> periode:</p>
                <ul class="mb-2 ps-3">
                    <li><strong>Kelas:</strong> ${kelasLabel}</li>
                    <li><strong>Periode:</strong> ${bulan}/${tahun}</li>
                </ul>
                <div class="alert alert-danger p-2 mb-0">
                    Jika terdapat tagihan <strong>LUNAS</strong> pada periode ini,
                    proses akan <strong>DITOLAK</strong>.
                </div>
            </div>
        `,
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    focusCancel: true
                });

                if (!confirmResult.isConfirmed) return;

                const url = new URL(`{{ url('/tagihan-spp/kelas') }}/${kelasId}/periode`, window.location
                    .origin);
                url.searchParams.set('tahun', tahun);
                url.searchParams.set('bulan', bulan);

                try {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang menghapus tagihan periode dan rollback accounting',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const res = await fetch(url.toString(), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        throw new Error(json?.message || 'Gagal menghapus periode kelas.');
                    }

                    // reload desktop DT
                    Object.values(tables).forEach(dt => dt.ajax.reload());

                    // reset + reload mobile tab kelas tersebut
                    resetMobileChildCache();
                    loadMobileCards(String(kelasId));

                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: json.message || 'Periode kelas berhasil dihapus.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.message || 'Terjadi kesalahan.'
                    });
                }
            });

        })();
    </script>

    <style>
        /* memperhalus tap target */
        .btn,
        .form-control,
        .form-select {
            border-radius: .75rem;
        }

        .nav-tabs .nav-link {
            border-top-left-radius: .75rem;
            border-top-right-radius: .75rem;
        }

        .hover-shadow-sm:hover {
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .08);
        }
    </style>
@endpush

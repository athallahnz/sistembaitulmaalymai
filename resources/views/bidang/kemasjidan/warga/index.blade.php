@extends('layouts.app')

@section('title', 'Data Warga')

@section('content')
    <div class="container py-4">
        {{-- ===== Heading + Filter + Actions ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3">

            <div class="mb-2 mb-md-2">
                <h1 class="mb-1">Dashboard <strong>Data Warga</strong></h1>
                <div class="small text-muted">Manajemen Kepala Keluarga & Anggota</div>
            </div>

            <div class="d-flex flex-wrap align-items-end gap-2">

                {{-- Filter Pencarian --}}
                <form action="{{ route('kemasjidan.warga.index') }}" method="GET" class="d-flex gap-2 align-items-end">

                    <div class="input-group">
                        <span class="input-group-text">Cari</span>
                        <input type="text" name="q" class="form-control" placeholder="Nama / HP / RT / Alamat"
                            value="{{ $q }}">
                    </div>

                    <select name="jenis" class="form-select">
                        <option value="">Semua</option>
                        <option value="kk" {{ $filter === 'kk' ? 'selected' : '' }}>Kepala</option>
                        <option value="anggota" {{ $filter === 'anggota' ? 'selected' : '' }}>Anggota</option>
                    </select>

                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                {{-- Tombol Tambah (Modal) --}}
                <button type="button" class="btn btn-primary shadow" data-bs-toggle="modal"
                    data-bs-target="#modalCreateWarga">
                    <i class="bi bi-plus-circle"></i> Tambah Warga
                </button>

                {{-- Tombol Import Excel (Modal) --}}
                <button type="button" class="btn btn-success shadow d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#modalImportWarga">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                    Import Excel
                </button>

            </div>
        </div>

        {{-- ===== Alerts ===== --}}
        @if ($errors->any())
            {{-- error global (misal dari aksi store/update modal) --}}
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ===== Ringkasan ===== --}}
        <div class="row g-3 mb-3">

            {{-- Total Warga --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted mb-1">Total Warga</div>
                            <div class="display-6 fw-bold mb-0">
                                {{ number_format($ringkas['total_semua'] ?? 0) }}
                            </div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-people-fill" style="font-size:2rem; color:#9a9a9a;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kepala Keluarga --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted mb-1">Kepala Keluarga</div>
                            <div class="display-6 fw-bold mb-0">
                                {{ number_format($ringkas['total_kk'] ?? 0) }}
                            </div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-person-badge-fill" style="font-size:2rem; color:#9a9a9a;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Anggota Keluarga --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted mb-1">Anggota Keluarga</div>
                            <div class="display-6 fw-bold mb-0">
                                {{ number_format($ringkas['total_anggota'] ?? 0) }}
                            </div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-people" style="font-size:2rem; color:#9a9a9a;"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== Tabel Warga ===== --}}
        <div class="p-3 shadow table-responsive rounded glass">
            <table id="warga-table" class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>RT</th>
                        <th>No. Rmh</th>
                        <th>No. Whatsapp</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Kepala Keluarga</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- body diisi oleh DataTables via Ajax --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- =============== MODAL: CREATE WARGA =============== --}}
    <div class="modal fade" id="modalCreateWarga" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('wargas.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Warga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control" value="{{ old('nama') }}"
                                    placeholder="Masukkan Nama Warga Baru..." required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">RT <span class="text-danger">*</span></label>
                                <input type="text" name="rt" class="form-control" value="{{ old('rt') }}"
                                    placeholder="01" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">No. Rumah <span class="text-danger">*</span></label>
                                <input type="text" name="no" class="form-control" value="{{ old('no') }}"
                                    placeholder="001">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" name="hp" class="form-control" value="{{ old('hp') }}"
                                    placeholder="Masukkan No. Hp...">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                <input type="text" name="alamat" class="form-control" value="{{ old('alamat') }}"
                                    placeholder="Masukkan Alamat Warga...">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">PIN (opsional)</label>
                                <input type="text" name="pin" class="form-control" placeholder="Minimal 4 digit"
                                    placeholder="Masukkan PIN Baru Warga...">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label d-block">Status Keluarga</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="set_kepala"
                                        id="create_set_kepala_ya" value="1" checked>
                                    <label class="form-check-label" for="create_set_kepala_ya">
                                        Kepala Keluarga
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="set_kepala"
                                        id="create_set_kepala_tidak" value="0">
                                    <label class="form-check-label" for="create_set_kepala_tidak">
                                        Anggota Keluarga
                                    </label>
                                </div>

                                <div class="mt-2">
                                    <label class="form-label">Jika Anggota, Pilih Kepala Keluarga</label>
                                    <select name="warga_id" id="create_warga_id" class="form-select" disabled>
                                        <option value="">-- Pilih Kepala Keluarga --</option>
                                        @foreach ($kepalas as $kk)
                                            <option value="{{ $kk->id }}">
                                                {{ $kk->nama }} (RT {{ $kk->rt }}, No {{ $kk->no ?? '-' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Aktif saat memilih "Anggota Keluarga".
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Warga</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- =============== MODAL: EDIT WARGA =============== --}}
    <div class="modal fade" id="modalEditWarga" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="form-edit-warga" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Warga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="nama" id="edit_nama" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">RT <span class="text-danger">*</span></label>
                                <input type="text" name="rt" id="edit_rt" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">No Rumah</label>
                                <input type="text" name="no" id="edit_no" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">No HP</label>
                                <input type="text" name="hp" id="edit_hp" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat</label>
                                <input type="text" name="alamat" id="edit_alamat" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">PIN (opsional)</label>
                                <input type="text" name="pin" id="edit_pin" class="form-control"
                                    placeholder="Biarkan kosong jika tidak diubah">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label d-block">Status Keluarga</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="set_kepala"
                                        id="edit_set_kepala_ya" value="1">
                                    <label class="form-check-label" for="edit_set_kepala_ya">
                                        Kepala Keluarga
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="set_kepala"
                                        id="edit_set_kepala_tidak" value="0">
                                    <label class="form-check-label" for="edit_set_kepala_tidak">
                                        Anggota Keluarga
                                    </label>
                                </div>

                                <div class="mt-2">
                                    <label class="form-label">Jika Anggota, Pilih Kepala Keluarga</label>
                                    <select name="warga_id" id="edit_warga_id" class="form-select">
                                        <option value="">-- Pilih Kepala Keluarga --</option>
                                        @foreach ($kepalas as $kk)
                                            <option value="{{ $kk->id }}">
                                                {{ $kk->nama }} (RT {{ $kk->rt }}, No {{ $kk->no ?? '-' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Nonaktif otomatis bila status "Kepala Keluarga".
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Warga</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- =============== MODAL: IMPORT WARGA DARI EXCEL =============== --}}
    <div class="modal fade" id="modalImportWarga" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                {{-- === STATE 1: BELUM UPLOAD (UPLOAD FILE) === --}}
                @if (!isset($importPath) || !isset($sheetNames))
                    <form action="{{ route('kemasjidan.warga.import.preview') }}" method="POST"
                        enctype="multipart/form-data" id="form-import-warga">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title">
                                Import Data Warga dari Excel
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">

                            {{-- Step info --}}
                            <div class="mb-3 small">
                                <span class="badge text-bg-primary me-1">1</span> Upload file Excel
                                <span class="badge text-bg-secondary ms-2 me-1">2</span> Pilih Sheet
                                <span class="badge text-bg-secondary ms-2 me-1">3</span> Import & simpan
                            </div>

                            {{-- Drag & Drop Zone --}}
                            <div id="drop-zone" class="border border-2 border-dashed rounded-3 p-4 text-center mb-3"
                                style="border-style: dashed;">
                                <p class="mb-1 fw-semibold">Drop file Excel di sini</p>
                                <p class="small text-muted mb-2">
                                    atau klik tombol di bawah untuk memilih file.<br>
                                    Format yang didukung: <strong>.xlsx, .xls, .csv</strong>
                                </p>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.getElementById('file-import-warga').click()">
                                    Pilih File
                                </button>
                                <input type="file" name="file" id="file-import-warga" class="d-none"
                                    accept=".xlsx,.xls,.csv">
                                <div id="file-import-info" class="small text-muted mt-2"></div>
                            </div>

                            {{-- Info kolom yang dibutuhkan --}}
                            <div class="alert alert-info small mb-0">
                                <strong>Kolom minimal yang dibutuhkan:</strong><br>
                                <code>nama</code>, <code>rt</code>, <code>alamat</code>, <code>no</code>,
                                <code>hp</code><br>
                                Kolom lain di tabel <code>wargas</code>
                                (<code>pin</code>, <code>warga_id</code>, <code>infaq_sosial_id</code>)
                                boleh dikosongkan dan akan diisi <em>NULL</em> oleh sistem.
                                <br><br>
                                Setelah upload, sistem akan menampilkan preview data di modal ini, kamu bisa pilih sheet dan
                                cek apakah header sudah terbaca dengan benar.
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success" id="btn-import-submit" disabled>
                                Lanjutkan ke Preview
                            </button>
                        </div>
                    </form>

                    {{-- === STATE 2: SUDAH UPLOAD (PREVIEW + PILIH SHEET + IMPORT) === --}}
                @else
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Preview Import Data Warga
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">

                        {{-- Step info --}}
                        <div class="mb-3 small">
                            <span class="badge text-bg-success me-1">1</span> File sudah di-upload
                            <span class="badge text-bg-primary ms-2 me-1">2</span> Pilih Sheet
                            <span class="badge text-bg-secondary ms-2 me-1">3</span> Import & simpan
                        </div>

                        {{-- FORM PILIH SHEET (auto-submit saat ganti) --}}
                        <form action="{{ route('kemasjidan.warga.import.preview') }}" method="POST"
                            class="row g-3 mb-3 align-items-end">
                            @csrf
                            <input type="hidden" name="import_path" value="{{ $importPath }}">

                            <div class="col-md-4">
                                <label class="form-label">Sheet Aktif</label>
                                <select name="sheet" class="form-select" onchange="this.form.submit()">
                                    @foreach ($sheetNames as $idx => $name)
                                        <option value="{{ $idx }}" {{ $idx == $sheetIndex ? 'selected' : '' }}>
                                            {{ $idx + 1 }}. {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    Ganti sheet di sini, preview akan langsung menyesuaikan.
                                </small>
                            </div>

                            <div class="col-md-8">
                                <div class="alert alert-info small mb-0">
                                    Sistem otomatis mendeteksi kolom berdasarkan nama header. Jika header di Excel berbeda
                                    (mis.
                                    "<em>Nama Lengkap Jamaah</em>" tetap akan terbaca sebagai <code>nama</code>).
                                </div>
                            </div>
                        </form>

                        {{-- INFO: KOLOM DITEMUKAN / TIDAK --}}
                        <div class="mb-3">
                            <h6 class="mb-2">Deteksi Kolom</h6>
                            <ul class="list-unstyled small mb-0">
                                @php
                                    $displayFields = $targetFields ?? ['nama', 'rt', 'alamat', 'no', 'hp'];
                                @endphp
                                @foreach ($displayFields as $field)
                                    @php
                                        $col = $fieldColumns[$field] ?? null;
                                        $label = $fieldLabels[$field] ?? null;
                                    @endphp
                                    <li class="mb-1">
                                        <strong class="text-uppercase">{{ $field }}</strong>:
                                        @if ($col)
                                            <span class="badge text-bg-success">Ditemukan</span>
                                            <span class="text-muted">
                                                &mdash; Kolom <code>{{ $col }}</code>
                                                @if ($label)
                                                    (header: "<em>{{ $label }}</em>")
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge text-bg-danger">Tidak ditemukan</span>
                                            <span class="text-muted">
                                                &mdash; Pastikan ada kolom yang namanya mengandung
                                                "<code>{{ $field }}</code>" atau sejenisnya.
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- PREVIEW 10 BARIS --}}
                        <hr class="my-3">
                        <h6 class="mb-2">Preview Data (maks. 10 baris pertama)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        @if (!empty($previewRows))
                                            @foreach (array_keys($previewRows[0]) as $col)
                                                <th>{{ $col }}</th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($previewRows as $row)
                                        <tr>
                                            @foreach ($row as $val)
                                                <td>{{ $val }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="99" class="text-center text-muted">
                                                Tidak ada data untuk preview.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="small text-muted mt-2">
                            <strong>Catatan:</strong> Preview hanya menampilkan beberapa baris, tetapi proses import akan
                            membaca
                            semua baris data (kecuali header dan baris kosong).
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="{{ route('kemasjidan.warga.index') }}" class="btn btn-outline-secondary">
                            Batalkan Import
                        </a>

                        <form action="{{ route('kemasjidan.warga.import.commit') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="import_path" value="{{ $importPath }}">
                            <input type="hidden" name="sheet" value="{{ $sheetIndex }}">

                            <button type="submit" class="btn btn-success"
                                @if (empty($fieldColumns['nama']) || empty($fieldColumns['rt'])) disabled @endif>
                                <i class="bi bi-check-circle me-1"></i>
                                Import ke Data Warga
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- =============== MODAL: TANDAI MENINGGAL =============== --}}
    <div class="modal fade" id="modalWargaMeninggal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-warga-meninggal" method="POST" action="#">
                    @csrf
                    {{-- action akan di-set dinamis via JS --}}

                    <div class="modal-header">
                        <h5 class="modal-title">Tandai Warga Meninggal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="pengganti_id" id="pengganti-id-hidden">

                        <p class="mb-2">
                            Kepala keluarga <strong id="warga-meninggal-nama"></strong> akan ditandai
                            <span class="badge bg-danger">MENINGGAL</span>.
                        </p>

                        <div class="mb-3">
                            <label class="form-label">Pilih anggota sebagai kepala keluarga baru:</label>
                            <select name="pengganti_id" id="select-pengganti" class="form-select" required>
                                <option value="">Memuat daftar anggota...</option>
                            </select>
                            <div class="form-text">
                                Daftar ini berisi semua anggota keluarga yang terdaftar di bawah kepala ini.
                            </div>
                        </div>

                        <div class="alert alert-warning small mb-0">
                            Setelah disimpan:
                            <ul class="mb-0">
                                <li>Kepala lama ditandai <strong>meninggal</strong> dan tidak bisa login.</li>
                                <li>Kepala baru akan menjadi rujukan untuk iuran & infaq.</li>
                                <li>Seluruh anggota akan dipindah di bawah kepala baru.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button class="btn btn-warning" type="submit">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ============================================
            // 1. MODAL CREATE: toggle Kepala / Anggota
            // ============================================
            (function() {
                const createSetKepalaYa = document.getElementById('create_set_kepala_ya');
                const createSetKepalaTidak = document.getElementById('create_set_kepala_tidak');
                const createWargaSelect = document.getElementById('create_warga_id');

                if (!createSetKepalaYa || !createSetKepalaTidak || !createWargaSelect) return;

                function syncCreateKepala() {
                    if (createSetKepalaTidak.checked) {
                        createWargaSelect.removeAttribute('disabled');
                    } else {
                        createWargaSelect.value = '';
                        createWargaSelect.setAttribute('disabled', 'disabled');
                    }
                }

                createSetKepalaYa.addEventListener('change', syncCreateKepala);
                createSetKepalaTidak.addEventListener('change', syncCreateKepala);
                syncCreateKepala();
            })();


            // ============================================
            // 2. MODAL EDIT: prefill data dari tombol Edit
            // ============================================
            // ============================================
            // 2. MODAL EDIT: handle klik tombol .btn-edit-warga
            //    (pakai event delegation supaya aman dengan DataTables)
            // ============================================
            (function() {
                const editModalEl = document.getElementById('modalEditWarga');
                const formEdit = document.getElementById('form-edit-warga');
                const editNama = document.getElementById('edit_nama');
                const editRt = document.getElementById('edit_rt');
                const editNo = document.getElementById('edit_no');
                const editHp = document.getElementById('edit_hp');
                const editAlamat = document.getElementById('edit_alamat');
                const editPin = document.getElementById('edit_pin');
                const editSetKepalaYa = document.getElementById('edit_set_kepala_ya');
                const editSetKepalaTidak = document.getElementById('edit_set_kepala_tidak');
                const editWargaSelect = document.getElementById('edit_warga_id');

                if (!editModalEl || !formEdit) return;

                function syncEditKepala() {
                    if (!editWargaSelect || !editSetKepalaTidak) return;

                    if (editSetKepalaTidak.checked) {
                        editWargaSelect.removeAttribute('disabled');
                    } else {
                        editWargaSelect.value = '';
                        editWargaSelect.setAttribute('disabled', 'disabled');
                    }
                }

                // delegated event: semua klik di dokumen yg punya .btn-edit-warga
                $(document).on('click', '.btn-edit-warga', function() {
                    const btn = this;

                    const updateUrl = btn.getAttribute('data-update-url') || '';
                    const nama = btn.getAttribute('data-nama') || '';
                    const rt = btn.getAttribute('data-rt') || '';
                    const no = btn.getAttribute('data-no') || '';
                    const hp = btn.getAttribute('data-hp') || '';
                    const alamat = btn.getAttribute('data-alamat') || '';
                    const wargaId = btn.getAttribute('data-warga-id');
                    const isKepala = btn.getAttribute('data-is-kepala') === '1';

                    if (updateUrl) {
                        formEdit.setAttribute('action', updateUrl);
                    }

                    if (editNama) editNama.value = nama;
                    if (editRt) editRt.value = rt;
                    if (editNo) editNo.value = no;
                    if (editHp) editHp.value = hp;
                    if (editAlamat) editAlamat.value = alamat;
                    if (editPin) editPin.value = '';

                    if (editSetKepalaYa && editSetKepalaTidak) {
                        if (isKepala) {
                            editSetKepalaYa.checked = true;
                            editSetKepalaTidak.checked = false;
                        } else {
                            editSetKepalaYa.checked = false;
                            editSetKepalaTidak.checked = true;
                        }
                    }

                    if (editWargaSelect) {
                        if (wargaId && !isKepala) {
                            editWargaSelect.value = wargaId;
                        } else {
                            editWargaSelect.value = '';
                        }
                    }

                    syncEditKepala();

                    // buka modal secara manual
                    const modal = new bootstrap.Modal(editModalEl);
                    modal.show();
                });

                // kalau mau, kamu masih bisa pakai toggle manual ini
                if (editSetKepalaYa) editSetKepalaYa.addEventListener('change', syncEditKepala);
                if (editSetKepalaTidak) editSetKepalaTidak.addEventListener('change', syncEditKepala);
            })();

            // ============================================
            // 3. DATATABLES: Data Warga (Yajra server-side)
            // ============================================
            (function() {
                if (typeof $ === 'undefined' || !$('#warga-table').length) return;

                const table = $('#warga-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('kemasjidan.warga.data') }}',
                        data: function(d) {
                            const qInput = document.querySelector('input[name="q"]');
                            const jenisSel = document.querySelector('select[name="jenis"]');
                            d.q = qInput ? (qInput.value || '') : '';
                            d.jenis = jenisSel ? (jenisSel.value || '') : '';
                        }
                    },
                    order: [
                        [1, 'asc']
                    ], // default: urut RT
                    columns: [{
                            data: 'nama',
                            name: 'nama'
                        },
                        {
                            data: 'rt',
                            name: 'rt'
                        },
                        {
                            data: 'no',
                            name: 'no'
                        },
                        {
                            data: 'hp',
                            name: 'hp'
                        },
                        {
                            data: 'jenis_label',
                            name: 'warga_id',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status_label',
                            name: 'status_keluarga'
                        },
                        {
                            data: 'kepala_nama',
                            name: 'kepala.nama',
                            orderable: false
                        },
                        {
                            data: 'aksi',
                            name: 'aksi',
                            orderable: false,
                            searchable: false
                        },
                    ]
                });

                // Filter form → redraw datatable
                const filterForm = document.querySelector(
                    'form[action="{{ route('kemasjidan.warga.index') }}"]');
                if (filterForm) {
                    filterForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        table.ajax.reload();
                    });
                }

                // Re-bind tombol delete setelah redraw
                $('#warga-table').on('draw.dt', function() {
                    bindDeleteButtons();
                });
            })();


            // ============================================
            // 4. SWEETALERT HAPUS WARGA
            // ============================================
            function bindDeleteButtons() {
                document.querySelectorAll('.btn-delete-warga').forEach(btn => {
                    btn.onclick = function(e) {
                        e.preventDefault();

                        const form = this.closest('form');
                        if (!form) return;

                        Swal.fire({
                            title: 'Hapus Warga?',
                            text: 'Data ini tidak dapat dikembalikan setelah dihapus.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, hapus!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    };
                });
            }

            // panggil sekali di awal (untuk render pertama)
            bindDeleteButtons();


            // ============================================
            // 5. IMPORT EXCEL (drag & drop) + auto-show preview
            // ============================================
            (function() {
                const dropZone = document.getElementById('drop-zone');
                const fileInput = document.getElementById('file-import-warga');
                const fileInfo = document.getElementById('file-import-info');
                const btnSubmit = document.getElementById('btn-import-submit');

                // drag & drop logic
                if (dropZone && fileInput && fileInfo && btnSubmit) {
                    function setFile(file) {
                        if (!file) return;
                        fileInfo.textContent = `File dipilih: ${file.name}`;
                        btnSubmit.disabled = false;
                    }

                    dropZone.addEventListener('click', () => fileInput.click());

                    fileInput.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            setFile(this.files[0]);
                        }
                    });

                    dropZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        dropZone.classList.add('bg-light');
                    });

                    dropZone.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        dropZone.classList.remove('bg-light');
                    });

                    dropZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        dropZone.classList.remove('bg-light');

                        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                            fileInput.files = e.dataTransfer.files;
                            setFile(e.dataTransfer.files[0]);
                        }
                    });
                }

                // auto-buka modal import saat balik dari preview
                @if (isset($importPath) && isset($sheetNames))
                    const modalEl = document.getElementById('modalImportWarga');
                    if (modalEl) {
                        const importModal = new bootstrap.Modal(modalEl);
                        importModal.show();
                    }
                @endif
            })();


            // ============================================
            // 6. TANDAI WARGA MENINGGAL + PILIH PENGGANTI
            // ============================================
            (function() {
                const meninggalUrlTemplate = @json(route('kemasjidan.warga.meninggal', ['warga' => '__ID__']));
                const anggotaUrlTemplate = @json(route('kemasjidan.warga.anggota', ['warga' => '__ID__']));

                // Klik tombol "Meninggal"
                $(document).on('click', '.btn-warga-meninggal', function() {
                    const id = $(this).data('id');
                    const nama = $(this).data('nama');

                    const form = document.getElementById('form-warga-meninggal');
                    const namaSpan = document.getElementById('warga-meninggal-nama');
                    const selectPengganti = document.getElementById('select-pengganti');

                    if (!form || !namaSpan || !selectPengganti) return;

                    // set action form
                    const actionUrl = meninggalUrlTemplate.replace('__ID__', id);
                    form.setAttribute('action', actionUrl);

                    // set nama di modal
                    namaSpan.textContent = nama;

                    // loading indicator
                    selectPengganti.innerHTML = '<option value="">Memuat daftar anggota...</option>';
                    selectPengganti.setAttribute('disabled', 'disabled');

                    // load anggota via Ajax
                    const anggotaUrl = anggotaUrlTemplate.replace('__ID__', id);

                    fetch(anggotaUrl, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Gagal memuat anggota');
                            return res.json();
                        })
                        .then(data => {
                            const anggota = data.anggota || [];

                            if (anggota.length === 0) {
                                selectPengganti.innerHTML =
                                    '<option value="">Tidak ada anggota. Tidak bisa memilih pengganti.</option>';
                                selectPengganti.setAttribute('disabled', 'disabled');
                            } else {
                                let opts = '<option value="">-- Pilih anggota keluarga --</option>';
                                anggota.forEach(a => {
                                    opts +=
                                        `<option value="${a.id}">${a.nama} (RT ${a.rt ?? '-'})</option>`;
                                });
                                selectPengganti.innerHTML = opts;
                                selectPengganti.removeAttribute('disabled');
                            }

                            const modal = new bootstrap.Modal(
                                document.getElementById('modalWargaMeninggal')
                            );
                            modal.show();
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: 'Gagal memuat anggota keluarga. Coba ulangi atau periksa koneksi.',
                            });
                        });
                });

                // Konfirmasi sebelum submit form meninggal
                const formMeninggal = document.getElementById('form-warga-meninggal');
                if (formMeninggal) {
                    formMeninggal.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const selectPengganti = document.getElementById('select-pengganti');
                        if (!selectPengganti || !selectPengganti.value) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Belum memilih pengganti',
                                text: 'Silakan pilih anggota keluarga yang akan menjadi kepala baru.',
                            });
                            return;
                        }

                        Swal.fire({
                            icon: 'warning',
                            title: 'Yakin?',
                            html: 'Kepala keluarga akan ditandai <strong>MENINGGAL</strong> dan seluruh data keuangan akan dialihkan ke kepala baru.',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, lanjutkan',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#d33',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                formMeninggal.submit();
                            }
                        });
                    });
                }
            })();


            // ============================================
            // 7. BOOTSTRAP TOOLTIP
            // ============================================
            (function() {
                const tooltipTriggerList = [].slice.call(
                    document.querySelectorAll('[data-bs-toggle="tooltip"]')
                );

                tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
            })();

        });
    </script>
@endpush

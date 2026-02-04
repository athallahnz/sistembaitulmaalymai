@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item" href="{{ route('students.index') }}"aria-current="page"><a>Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a>Dashboard</a></li>
                    </ol>
                </nav>
                <h1 class="mb-2">Daftar Kelas</h1>
                <div class="text-muted small">Kelola kelas, tahun ajaran, dan mapping akun biaya pendidikan.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#eduClassCreateModal">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kelas
                </button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="p-3 shadow table-responsive rounded">
            <table class="table table-bordered yajra-datatable w-100">
                <thead>
                    <tr>
                        <th>Nama Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Jumlah Murid</th>
                        <th style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- Hidden delete form --}}
        <form id="deleteForm" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>

        {{-- =========================
            MODAL CREATE
        ========================== --}}
        <div class="modal fade" id="eduClassCreateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4">
                    <form method="POST" action="{{ route('edu_classes.store') }}" class="needs-validation" novalidate>
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Kelas Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Nama Kelas</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        name="name" value="{{ old('name') }}" placeholder="Contoh: Kelas A" required>
                                    <div class="invalid-feedback">Nama kelas wajib diisi.</div>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Tahun Awal Ajaran</label>
                                    <select class="form-select" id="create_tahun_awal" name="tahun_awal" required>
                                        <option value="" disabled {{ old('tahun_awal') ? '' : 'selected' }}>Pilih
                                            Tahun</option>
                                        @foreach ($years as $year)
                                            <option value="{{ $year }}"
                                                {{ old('tahun_awal') == $year ? 'selected' : '' }}>
                                                {{ $year }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Tahun awal wajib dipilih.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Tahun Ajaran (Preview)</label>
                                    <input type="text" class="form-control" id="create_tahun_ajaran" readonly>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Pilih Biaya Keuangan Pendidikan</label>
                                    <div class="border rounded-3 p-2" style="max-height:240px; overflow:auto;">
                                        @foreach ($akunKeuangans as $akun)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="akun_keuangan_ids[]"
                                                    id="create_akun{{ $akun->id }}" value="{{ $akun->id }}"
                                                    {{ is_array(old('akun_keuangan_ids')) && in_array($akun->id, old('akun_keuangan_ids')) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="create_akun{{ $akun->id }}">
                                                    {{ $akun->nama_akun }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('akun_keuangan_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-1"></i> Simpan Kelas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- =========================
            MODAL EDIT (AJAX)
        ========================== --}}
        <div class="modal fade" id="eduClassEditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4">
                    <form method="POST" id="editForm" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title">Edit Kelas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Nama Kelas</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                    <div class="invalid-feedback">Nama kelas wajib diisi.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Tahun Awal Ajaran</label>
                                    <select class="form-select" id="edit_tahun_awal" name="tahun_awal" required>
                                        <option value="" disabled selected>Pilih Tahun</option>
                                        @foreach ($years as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Tahun awal wajib dipilih.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Tahun Ajaran (Preview)</label>
                                    <input type="text" class="form-control" id="edit_tahun_ajaran" readonly>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Pilih Biaya Keuangan Pendidikan</label>
                                    <div class="border rounded-3 p-2" style="max-height:240px; overflow:auto;">
                                        @foreach ($akunKeuangans as $akun)
                                            <div class="form-check">
                                                <input class="form-check-input edit-akun" type="checkbox"
                                                    name="akun_keuangan_ids[]" id="edit_akun{{ $akun->id }}"
                                                    value="{{ $akun->id }}">
                                                <label class="form-check-label" for="edit_akun{{ $akun->id }}">
                                                    {{ $akun->nama_akun }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save me-1"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function previewTahunAjaran(selectEl, outputEl) {
            const y = parseInt(selectEl.value || '');
            outputEl.value = (!isNaN(y)) ? `${y}/${y + 1}` : '';
        }

        // Bootstrap validation
        (function() {
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

        // CREATE preview init
        (function() {
            const createTahunAwal = document.getElementById('create_tahun_awal');
            const createTahunAjaran = document.getElementById('create_tahun_ajaran');
            if (createTahunAwal && createTahunAjaran) {
                createTahunAwal.addEventListener('change', () => previewTahunAjaran(createTahunAwal,
                createTahunAjaran));
                previewTahunAjaran(createTahunAwal, createTahunAjaran);
            }
        })();

        // EDIT preview init
        (function() {
            const editTahunAwal = document.getElementById('edit_tahun_awal');
            const editTahunAjaran = document.getElementById('edit_tahun_ajaran');
            if (editTahunAwal && editTahunAjaran) {
                editTahunAwal.addEventListener('change', () => previewTahunAjaran(editTahunAwal, editTahunAjaran));
            }
        })();

        $(document).ready(function() {
            const dt = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('edu_classes.data') }}",
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'tahun_ajaran',
                        name: 'tahun_ajaran'
                    },
                    {
                        data: 'students_count',
                        name: 'students_count',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            // OPEN EDIT MODAL
            $(document).on('click', '.btn-edit', async function() {
                const jsonUrl = $(this).data('json-url');
                const updateUrl = $(this).data('update-url');

                if (!jsonUrl || !updateUrl) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'URL edit tidak ditemukan pada tombol.'
                    });
                    return;
                }

                try {
                    const res = await fetch(jsonUrl, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json?.message ||
                        'Gagal memuat data kelas.');

                    document.getElementById('editForm').setAttribute('action', updateUrl);

                    document.getElementById('edit_name').value = json.data.name || '';
                    document.getElementById('edit_tahun_awal').value = json.data.tahun_awal || '';
                    previewTahunAjaran(
                        document.getElementById('edit_tahun_awal'),
                        document.getElementById('edit_tahun_ajaran')
                    );

                    // reset checkbox
                    document.querySelectorAll('.edit-akun').forEach(cb => cb.checked = false);

                    const selected = (json.data.akun_keuangan_ids || []).map(x => String(x));
                    selected.forEach(idAkun => {
                        const el = document.getElementById(`edit_akun${idAkun}`);
                        if (el) el.checked = true;
                    });

                    new bootstrap.Modal(document.getElementById('eduClassEditModal')).show();
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.message
                    });
                }
            });

            // DELETE SWEETALERT
            $(document).on('click', '.btn-delete', function() {
                const url = $(this).data('url');
                if (!url) return;

                Swal.fire({
                    title: 'Yakin hapus kelas ini?',
                    text: 'Tindakan ini tidak bisa dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    const form = document.getElementById('deleteForm');
                    form.setAttribute('action', url);
                    form.submit();
                });
            });
        });
    </script>

    {{-- Jika ada error validasi CREATE, buka modal create --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('eduClassCreateModal')).show();
            });
        </script>
    @endif
@endpush

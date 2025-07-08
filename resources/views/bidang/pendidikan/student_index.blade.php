@extends('layouts.app')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page"><a>Home</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a>Dashboard</a></li>
            </ol>
        </nav>
        <h1 class="mb-4">Dashboard Murid</h1>

        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#studentModal">
            <i class="bi bi-person-plus"></i>
            Tambah Murid
        </button>

        <div class="p-3 shadow table-responsive rounded">
            <table class="table table-bordered yajra-datatable">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kelas - T.A.</th>
                        <th>Total Biaya Pendidikan</th>
                        <th>RFID UID</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
        <form id="delete-form" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
        {{-- Modal Form Tambah Student --}}
        <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title"><strong>Formulir Pendaftaran Murid Baru</strong></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                {{-- Kolom 1: Data Diri Murid --}}
                                <div class="col-md-4">
                                    <h4 class="mb-3">Data Diri Murid</h4>

                                    <div class="mb-3">
                                        <label>Kelas <span class="text-danger">*</span></label>
                                        <select name="edu_class_id" class="form-select" required>
                                            <option value="">Pilih Kelas</option>
                                            @foreach ($eduClasses as $class)
                                                <option value="{{ $class->id }}"
                                                    {{ old('edu_class_id') == $class->id ? 'selected' : '' }}>
                                                    {{ $class->name }} -
                                                    {{ $class->tahun_ajaran }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label>NISN</label>
                                            <input type="text" name="nisn" class="form-control"
                                                value="{{ old('nisn') }}" placeholder="Masukkan NISN Murid..">
                                            @error('nisn')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label>No. Induk <span class="text-danger">*</span></label>
                                            <input type="text" name="no_induk" class="form-control"
                                                value="{{ old('no_induk') }}" placeholder="Ex: 001" required>
                                            @error('no_induk')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control"
                                                value="{{ old('name') }}" placeholder="Masukkan Nama Murid.." required>
                                            @error('name')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Nama Panggilan <span class="text-danger">*</span></label>
                                            <input type="text" name="nickname" class="form-control"
                                                value="{{ old('nickname') }}" placeholder="Masukkan Nama Panggilan Murid.."
                                                required>
                                            @error('nickname')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label>Jenis Kelamin <span class="text-danger">*</span> </label>
                                        <select name="jenis_kelamin" class="form-select" required>
                                            <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>
                                                Laki-laki</option>
                                            <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>
                                                Perempuan</option>
                                        </select>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Tempat Lahir</label>
                                            <input type="text" name="tempat_lahir" class="form-control"
                                                value="{{ old('tempat_lahir') }}" placeholder="Ex: Surabaya">
                                            @error('tempat_lahir')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Tanggal Lahir</label>
                                            <input type="text" name="ttl" class="form-control"
                                                placeholder="dd/mm/yyyy" value="{{ old('ttl') }}">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label>Usia</label>
                                        <input type="text" name="usia" class="form-control" readonly
                                            placeholder="Akan dihitung otomatis.." required>
                                        @error('usia')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>NIK</label>
                                        <input type="text" name="nik" class="form-control"
                                            placeholder="Masukkan NIK.." value="{{ old('nik') }}">
                                        @error('nik')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>No. Akta</label>
                                        <input type="text" name="no_akte" class="form-control"
                                            placeholder="Masukkan No. Akta.." value="{{ old('no_akte') }}">
                                        @error('no_akte')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>No. Kartu Keluarga</label>
                                        <input type="text" name="no_kk" class="form-control"
                                            placeholder="Masukkan No. Kartu Keluarga.." value="{{ old('no_kk') }}">
                                        @error('no_kk')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>Alamat KK (Utama)</label>
                                        <textarea name="alamat_kk" id="alamat_kk" class="form-control" placeholder="Sesuaikan dengan Alamat KK...">{{ old('alamat_kk') }}</textarea>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="copyAlamatTinggal">
                                        <label class="form-check-label" for="copyAlamatTinggal">
                                            Gunakan Alamat Utama sbg Alamat Tinggal
                                        </label>
                                    </div>

                                    <div class="mb-3">
                                        <label>Alamat Tinggal</label>
                                        <textarea name="alamat_tinggal" id="alamat_tinggal" class="form-control">{{ old('alamat_tinggal') }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label>Pas Photo</label>
                                        <input type="file" name="pas_photo" class="form-control">
                                        @error('pas_photo')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>Upload Akta</label>
                                        <input type="file" name="akte" class="form-control">
                                        @error('akte')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>Upload KK</label>
                                        <input type="file" name="kk" class="form-control">
                                        @error('kk')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Kolom 2: Data Wali Murid --}}
                                <div class="col-md-4">
                                    <h4 class="mb-3">Data Wali Murid</h4>
                                    <div class="accordion mb-4" id="waliAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingAyah">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapseAyah" aria-expanded="true"
                                                    aria-controls="collapseAyah">
                                                    Data Ayah
                                                </button>
                                            </h2>
                                            <div id="collapseAyah"
                                                class="accordion-collapse collapse show"aria-labelledby="headingAyah"
                                                data-bs-parent="#waliAccordion">
                                                <div class="accordion-body">
                                                    @include(
                                                        'bidang.pendidikan.wali_murids.partials.form_wali_murid',
                                                        [
                                                            'hubungan' => 'Ayah',
                                                            'jenis_kelamin' => 'L',
                                                            'loopIndex' => 0,
                                                        ]
                                                    )
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingIbu">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapseIbu"
                                                    aria-expanded="false" aria-controls="collapseIbu">
                                                    Data Ibu
                                                </button>
                                            </h2>
                                            <div id="collapseIbu" class="accordion-collapse collapse"
                                                aria-labelledby="headingIbu" data-bs-parent="#waliAccordion">
                                                <div class="accordion-body">
                                                    @include(
                                                        'bidang.pendidikan.wali_murids.partials.form_wali_murid',
                                                        [
                                                            'hubungan' => 'Ibu',
                                                            'jenis_kelamin' => 'P',
                                                            'loopIndex' => 1,
                                                        ]
                                                    )
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Kolom 3: Rincian Biaya & RFID --}}
                                <div class="col-md-4">
                                    <h4 class="mb-3">Rincian Biaya <span class="text-danger">*</span></h4>

                                    {{-- Template hidden untuk akun --}}
                                    <select class="akun-template" hidden>
                                        @foreach ($akunKeuangans as $akun)
                                            <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
                                        @endforeach
                                    </select>

                                    <table class="table table-bordered" id="costTable">
                                        <thead>
                                            <tr>
                                                <th>Pos Biaya</th>
                                                <th>Jumlah Nominal</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                    <button type="button" id="addRow" class="btn btn-sm btn-outline-primary mb-3">+
                                        Tambah Biaya</button>

                                    <div class="mb-3">
                                        <label>Total Biaya</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" id="total_display" class="form-control" readonly>
                                        </div>
                                        <input type="hidden" name="total_biaya" id="total_biaya">
                                    </div>

                                    <hr>
                                    <h6 class="mb-3">Kartu RFID <span class="text-danger">*</span></h6>
                                    <div class="mb-3">
                                        <input type="text" name="rfid_uid" class="form-control"
                                            placeholder="Tempelkan Kartu..." required>
                                        @error('rfid_uid')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Daftarkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
@push('scripts')
    {{-- CDN untuk Flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === Modal: Buka otomatis jika ada error ===
            @if ($errors->any())
                const studentModal = new bootstrap.Modal(document.getElementById('studentModal'));
                studentModal.show();

                // Fokus ke input RFID saat modal terbuka
                document.getElementById('studentModal').addEventListener('shown.bs.modal', function() {
                    document.getElementById('rfid_uid_input')?.focus();
                });
            @endif

            // === Inisialisasi Flatpickr untuk TTL dan Hitung Usia ===
            flatpickr("input[name='ttl']", {
                dateFormat: "d/m/Y",
                altInput: true,
                altFormat: "d/m/Y",
                altInputClass: "form-control",
                onReady: function(selectedDates, dateStr, instance) {
                    instance.altInput.setAttribute('required', 'required');
                    instance.altInput.setAttribute('name', 'ttl');
                    instance.input.removeAttribute('name');

                    instance.altInput.addEventListener('invalid', function() {
                        this.setCustomValidity('Tanggal lahir wajib diisi.');
                    });
                    instance.altInput.addEventListener('input', function() {
                        this.setCustomValidity('');
                    });
                },
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        const birthDate = selectedDates[0];
                        const today = new Date();
                        let years = today.getFullYear() - birthDate.getFullYear();
                        let months = today.getMonth() - birthDate.getMonth();
                        let days = today.getDate() - birthDate.getDate();

                        if (days < 0) {
                            days += new Date(today.getFullYear(), today.getMonth(), 0).getDate();
                            months--;
                        }
                        if (months < 0) {
                            months += 12;
                            years--;
                        }
                        document.querySelector("input[name='usia']").value =
                            `${years} tahun ${months} bulan ${days} hari`;
                    }
                }
            });

            // === DataTable Yajra ===
            $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('students.data') }}",
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'kelas',
                        name: 'edu_class.name'
                    },
                    {
                        data: 'total_biaya',
                        name: 'total_biaya',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return number_format(data);
                        }
                    },
                    {
                        data: 'rfid_uid',
                        name: 'rfid_uid',
                        orderable: false,
                        searchable: false
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

            // === Fungsi Format Angka ===
            function number_format(number, decimals = 0, dec_point = ',', thousands_sep = '.') {
                number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
                let n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = thousands_sep,
                    dec = dec_point,
                    s = '',
                    toFixedFix = function(n, prec) {
                        return '' + Math.round(n * Math.pow(10, prec)) / Math.pow(10, prec);
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

            // === Dinamis Rincian Biaya ===
            const tbody = document.querySelector('#costTable tbody');
            const totalDisplay = document.getElementById('total_display');
            const totalInput = document.getElementById('total_biaya');
            const akunTemplate = document.querySelector('.akun-template');
            const classSelect = document.querySelector('[name="edu_class_id"]');

            function formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID').format(angka);
            }

            function updateTotal() {
                let total = 0;
                document.querySelectorAll('input.jumlah-hidden').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                totalDisplay.value = formatRupiah(total);
                totalInput.value = total;
            }

            document.getElementById('addRow')?.addEventListener('click', () => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <select name="akun_keuangan_id[]" class="form-select" required>
                            ${akunTemplate.innerHTML}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control jumlah" required>
                        <input type="hidden" name="jumlah[]" class="jumlah-hidden">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>`;
                tbody.appendChild(row);
            });

            tbody.addEventListener('click', function(e) {
                if (e.target.closest('.remove-row')) {
                    e.target.closest('tr').remove();
                    updateTotal();
                }
            });

            tbody.addEventListener('input', function(e) {
                if (e.target.classList.contains('jumlah')) {
                    const angka = e.target.value.replace(/\D/g, '');
                    e.target.value = formatRupiah(angka);
                    const hidden = e.target.closest('td').querySelector('.jumlah-hidden');
                    if (hidden) hidden.value = angka;
                    updateTotal();
                }
            });

            // AJAX Ambil Akun Keuangan Default Berdasarkan Kelas
            classSelect?.addEventListener('change', function() {
                fetch(`/kelas/${this.value}/akun-keuangan`)
                    .then(res => res.json())
                    .then(data => {
                        tbody.innerHTML = '';
                        data.forEach(akun => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <select name="akun_keuangan_id[]" class="form-select" required>
                                        <option value="${akun.id}">${akun.nama_akun}</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control jumlah" required>
                                    <input type="hidden" name="jumlah[]" class="jumlah-hidden">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-row">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </td>`;
                            tbody.appendChild(row);
                        });
                        updateTotal();
                    });
            });

            updateTotal();

            // === Alamat: Salin Alamat KK ke Alamat Tinggal & Wali ===
            const alamatKK = document.getElementById('alamat_kk');
            const alamatTinggal = document.getElementById('alamat_tinggal');
            const copyAlamatTinggal = document.getElementById('copyAlamatTinggal');

            copyAlamatTinggal?.addEventListener('change', function() {
                alamatTinggal.value = this.checked ? alamatKK.value : '';
            });

            alamatKK?.addEventListener('input', function() {
                if (copyAlamatTinggal.checked) {
                    alamatTinggal.value = this.value;
                }
            });

            // Fungsi untuk Wali (Ayah/Ibu)
            function setupAlamatWali(index) {
                const checkbox = document.getElementById(`copyAlamatWali${index}`);
                const alamat = document.getElementById(`alamat_wali_${index}`);
                if (!checkbox || !alamat) return;

                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        alamat.value = alamatKK.value;
                        alamat.setAttribute('readonly', true);
                    } else {
                        alamat.removeAttribute('readonly');
                        alamat.value = '';
                    }
                });

                alamatKK.addEventListener('input', function() {
                    if (checkbox.checked) {
                        alamat.value = alamatKK.value;
                    }
                });
            }

            // Inisialisasi untuk Ayah (0) dan Ibu (1)
            setupAlamatWali(0);
            setupAlamatWali(1);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const deleteForm = document.getElementById('delete-form');

            document.body.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-hapus');
                if (!btn) return;

                const url = btn.getAttribute('data-url');

                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Data siswa akan dihapus permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteForm.setAttribute('action', url);
                        deleteForm.submit();
                    }
                });
            });
        });
    </script>
@endpush

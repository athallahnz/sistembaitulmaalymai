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
                                                value="{{ old('nisn') }}" placeholder="Masukkan NISN Murid.." required>
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
                                                value="{{ old('nickname') }}" placeholder="Masukkan Nama Panggilan Murid.." required>
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
                                            <div id="collapseAyah" class="accordion-collapse collapse show"
                                                aria-labelledby="headingAyah" data-bs-parent="#waliAccordion">
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
    @if ($errors->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                var myModal = new bootstrap.Modal(document.getElementById('studentModal'));
                myModal.show();

                // Fokus ke input RFID saat modal ditampilkan
                document.getElementById('studentModal').addEventListener('shown.bs.modal', function() {
                    document.getElementById('rfid_uid_input').focus();
                });
            });
        </script>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
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
                        render: function(data, type, row) {
                            return number_format(data); // Format debit
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
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                }
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

        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.querySelector('#costTable tbody');
            const totalDisplay = document.getElementById('total_display');
            const totalInput = document.getElementById('total_biaya');
            const classSelect = document.querySelector('[name="edu_class_id"]');
            const akunSelectTemplate = document.querySelector('select.akun-template');

            // Format angka ke rupiah
            function formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID').format(angka);
            }

            // Hitung dan update total
            function updateTotal() {
                let total = 0;
                document.querySelectorAll('input.jumlah-hidden').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                totalDisplay.value = formatRupiah(total);
                totalInput.value = total;
            }

            // Tambah baris biaya baru
            document.getElementById('addRow').addEventListener('click', function() {
                const akunOptions = akunSelectTemplate.innerHTML;
                const row = document.createElement('tr');
                row.innerHTML = `
                <td>
                    <select name="akun_keuangan_id[]" class="form-select" required>
                        ${akunOptions}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control jumlah" required>
                    <input type="hidden" name="jumlah[]" class="jumlah-hidden">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash3"></i></button>
                </td>
            `;
                tbody.appendChild(row);
            });

            // Event Delegation: hapus baris dan hitung total
            tbody.addEventListener('click', function(e) {
                if (e.target.closest('.remove-row')) {
                    e.target.closest('tr').remove();
                    updateTotal();
                }
            });

            // Format input jumlah dan isi hidden input
            tbody.addEventListener('input', function(e) {
                if (e.target.classList.contains('jumlah')) {
                    const angka = e.target.value.replace(/\D/g, '');
                    e.target.value = formatRupiah(angka);
                    const hiddenInput = e.target.closest('td').querySelector('.jumlah-hidden');
                    if (hiddenInput) hiddenInput.value = angka;
                    updateTotal();
                }
            });


            // AJAX: saat kelas berubah, ambil akun keuangan default
            console.log('Template:', akunSelectTemplate?.innerHTML);
            classSelect.addEventListener('change', function() {
                const classId = this.value;
                fetch(`/kelas/${classId}/akun-keuangan`)
                    .then(res => res.json())
                    .then(data => {
                        console.log(data);
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
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                        updateTotal();
                    });
            });

            updateTotal(); // Inisialisasi awal
        });

        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("input[name='ttl']", {
                dateFormat: "d/m/Y",
                altInput: true,
                altFormat: "d/m/Y",
                altInputClass: "form-control", // tetap pakai bootstrap
                onReady: function(selectedDates, dateStr, instance) {
                    // Tambahkan required dan validasi HTML5 ke altInput
                    instance.altInput.setAttribute('required', 'required');
                    instance.altInput.setAttribute('name', 'ttl'); // agar nama tetap dikirim

                    // Hapus name di input asli agar tidak double
                    instance.input.removeAttribute('name');

                    // Pesan error kustom
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
                            const prevMonth = new Date(today.getFullYear(), today.getMonth(), 0)
                                .getDate();
                            days += prevMonth;
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
        });

        document.getElementById('copyAlamatTinggal').addEventListener('change', function() {
            const kk = document.getElementById('alamat_kk').value;
            document.getElementById('alamat_tinggal').value = this.checked ? kk : '';
        });

        document.getElementById('copyAlamatWali').addEventListener('change', function() {
            const kk = document.getElementById('alamat_kk').value;
            document.getElementById('wali_alamat').value = this.checked ? kk : '';
        });

        // Optional: update textarea if alamat_kk changes and checkbox is checked
        document.getElementById('alamat_kk').addEventListener('input', function() {
            if (document.getElementById('copyAlamatTinggal').checked) {
                document.getElementById('alamat_tinggal').value = this.value;
            }
            if (document.getElementById('copyAlamatWali').checked) {
                document.getElementById('wali_alamat').value = this.value;
            }
        });

        function setupAlamatWali(index) {
            const checkbox = document.getElementById(`copyAlamatWali${index}`);
            const alamatKK = document.getElementById('alamat_kk');
            const alamatWali = document.getElementById(`alamat_wali_${index}`);

            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    alamatWali.value = alamatKK.value;
                    alamatWali.setAttribute('readonly', true);
                } else {
                    alamatWali.removeAttribute('readonly');
                    alamatWali.value = '';
                }
            });

            alamatKK.addEventListener('input', function() {
                if (checkbox.checked) {
                    alamatWali.value = alamatKK.value;
                }
            });
        }

        // Jalankan untuk dua wali (Ayah dan Ibu)
        setupAlamatWali(0);
        setupAlamatWali(1);
    </script>
@endpush

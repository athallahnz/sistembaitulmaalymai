@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Data Murid</h1>

        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#studentModal">
            Tambah Siswa
        </button>

        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#eduClassModal">
            Tambah Kelas
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
            <div class="modal-dialog modal-xl"> {{-- Lebih lebar agar tabel tidak sempit --}}
                <div class="modal-content">
                    <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title"><strong>Formulir Pendaftaran Murid Baru<strong></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                {{-- Kolom 1: Data Diri Murid --}}
                                <div class="col-md-4">
                                    <h4 class="mb-3">Data Diri Murid</h4>

                                    <div class="mb-3">
                                        <label>Kelas</label>
                                        <select name="edu_class_id" class="form-select" required>
                                            <option value="">Pilih Kelas</option>
                                            @foreach ($eduClasses as $class)
                                                <option value="{{ $class->id }}">{{ $class->name }} -
                                                    {{ $class->tahun_ajaran }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label>Nama Lengkap</label>
                                        <input type="text" name="name" class="form-control"
                                            placeholder="Masukkan Nama Murid.." required>
                                        @error('name')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>Jenis Kelamin</label>
                                        <select name="jenis_kelamin" class="form-select" required>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label>Tanggal Lahir</label>
                                        <input type="text" name="ttl" class="form-control" placeholder="dd/mm/yyyy">
                                    </div>

                                    <div class="mb-3">
                                        <label>Usia</label>
                                        <input type="text" name="usia" class="form-control" readonly
                                            placeholder="Akan dihitung otomatis..">
                                    </div>

                                    <div class="mb-3">
                                        <label>NIK</label>
                                        <input type="text" name="nik" class="form-control"
                                            placeholder="Masukkan NIK..">
                                        @error('nik')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>No. Akta</label>
                                        <input type="text" name="no_akte" class="form-control"
                                            placeholder="Masukkan No. Akta..">
                                        @error('no. akte')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>No. Kartu Keluarga</label>
                                        <input type="text" name="no_kk" class="form-control"
                                            placeholder="Masukkan No. Kartu Keluarga..">
                                        @error('no_kk')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>Alamat KK (Utama)</label>
                                        <textarea name="alamat_kk" id="alamat_kk" class="form-control" placeholder="Sesuaikan dengan Alamat KK..."></textarea>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="copyAlamatTinggal">
                                        <label class="form-check-label" for="copyAlamatTinggal">
                                            Gunakan Alamat Utama sbg Alamat Tinggal
                                        </label>
                                    </div>

                                    <div class="mb-3">
                                        <label>Alamat Tinggal</label>
                                        <textarea name="alamat_tinggal" id="alamat_tinggal" class="form-control"></textarea>
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

                                    <div class="mb-3">
                                        <label>Nama</label>
                                        <input type="text" name="wali_nama" class="form-control"
                                            placeholder="Masukkan Nama Wali Murid.." required>
                                        @error('wali_nama')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>Jenis Kelamin</label>
                                        <select name="wali_jenis_kelamin" class="form-select">
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label>Hubungan</label>
                                        <select name="wali_hubungan" class="form-select" required>
                                            <option value="Ayah">Ayah</option>
                                            <option value="Ibu">Ibu</option>
                                            <option value="Wali">Wali</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label>NIK</label>
                                        <input type="text" name="wali_nik" class="form-control"
                                            placeholder="Masukkan NIK Wali Murid..">
                                        @error('wali_nik')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>No. Handphone</label>
                                        <input type="text" name="wali_no_hp" class="form-control"
                                            placeholder="Masukkan No. Handphone..">
                                        @error('wali_no_hp')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="copyAlamatWali">
                                        <label class="form-check-label" for="copyAlamatWali">
                                            Gunakan Alamat Utama sbg Alamat Wali Murid
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label>Alamat Wali</label>
                                        <textarea name="wali_alamat" id="wali_alamat" class="form-control"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label>Foto KTP</label>
                                        <input type="file" name="wali_foto_ktp" class="form-control">
                                        @error('wali_foto_ktp')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Kolom 3: Rincian Biaya & RFID --}}
                                <div class="col-md-4">
                                    <h4 class="mb-3">Rincian Biaya</h4>

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
                                    <h6 class="mb-3">Kartu RFID</h6>
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


        <!-- Modal Form Tambah Edu Class -->
        <div class="modal fade" id="eduClassModal" tabindex="-1" aria-labelledby="eduClassModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('edu_classes.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="eduClassModalLabel">Tambah Kelas Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Nama Kelas -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Kelas:</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <!-- Tahun Awal Ajaran -->
                                <div class="col-md-6 mb-3">
                                    <label for="tahun_awal" class="form-label">Tahun Awal Ajaran:</label>
                                    <select class="form-select" id="tahun_awal" name="tahun_awal" required>
                                        <option value="" disabled selected>Pilih Tahun</option>
                                        @php
                                            $startYear = date('Y'); // tahun sekarang
                                            $endYear = $startYear + 5; // 5 tahun ke depan
                                        @endphp
                                        @for ($year = $startYear; $year <= $endYear; $year++)
                                            <option value="{{ $year }}"
                                                {{ old('tahun_awal') == $year ? 'selected' : '' }}>
                                                {{ $year }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>

                                <!-- Tahun Ajaran Preview -->
                                <div class="col-md-6 mb-3">
                                    <label for="tahun_ajaran" class="form-label">Tahun Ajaran (Preview):</label>
                                    <input type="text" class="form-control" id="tahun_ajaran" readonly>
                                </div>
                            </div>

                            <!-- Tambah pilih akun keuangan -->
                            <div class="mb-3">
                                <label class="form-label">Pilih Akun Keuangan</label>
                                @foreach ($akunKeuangans as $akun)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="akun_keuangan_ids[]"
                                            id="akun{{ $akun->id }}" value="{{ $akun->id }}">
                                        <label class="form-check-label" for="akun{{ $akun->id }}">
                                            {{ $akun->nama_akun }}
                                        </label>
                                    </div>
                                @endforeach
                                @error('akun_keuangan_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>



                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan Kelas</button>
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
    <script>
        const tahunAwalInput = document.getElementById('tahun_awal');
        const tahunAjaranInput = document.getElementById('tahun_ajaran');

        tahunAwalInput.addEventListener('input', () => {
            const tahunAwal = parseInt(tahunAwalInput.value);
            if (!isNaN(tahunAwal)) {
                tahunAjaranInput.value = `${tahunAwal}/${tahunAwal + 1}`;
            } else {
                tahunAjaranInput.value = '';
            }
        });
    </script>
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
    </script>
    <script>
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
    </script>
    <!-- Tambahkan di bawah sebelum </body> -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("input[name='ttl']", {
            dateFormat: "d/m/Y",
            altInput: true,
            altFormat: "d/m/Y",
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    const birthDate = selectedDates[0];
                    const today = new Date();

                    let years = today.getFullYear() - birthDate.getFullYear();
                    let months = today.getMonth() - birthDate.getMonth();
                    let days = today.getDate() - birthDate.getDate();

                    if (days < 0) {
                        // Ambil jumlah hari bulan sebelumnya
                        const prevMonth = new Date(today.getFullYear(), today.getMonth(), 0).getDate();
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
    </script>
    <script>
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
    </script>
@endpush

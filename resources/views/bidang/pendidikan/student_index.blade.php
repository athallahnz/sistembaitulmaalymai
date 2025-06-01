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
            <div class="modal-dialog modal-lg"> {{-- Lebih lebar agar tabel tidak sempit --}}
                <div class="modal-content">
                    <form method="POST" action="{{ route('students.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="studentModalLabel">Daftar Murid Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            {{-- Informasi Murid --}}
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama:</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}" placeholder="Masukkan Nama Murid.." required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="edu_class_id" class="form-label">Kelas:</label>
                                <select name="edu_class_id" class="form-select @error('edu_class_id') is-invalid @enderror"
                                    required>
                                    <option value="" disabled selected>Pilih Kelas</option>
                                    @foreach ($eduClasses as $class)
                                        <option value="{{ $class->id }}"
                                            {{ old('edu_class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }} - {{ $class->tahun_ajaran }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('edu_class_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Rincian Biaya --}}
                            <hr>
                            <h6>Rincian Biaya per Murid</h6>
                            {{-- Template akun keuangan tersembunyi --}}
                            <select class="akun-template" hidden>
                                @foreach ($akunKeuangans as $akun)
                                    <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
                                @endforeach
                            </select>
                            <table class="table table-bordered align-middle mt-3" id="costTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pos Biaya</th>
                                        <th>Jumlah (Rp)</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                    </tr>
                                </tbody>
                            </table>

                            <button type="button" id="addRow" class="btn btn-sm btn-outline-primary mb-3">
                                + Tambah Biaya
                            </button>

                            <div class="mb-3">
                                <label for="total_display" class="form-label"><strong>Total Rincian:</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="total_display" class="form-control" value="0" readonly>
                                </div>
                            </div>

                            {{-- Total Biaya (Hidden dari input manual tapi dikirimkan) --}}
                            <input type="number" id="total_biaya" name="total_biaya"
                                class="form-control @error('total_biaya') is-invalid @enderror" readonly hidden>
                            @error('total_biaya')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="mb-3">
                                <label for="rfid_uid_input" class="form-label">Daftarkan Kartu RFID:</label>
                                <input type="text" class="form-control @error('rfid_uid') is-invalid @enderror"
                                    id="rfid_uid_input" name="rfid_uid" value="{{ old('rfid_uid') }}"
                                    placeholder="Tempelkan Kartu.." required autofocus>
                                @error('rfid_uid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
        <div class="modal fade" id="eduClassModal" tabindex="-1" aria-labelledby="eduClassModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('edu_classes.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="eduClassModalLabel">Tambah Kelas Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
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
@endpush

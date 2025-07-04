@extends('layouts.app')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page"><a>Home</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a>Dashboard</a></li>
            </ol>
        </nav>
        <h1 class="mb-4">Daftar Kelas</h1>

        <button type="button" class="btn btn-success mb-3 text-end" data-bs-toggle="modal" data-bs-target="#eduClassModal">
            <i class="bi bi-plus-circle"></i>
            Tambah Kelas
        </button>

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
                                    id="name" name="name" value="{{ old('name') }}"
                                    placeholder="Masukkan Nama Kelas.." required>
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
                                <label class="form-label">Pilih Biaya Keuangan Pendidikan</label>
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

        <div class="p-3 shadow table-responsive rounded">
            <table class="table table-bordered yajra-datatable">
                <thead>
                    <tr>
                        <th>Nama Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Jumlah Murid</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection
@push('scripts')
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
                    }
                ],
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    </script>
@endpush

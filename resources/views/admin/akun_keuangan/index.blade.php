@extends('layouts.app')
@section('title', 'Manajemen Akun Keuangan')

@section('content')
    <div class="container">
        <h2 class="mb-4">Daftar Akun Keuangan</h2>

        <!-- Button untuk membuka modal Create -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#akunModal">
            Tambah Akun Keuangan
        </button>
        <style>
            .shake {
                animation: shake 0.5s ease-in-out;
            }

            @keyframes shake {
                0% {
                    transform: translateX(0);
                }

                20% {
                    transform: translateX(-10px);
                }

                40% {
                    transform: translateX(10px);
                }

                60% {
                    transform: translateX(-10px);
                }

                80% {
                    transform: translateX(10px);
                }

                100% {
                    transform: translateX(0);
                }
            }
        </style>
        <!-- Modal -->
        <div class="modal fade" id="akunModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="akunModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="akunModalLabel">Silahkan Isi Data Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form Create -->
                        <form id="akunForm" action="{{ route('admin.akun_keuangan.store') }}" method="POST">
                            @csrf
                            <input type="hidden" id="formMethod" name="_method" value="POST">

                            <div class="mb-3">
                                <label class="mb-2">ID Akun</label>
                                <input type="number" id="id" name="id" class="form-control"
                                    value="{{ old('id') }}" placeholder="Masukkan ID Akun" required>
                                @error('id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Nama Akun</label>
                                <input type="text" id="nama_akun" name="nama_akun" class="form-control"
                                    value="{{ old('nama_akun') }}" placeholder="Masukkan Nama Akun" required>
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Tipe Akun</label>
                                <select class="form-control" id="tipe_akun" name="tipe_akun" required>
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="revenue">Revenue</option>
                                    <option value="expense">Expense</option>
                                    <option value="equity">Equity</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Kode Akun</label>
                                <input type="text" name="kode_akun" id="kode_akun" class="form-control"
                                    value="{{ old('kode_akun') }}" placeholder="Masukkan Kode Akun" required>
                                @error('kode_akun')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Saldo Normal</label>
                                <select class="form-control" id="saldo_normal" name="saldo_normal" required>
                                    <option value="debit">Debit</option>
                                    <option value="kredit">Kredit</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Induk Akun (Opsional)</label>
                                <select class="form-control" id="parent_id" name="parent_id">
                                    <option value="">- Tidak Ada Induk -</option>
                                    @if (isset($akunKeuanganTanpaParent))
                                        @foreach ($akunKeuanganTanpaParent as $akun)
                                            <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3 shadow table-responsive rounded">
            <div class="d-flex justify-content-between mb-3">
                <!-- Search Form -->
                <form method="GET" action="{{ route('admin.akun_keuangan.index') }}" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Cari akun..."
                        value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i></button>
                </form>

                <!-- Per Page Dropdown -->
                <form method="GET" action="{{ route('admin.akun_keuangan.index') }}">
                    <select name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                                {{ $size }} per halaman
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a
                                href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'direction' => request('direction', 'asc') == 'asc' ? 'desc' : 'asc']) }}">
                                ID Account
                                @if (request('sort', 'id') == 'id')
                                    @if (request('direction', 'asc') == 'asc')
                                        <i class="bi bi-arrow-up"></i> <!-- Panah naik -->
                                    @else
                                        <i class="bi bi-arrow-down"></i> <!-- Panah turun -->
                                    @endif
                                @endif
                            </a>
                        </th>
                        <th>Nama Akun</th>
                        <th>Kode Akun</th>
                        <th>Tipe Akun</th>
                        <th>Induk Akun</th>
                        <th>Saldo Normal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($akunKeuangans as $akun)
                        <tr>
                            <td>{{ $akun->id }}</td>
                            <td>{{ $akun->nama_akun }}</td>
                            <td>{{ $akun->kode_akun }}</td>
                            <td>{{ $akun->tipe_akun }}</td>
                            <td>{{ optional($akun->parentAkun)->nama_akun ?? '-' }}</td>
                            <td>{{ $akun->saldo_normal }}</td>
                            <td class="d-flex text-end">
                                <a href="{{ route('admin.akun_keuangan.edit', $akun->id) }}"
                                    class="btn btn-warning btn-sm me-2">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form id="delete-form-{{ $akun->id }}"
                                    onsubmit="return confirmDelete(event, {{ $akun->id }})"
                                    action="{{ route('admin.akun_keuangan.destroy', $akun->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Data tidak ditemukan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="row align-items-center">
                <div class="text-end">
                    {{ $akunKeuangans->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function confirmDelete(event, akunId) {
            event.preventDefault(); // Mencegah submit otomatis

            Swal.fire({
                title: 'Yakin ingin menghapus akun ini?',
                text: "Data akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + akunId).submit();
                }
            });

            return false; // Mencegah submit sebelum konfirmasi
        }

        $(document).ready(function() {
            $("#akunForm").on("submit", function(event) {
                event.preventDefault(); // Mencegah form langsung submit

                var form = $(this);
                var formData = form.serialize();
                var method = form.find('input[name="_method"]').val() === "PUT" ? "POST" : "POST";

                $.ajax({
                    url: form.attr("action"),
                    type: "POST", // Laravel menerima _method=PUT sebagai simulasi PUT
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data akun keuangan berhasil disimpan.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });

                        $("#akunModal").modal("hide");
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';

                        if (errors) {
                            errorMessage = Object.values(errors).map(err => err[0]).join('<br>');
                        }

                        Swal.fire({
                            title: 'Gagal!',
                            html: errorMessage,
                            icon: 'error'
                        });

                        // Efek getaran jika terjadi error
                        $("#akunModal .modal-content").addClass("shake");
                        setTimeout(() => {
                            $("#akunModal .modal-content").removeClass("shake");
                        }, 500);
                    }
                });
            });
        });
    </script>
@endsection
@if (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '{{ session('error') }}'
        });
    </script>
@endif
@if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '{{ implode(', ', $errors->all()) }}'
        });
    </script>
@endif

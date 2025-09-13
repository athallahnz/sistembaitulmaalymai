@extends('layouts.app')
@section('title', 'Manajemen Akun Keuangan')

@section('content')
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
    <div class="container">
        <h2 class="mb-4">Daftar Akun Keuangan</h2>

        <!-- Button untuk membuka modal Create -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#akunModal">
            Tambah Akun Keuangan
        </button>

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
            <table class="table table-striped table-bordered" id="akunTable">
                <thead class="table-light">
                    <tr>
                        <th>ID Account</th>
                        <th>Nama Akun</th>
                        <th>Kode Akun</th>
                        <th>Tipe Akun</th>
                        <th>Induk Akun</th>
                        <th>Saldo Normal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#akunTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.akun_keuangan.datatable') }}",
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'nama_akun',
                        name: 'nama_akun'
                    },
                    {
                        data: 'kode_akun',
                        name: 'kode_akun'
                    },
                    {
                        data: 'tipe_akun',
                        name: 'tipe_akun'
                    },
                    {
                        data: 'parent',
                        name: 'parent'
                    },
                    {
                        data: 'saldo_normal',
                        name: 'saldo_normal'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });

        function confirmDelete(event, akunId) {
            event.preventDefault();

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

            return false;
        }
    </script>

    {{-- SweetAlert dari Session --}}
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
                html: `{!! implode('<br>', $errors->all()) !!}`
            });
        </script>
    @endif
@endpush

@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('content')
    <div class="container">
        <h2 class="mb-4">Manajemen Pengguna</h2>
        <!-- Button untuk membuka modal -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#userModal">
            Tambah User
        </button>

        <!-- Modal -->
        <div class="modal fade" id="userModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">Silahkan Isi data Pengguna Baru!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form Create -->
                        <form id="userForm" action="{{ route('users.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="mb-2">Nama</label>
                                <input type="text" name="name" class="form-control" placeholder="Masukkan Nama" required>
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Masukkan Email" required>
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Nomor</label>
                                <input type="text" name="nomor" class="form-control" placeholder="Masukkan Nomor" required>
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">PIN</label>
                                <input type="password" name="pin" class="form-control" placeholder="Masukkan PIN" required>
                            </div>
                            <div class="mb-3">
                                <label class="mb-2">Role</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="Admin">Admin</option>
                                    <option value="Ketua Yayasan">Ketua Yayasan</option>
                                    <option value="Bendahara">Bendahara</option>
                                    <option value="Manajer Keuangan">Manajer Keuangan</option>
                                    <option value="Bidang">Bidang</option>
                                </select>
                            </div>
                            <div class="mb-3" id="bidang_name_group" style="display: none;">
                                <label for="bidang_name" class="mb-2">Bidang Name</label>
                                <select class="form-control" id="bidang_name" name="bidang_name">
                                    @foreach($bidangs as $bidang)
                                        <option value="{{ $bidang->name }}">{{ $bidang->name }}</option>
                                    @endforeach
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
            <table id="users-table" class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Nomor</th>
                        <th>Role</th>
                        <th>Bidang</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <script>
            $(document).ready(function() {
                var table = $('.yajra-datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('admin.users.data') }}",
                    columns: [{
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'email',
                            name: 'email'
                        },
                        {
                            data: 'nomor',
                            name: 'nomor'
                        },
                        {
                            data: 'role',
                            name: 'role'
                        },
                        {
                            data: 'bidang_name',
                            name: 'bidang_name'
                        },
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false
                        },
                    ],
                    error: function(xhr, status, error) {
                        console.log(xhr.responseText); // Debugging error response
                    }
                });
            });

            document.getElementById('role').addEventListener('change', function() {
                var role = this.value;
                var bidangField = document.getElementById('bidang_name_group');

                // Jika role adalah Bidang, tampilkan kolom bidang_name
                if (role === 'Bidang') {
                    bidangField.style.display = 'block';
                } else {
                    bidangField.style.display = 'none';
                }
            });
        </script>


    </div>
@endsection
@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    let userId = this.getAttribute('data-id');

                    Swal.fire({
                        title: 'Yakin ingin menghapus?',
                        text: 'Data ini akan dihapus secara permanen!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('delete-form-' + userId).submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection

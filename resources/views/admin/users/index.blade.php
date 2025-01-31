@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('content')
    <div class="container">
        <h2 class="mb-4">Manajemen Pengguna</h2>
        <a href="{{ route('users.create') }}" class="btn btn-primary mb-3 shadow">Tambah User</a>

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
        </script>


    </div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
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


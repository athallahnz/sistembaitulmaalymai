@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Daftar Bidang</h2>

        <!-- Button Create -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#createModal">
            Tambah Bidang
        </button>

        {{-- ================================
        MODAL CREATE
    ================================= --}}
        <div class="modal fade" id="createModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content modal-dialog modal-centered">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Bidang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form id="createForm" action="{{ route('admin.add_bidangs.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label>Nama Bidang</label>
                                <input type="text" name="name" class="form-control" placeholder="Masukkan Nama Bidang..." required>
                            </div>

                            <div class="mb-3">
                                <label>Deskripsi</label>
                                <textarea name="description" class="form-control" placeholder="Masukkan Keterangan Bidang..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        {{-- ================================
        MODAL EDIT
    ================================= --}}
        <div class="modal fade" id="editModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content modal-dialog modal-centered">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Bidang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form id="editForm" method="POST">
                            @csrf
                            @method('PUT')

                            <input type="hidden" id="edit_id">

                            <div class="mb-3">
                                <label>Nama Bidang</label>
                                <input type="text" id="edit_name" name="name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>Deskripsi</label>
                                <textarea id="edit_description" name="description" class="form-control"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        {{-- ================================
        TABLE
    ================================= --}}
        <div class="p-3 shadow table-responsive rounded">
            <table class="table table-bordered yajra-datatable">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection


@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(function() {
            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.add_bidangs.data') }}",
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            // Klik tombol Edit
            $('body').on('click', '.btn-edit', function() {
                let id = $(this).data('id');
                let url = "{{ route('admin.add_bidangs.edit', ':id') }}".replace(':id', id);

                $.get(url, function(res) {
                    $('#edit_id').val(res.id);
                    $('#edit_name').val(res.name);
                    $('#edit_description').val(res.description);

                    let updateUrl = "{{ route('admin.add_bidangs.update', ':id') }}".replace(':id',
                        id);
                    $('#editForm').attr('action', updateUrl);

                    $('#editModal').modal('show');
                }).fail(function(xhr) {
                    console.log('ERROR EDIT:', xhr.responseText);
                });
            });
        });
    </script>
@endpush

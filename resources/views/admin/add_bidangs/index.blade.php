@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Daftar Bidang</h2>
        <!-- Button untuk membuka modal Create -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#akunModal">
            Tambah Bidang
        </button>
        <!-- Modal -->
        <div class="modal fade" id="akunModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="akunModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="akunModalLabel">Silahkan Isi Data Bidang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form Create -->
                        <form id="akunForm" action="{{ route('admin.add_bidangs.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="name">Nama Bidang</label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $bidang->name ?? '') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="description">Deskripsi</label>
                                <textarea name="description" class="form-control">{{ old('description', $bidang->description ?? '') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <script>
        $(document).ready(function() {
            $('.yajra-datatable').DataTable({
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
                    }
                ],
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                }
            });
        });
    </script>
@endpush

@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Daftar Piutang <strong>Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}</strong></h1>
        <a href="{{ route('piutangs.create') }}" class="btn btn-primary mb-3"><i class="bi bi-plus-circle"></i> Catat
            Piutang!</a>
        <div class="p-3 shadow table-responsive rounded">
            <table id="transaksi-table" class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Nama Penghutang</th>
                        <th>Jumlah</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('piutangs.data') }}",
                    type: "GET",
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'user_name',
                        name: 'user.name'
                    },
                    {
                        data: 'jumlah_formatted',
                        name: 'jumlah'
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi'
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });
        $(document).ready(function() {
            $(document).on("click", ".delete-btn", function() {
                var transaksiId = $(this).data("id");
                Swal.fire({
                    title: "Apakah Anda yakin?",
                    text: "Data ini akan dihapus dan tidak dapat dikembalikan!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Ya, hapus!",
                    cancelButtonText: "Batal"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $("#delete-form-" + transaksiId).submit();
                    }
                });
            });
        });
    </script>
@endsection

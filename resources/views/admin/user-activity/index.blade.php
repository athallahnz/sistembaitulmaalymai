@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-3">User Activity Log</h3>

    <table id="user-activity-table" class="table table-bordered yajra-datatable">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama Pengguna</th>
                <th>Aktivitas</th>
                <th>IP Address</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTables akan menambahkan baris di sini secara otomatis -->
        </tbody>
    </table>

    {{ $logs->links() }}
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        var table = $('#user-activity-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.user-activity.data') }}", // Rute baru untuk mengambil data
            columns: [
                { data: 'id', name: 'id' },
                { data: 'user_id', name: 'user_id' },
                { data: 'activity', name: 'activity' },
                { data: 'ip_address', name: 'ip_address' },
                { data: 'created_at', name: 'created_at' }
            ],
            error: function(xhr, status, error) {
                console.log(xhr.responseText); // Debugging error response
            }
        });
    });
</script>
@endsection

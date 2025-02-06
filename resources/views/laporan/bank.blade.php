@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="card" style="width: 20rem;">
                <h5>Konsolidasi Saldo Bank</h5>
                <div class="icon bi bi-bank"></div>
                <div class="value {{ $totalSaldoBank >= 0 ? 'positive' : 'negative' }}">Rp.{{ number_format($totalSaldoBank, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="p-3 shadow table-responsive rounded">
            <table class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode Transaksi</th>
                        <th>Deskripsi</th>
                        <th>Debit</th>
                        <th>Credit</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('laporan.bank.data') }}",
                columns: [{
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'kode_transaksi',
                        name: 'kode_transaksi'
                    },
                    {
                        data: 'akun_nama',
                        name: 'akun_nama'
                    },
                    {
                        data: 'debit',
                        name: 'debit'
                    },
                    {
                        data: 'credit',
                        name: 'credit'
                    },
                ],
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        });
    </script>
@endpush

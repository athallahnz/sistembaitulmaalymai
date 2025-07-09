@extends('layouts.app')
@section('title', 'Catatan Buku Harian')
@section('content')
    <div class="container">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ url()->previous() }}" class="me-1 text-decoration-none">
                <i class="bi bi-arrow-left-short fs-1"></i>
            </a>
            <h1 class="mb-0">
                Detail Transaksi:
                <strong>
                    @if ($parentAkun?->nama_akun && $type)
                        {{ $parentAkun->nama_akun }} ({{ ucfirst($type) }})
                    @elseif ($parentAkun?->nama_akun)
                        {{ $parentAkun->nama_akun }}
                    @elseif ($type)
                        {{ ucfirst($type) }}
                    @else
                        Tidak Ditemukan
                    @endif
                </strong>
            </h1>

        </div>
        <div class="p-3 shadow table-responsive rounded">
            <table id="transaksi-table" class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode Transaksi</th>
                        <th>Jenis Transaksi</th>
                        <th>Akun</th>
                        <th>Sub Akun</th>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('bidang.detail.data') }}",
                    data: function(d) {
                        d.parent_akun_id = @json($parentAkunId);
                        d.type = @json($type);
                    }
                },
                columns: [{
                        data: 'tanggal_transaksi',
                        name: 'tanggal_transaksi'
                    },
                    {
                        data: 'kode_transaksi',
                        name: 'kode_transaksi'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'akun_keuangan',
                        name: 'akun_keuangan'
                    },
                    {
                        data: 'parent_akun_keuangan',
                        name: 'parent_akun_keuangan'
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        render: function(data, type, row) {
                            return number_format(data); // Format debit
                        }
                    }
                ]
            });
        });

        function number_format(number, decimals = 0, dec_point = ',', thousands_sep = '.') {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                s = '',
                toFixedFix = function(n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }
    </script>
@endsection

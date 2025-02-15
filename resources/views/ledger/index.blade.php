@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="card" style="width: 20rem;">
                <h5>Saldo Kas</h5>
                <div class="icon bi bi-cash"></div>
                <div class="value {{ $totalKas >= 0 ? 'positive' : 'negative' }}">Rp. {{ number_format($totalKas, 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="p-3 shadow table-responsive rounded">
            <table class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode Transaksi</th>
                        <th>Akun</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('.yajra-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('ledger.data') }}", // Sesuaikan dengan route untuk mengambil data ledger
            columns: [{
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'kode_transaksi',
                    name: 'kode_transaksi',
                    render: function(data, type, row) {
                        return row.transaksi ? row.transaksi.kode_transaksi : 'N/A';
                    }
                },
                {
                    data: 'akun_nama',
                    name: 'akun_nama',
                    render: function(data, type, row) {
                        return row.akun_keuangan ? row.akun_keuangan.nama_akun : 'N/A';
                    }
                },
                {
                    data: 'debit',
                    name: 'debit',
                    render: function(data, type, row) {
                        return number_format(data, 2); // Format angka untuk debit
                    }
                },
                {
                    data: 'credit',
                    name: 'credit',
                    render: function(data, type, row) {
                        return number_format(data, 2); // Format angka untuk kredit
                    }
                },
                // {
                //     data: 'saldo',
                //     name: 'saldo',
                //     render: function(data, type, row) {
                //         return 'Rp ' + number_format(data, 2, ',',
                //             '.'); // Format angka untuk saldo
                //     }
                // }
            ],
            error: function(xhr, status, error) {
                console.log(xhr.responseText); // Debugging error response
            }
        });
    });

    // Function to format numbers with thousand separators
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

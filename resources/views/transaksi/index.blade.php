@extends('layouts.app')
@section('title', 'Catatan Buku Harian')
@section('content')
    <style>
        .btn-outline-success,
        .btn-outline-danger {
            transition: all 0.2s ease-in-out;
        }

        #penerimaan:not(:checked)+.btn-outline-success:hover {
            background-color: #198754;
            color: #fff;
            border-color: #198754;
            box-shadow: 0 0 0.3rem rgba(25, 135, 84, 0.4);
        }

        #pengeluaran:not(:checked)+.btn-outline-danger:hover {
            background-color: #dc3545;
            color: #fff;
            border-color: #dc3545;
            box-shadow: 0 0 0.3rem rgba(220, 53, 69, 0.4);
        }

        #penerimaan:checked+.btn-outline-success {
            background-color: #198754;
            color: #fff;
            border-color: #198754;
            box-shadow: 0 0 0.4rem rgba(25, 135, 84, 0.5);
        }

        #pengeluaran:checked+.btn-outline-danger {
            background-color: #dc3545;
            color: #fff;
            border-color: #dc3545;
            box-shadow: 0 0 0.4rem rgba(220, 53, 69, 0.5);
        }
    </style>

    <div class="container">
        <h1 class="mb-4">
            @if (auth()->user()->hasRole('Bidang'))
                Data Buku Harian <strong>Bidang {{ auth()->user()->bidang->name ?? 'Tidak Ada' }}</strong>
            @elseif(auth()->user()->hasRole('Bendahara'))
                Seluruh Data Transaksi Buku Harian <strong>Bidang</strong>
            @endif
        </h1>

        <!-- Button untuk membuka modal -->
        <button type="button" class="btn btn-primary mb-3 me-2 shadow" data-bs-toggle="modal"
            data-bs-target="#transactionModal">
            <i class="bi bi-plus-circle"></i> Tambah Transaksi Kas
        </button>
        <a href="{{ route('transaksi.exportAllPdf') }}" class="btn btn-danger mb-3 me-2 shadow">
            <i class="bi bi-filetype-pdf"></i> Unduh PDF
        </a>
        <a href="{{ route('transaksi.exportExcel') }}" class="btn btn-success mb-3 shadow">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>

        <!-- Modal -->
        <div class="modal fade" id="transactionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="transactionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transactionModalLabel">Silahkan Isi Data Transaksi Baru!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form Create Transaksi -->
                        <form action="{{ route('transaksi.store') }}" method="POST">
                            @csrf
                            <div class="mb-3 d-none">
                                <label class="mb-2">Bidang</label>
                                @if (auth()->user()->role === 'Bendahara')
                                    <input type="text" name="bidang_name" class="form-control" value="Tidak Ada"
                                        readonly>
                                    <small class="form-text text-muted">Role Bendahara tidak memiliki bidang.</small>
                                @else
                                    <input type="text" name="bidang_name" class="form-control"
                                        value="{{ auth()->user()->bidang_name }}" readonly>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="kode_transaksi" class="form-label mb-2">Kode Transaksi</label>
                                <input type="text" class="form-control" id="kode_transaksi" name="kode_transaksi"
                                    value="{{ $kodeTransaksi }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Tanggal Transaksi</label>
                                <input type="date" name="tanggal_transaksi" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">Tipe Transaksi</label>
                                <div class="d-flex gap-2" id="transaction-type-buttons">
                                    <input type="radio" class="btn-check" name="type" id="penerimaan"
                                        value="penerimaan" autocomplete="off" required>
                                    <label class="btn btn-outline-success" for="penerimaan">Penerimaan</label>

                                    <input type="radio" class="btn-check" name="type" id="pengeluaran"
                                        value="pengeluaran" autocomplete="off" required>
                                    <label class="btn btn-outline-danger" for="pengeluaran">Pengeluaran</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2" id="akun-label">Asal Akun</label> <!-- Label dinamis -->
                                <select class="form-control" name="akun_keuangan_id" id="akun_keuangan" required>
                                    <option value="">Pilih Akun</option>
                                    @foreach ($akunTanpaParent as $akun)
                                        <option value="{{ $akun->id }}" data-saldo-normal="{{ $akun->saldo_normal }}">
                                            {{ $akun->nama_akun }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3" id="parent-akun-container" style="display: none;">
                                <label class="mb-2">Akun Parent</label>
                                <select class="form-control" name="parent_akun_id" id="parent_akun_id">
                                    <option value="">Pilih Akun Parent</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Deskripsi Transaksi</label>
                                <input type="text" name="deskripsi" class="form-control" placeholder="Masukkan Deskripsi"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">Jumlah</label>
                                <input type="text" id="formattedAmount" class="form-control"
                                    oninput="formatInput(this)">
                                <input type="number" name="amount" id="amount" class="form-control d-none">
                                <small class="form-text text-muted" id="saldo-akun">
                                    Saldo Kas: Rp {{ number_format($saldoKas, 2, ',', '.' ?? 0) }}
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3 shadow table-responsive rounded">
            <table id="transaksi-table" class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode T.</th>
                        <th>Jenis T.</th>
                        <th>Akun</th>
                        <th>Sub Akun</th>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const typeSelect = document.getElementById("type-select");
            const akunLabel = document.getElementById("akun-label");

            // Fungsi untuk mengubah label berdasarkan tipe transaksi
            function updateAkunLabel() {
                const selectedType = typeSelect.value;
                if (selectedType === "penerimaan") {
                    akunLabel.textContent = "Asal Akun";
                } else if (selectedType === "pengeluaran") {
                    akunLabel.textContent = "Tujuan Akun";
                }
            }

            // Event listener untuk mendeteksi perubahan tipe transaksi
            typeSelect.addEventListener("change", updateAkunLabel);

            // Set label awal sesuai nilai default
            updateAkunLabel();
        });

        document.addEventListener("DOMContentLoaded", function() {
            let akunKeuangan = document.getElementById("akun_keuangan");
            let parentAkunContainer = document.getElementById("parent-akun-container");
            let parentAkunSelect = document.getElementById("parent_akun_id");

            let akunDenganParent = @json($akunDenganParent);

            akunKeuangan.addEventListener("change", function() {
                let selectedAkunId = this.value;
                parentAkunSelect.innerHTML = '<option value="">Pilih Akun Parent</option>';

                if (selectedAkunId && akunDenganParent[selectedAkunId]) {
                    akunDenganParent[selectedAkunId].forEach(akun => {
                        let newOption = document.createElement("option");
                        newOption.value = akun.id;
                        newOption.textContent = akun.nama_akun;
                        parentAkunSelect.appendChild(newOption);
                    });
                    parentAkunContainer.style.display = "block";
                } else {
                    parentAkunContainer.style.display = "none";
                }

                // Menyesuaikan nilai debit atau kredit berdasarkan saldo_normal
                updateFormByAkun(akunKeuangan);
            });
        });

        $(document).ready(function() {
            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.data') }}", // Sesuaikan dengan route yang sesuai untuk mengambil data transaksi
                columns: [{
                        data: 'tanggal_transaksi', // Ambil tanggal transaksi
                        name: 'tanggal_transaksi'
                    },
                    {
                        data: 'kode_transaksi', // Ambil kode transaksi
                        name: 'kode_transaksi'
                    },
                    {
                        data: 'type', // Ambil type
                        name: 'type',
                        render: function(data, type, row) {
                            // Custom rendering based on the type value
                            if (data === 'penerimaan') {
                                return '<span class="badge bg-success">Penerimaan</span>'; // Green label
                            } else if (data === 'pengeluaran') {
                                return '<span class="badge bg-danger">Pengeluaran</span>'; // Red label
                            } else if (data === 'pendapatan belum diterima') {
                                return '<span class="badge bg-warning">Belum Diterima</span>'; // Yellow label
                            } else {
                                return '<span class="badge bg-secondary">Unknown</span>'; // Grey label for unknown
                            }
                        }
                    },
                    {
                        data: 'akun_keuangan_id', // Ambil nama akun yang terkait
                        name: 'akun_keuangan_id',
                        render: function(data, type, row) {
                            return row.akun_keuangan ? row.akun_keuangan.nama_akun :
                                'N/A'; // Menampilkan nama akun keuangan
                        }
                    },
                    {
                        data: 'parent_akun_id', // Ambil parent_akun_id dari data transaksi
                        name: 'parent_akun_id',
                        render: function(data, type, row) {
                            return row.parent_akun_keuangan ? row.parent_akun_keuangan.nama_akun :
                                'N/A'; // Menampilkan nama akun keuangan
                        }
                    },
                    {
                        data: 'deskripsi', // Ambil deskripsi dari data transaksi
                        name: 'deskripsi'
                    },
                    {
                        data: 'amount', // Ambil amount
                        name: 'amount',
                        render: function(data, type, row) {
                            return number_format(data); // Format debit
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                error: function(xhr, status, error) {
                    console.log(xhr.responseText); // Debugging error response
                }
            });
        });

        function formatInput(input) {
            let rawValue = input.value.replace(/\D/g, ""); // Hanya angka
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);

            input.value = formatted; // Tampilkan angka dengan separator
            document.getElementById("amount").value = rawValue; // Simpan angka asli tanpa separator
        }

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

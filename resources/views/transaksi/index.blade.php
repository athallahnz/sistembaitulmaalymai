@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('content')
    <div class="container">
        <h1 class="mb-4">Data Transaksi Keuangan Bidang {{ auth()->user()->bidang_name }}</h1>

        <!-- Button untuk membuka modal -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#transactionModal">
            Tambah Transaksi
        </button>

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
                                <input type="text" name="bidang_name" class="form-control"
                                    value="{{ auth()->user()->bidang_name }}" readonly>
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
                                <label class="mb-2">Jenis Akun</label>
                                <select class="form-control" name="akun_keuangan_id" id="akun_keuangan" required>
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

                            <div id="debit-container" class="mb-3">
                                <label class="mb-2">Debit</label>
                                <input type="number" name="debit" id="debit" class="form-control"
                                    value="{{ old('debit', 0) }}" required>
                                <input type="hidden" name="debit_hidden" id="debit_hidden" value="{{ old('debit', 0) }}">
                            </div>

                            <div id="kredit-container" class="mb-3">
                                <label class="mb-2">Kredit</label>
                                <input type="number" name="kredit" id="kredit" class="form-control"
                                    value="{{ old('kredit', 0) }}" required>
                                <input type="hidden" name="kredit_hidden" id="kredit_hidden"
                                    value="{{ old('kredit', 0) }}">
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Saldo</label>
                                <input type="number" name="saldo" class="form-control"
                                    value="{{ old('saldo', $lastSaldo) }}" placeholder="Masukkan Saldo" readonly>
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
                        <th>Kode Transaksi</th>
                        <th>Akun</th>
                        <th>Jenis Transaksi</th>
                        <th>Deskripsi</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
    <script>
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

        document.addEventListener("DOMContentLoaded", function() {
            let akunKeuangan = document.getElementById("akun_keuangan");
            let debitContainer = document.getElementById("debit-container");
            let kreditContainer = document.getElementById("kredit-container");
            let debitInput = document.getElementById("debit");
            let kreditInput = document.getElementById("kredit");
            let debitHidden = document.getElementById("debit_hidden");
            let kreditHidden = document.getElementById("kredit_hidden");

            akunKeuangan.addEventListener("change", function() {
                let selectedOption = akunKeuangan.options[akunKeuangan.selectedIndex];
                let saldoNormal = selectedOption.getAttribute("data-saldo-normal");

                if (saldoNormal === "debit") {
                    debitContainer.style.display = "block"; // Tampilkan Debit
                    kreditContainer.style.display = "none"; // Sembunyikan Kredit
                    kreditInput.value = 0; // Reset Kredit
                    kreditHidden.value = 0; // Pastikan Hidden Input juga direset
                } else if (saldoNormal === "kredit") {
                    kreditContainer.style.display = "block"; // Tampilkan Kredit
                    debitContainer.style.display = "none"; // Sembunyikan Debit
                    debitInput.value = 0; // Reset Debit
                    debitHidden.value = 0; // Pastikan Hidden Input juga direset
                } else {
                    debitContainer.style.display = "block"; // Tampilkan Debit
                    kreditContainer.style.display = "block"; // Tampilkan Kredit
                }
            });

            // Set nilai hidden sebelum form dikirim
            document.querySelector("form").addEventListener("submit", function() {
                debitHidden.value = debitInput.value;
                kreditHidden.value = kreditInput.value;
            });

            // Jalankan saat halaman pertama kali dimuat
            akunKeuangan.dispatchEvent(new Event("change"));
        });

        $(document).ready(function() {
            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.data') }}", // Sesuaikan dengan route yang sesuai untuk mengambil data transaksi
                columns: [
                    // {
                    //     data: 'bidang_name', // Ambil bidang_name dari data transaksi
                    //     name: 'bidang_name'
                    // },
                    {
                        data: 'tanggal_transaksi', // Ambil tanggal transaksi
                        name: 'tanggal_transaksi'
                    },
                    {
                        data: 'kode_transaksi', // Ambil kode transaksi
                        name: 'kode_transaksi'
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
                        data: 'debit', // Ambil debit
                        name: 'debit',
                        render: function(data) {
                            return new Intl.NumberFormat().format(data); // Format angka untuk debit
                        }
                    },
                    {
                        data: 'kredit', // Ambil kredit
                        name: 'kredit',
                        render: function(data) {
                            return new Intl.NumberFormat().format(
                                data); // Format angka untuk kredit
                        }
                    },
                    {
                        data: 'saldo', // Ambil saldo
                        name: 'saldo',
                        render: function(data) {
                            return new Intl.NumberFormat().format(data); // Format angka untuk saldo
                        }
                    },
                ],
                error: function(xhr, status, error) {
                    console.log(xhr.responseText); // Debugging error response
                }
            });
        });
    </script>


@endsection

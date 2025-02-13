@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="card" style="width: 20rem;">
                <h5>Konsolidasi Saldo Bank</h5>
                <div class="icon bi bi-bank"></div>
                <div class="value {{ $totalSaldoBank >= 0 ? 'positive' : 'negative' }}">
                    Rp.{{ number_format($totalSaldoBank, 2, ',', '.') }}</div>
            </div>
        </div>

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
                        <form action="{{ route('transaksi.storeBankTransaction') }}" method="POST">
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
                                <label class="form-label mb-2">Tipe Transaksi</label>
                                <select name="type" class="form-control" id="type-select" required>
                                    <option value="penerimaan">Penerimaan</option>
                                    <option value="pengeluaran">Pengeluaran</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2" id="akun-label">Asal Akun</label>
                                <!-- Menampilkan nama akun secara statis -->
                                <input type="hidden" name="akun_keuangan_id" value="102"> <!-- Nilai tetap untuk akun keuangan -->
                                <div class="form-control bg-light" readonly>
                                    Bank
                                </div>
                                <small class="form-text text-muted" id="saldo-akun">
                                    Saldo Akun: Rp {{ number_format($lastSaldos[102] ?? 0, 2) }}
                                </small>
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
                                <input type="number" name="amount" class="form-control" id="amount" required>
                                <small class="form-text text-danger" id="amount-error" style="display: none;">Jumlah
                                    pengeluaran tidak boleh melebihi saldo akun.</small>
                            </div>

                            <button type="submit" class="btn btn-primary" id="submit-btn">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
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
        document.getElementById('akun_keuangan').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const saldo = parseFloat(selectedOption.getAttribute('data-saldo-normal')) || 0;
            document.getElementById('saldo-akun').textContent = `Saldo Akun: Rp ${saldo.toFixed(2)}`;
            checkAmountValidity(saldo);
        });

        document.getElementById('amount').addEventListener('input', function() {
            const selectedOption = document.getElementById('akun_keuangan').options[document.getElementById(
                'akun_keuangan').selectedIndex];
            const saldo = parseFloat(selectedOption.getAttribute('data-saldo-normal')) || 0;
            const amount = parseFloat(this.value) || 0;
            checkAmountValidity(saldo, amount);
        });

        function checkAmountValidity(saldo, amount) {
            const errorMessage = document.getElementById('amount-error');
            const submitButton = document.getElementById('submit-btn');
            const type = document.getElementById('type-select').value; // Mendapatkan nilai type transaksi

            // Menyembunyikan error message jika tipe transaksi adalah 'penerimaan'
            if (type === 'penerimaan') {
                errorMessage.style.display = 'none';
                submitButton.disabled = false; // Tetap aktifkan tombol simpan
            } else {
                // Menampilkan error message hanya untuk tipe 'pengeluaran'
                if (amount > saldo) {
                    errorMessage.style.display = 'block';
                    submitButton.disabled = true; // Nonaktifkan tombol simpan
                } else {
                    errorMessage.style.display = 'none';
                    submitButton.disabled = false; // Aktifkan tombol simpan
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectedOption = document.getElementById('akun_keuangan').options[document.getElementById(
                'akun_keuangan').selectedIndex];
            const saldo = parseFloat(selectedOption.getAttribute('data-saldo-normal')) || 0;
            document.getElementById('saldo-akun').textContent = `Saldo Akun: Rp ${saldo.toFixed(2)}`;
            checkAmountValidity(saldo);
        });
    </script>
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

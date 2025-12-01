@extends('layouts.app')
@section('title', 'Buku Besar Kas')
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
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="card" style="width: 20rem;">
                <h5>Saldo Kas</h5>
                <div class="icon bi bi-cash"></div>
                <div class="value {{ $saldoKas >= 0 ? 'positive' : 'negative' }}">
                    Rp <span class="hidden-value" style="display: none;">{{ number_format($saldoKas, 0, ',', '.') }}</span>
                    <span class="masked-value">***</span>
                    <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;"
                        onclick="toggleVisibility(this)"></i>
                </div>
            </div>
        </div>

        <!-- Button untuk membuka modal -->
        <button type="button" class="btn btn-primary mb-3 me-2 shadow" data-bs-toggle="modal"
            data-bs-target="#transactionModal">
            <i class="bi bi-plus-circle"></i> Tambah Transaksi Kas
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
                                <input type="text" name="deskripsi" class="form-control"
                                    placeholder="Masukkan Deskripsi" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">Jumlah</label>
                                <input type="text" id="formattedAmount" class="form-control"
                                    oninput="formatInput(this)" required>
                                <input type="number" name="amount" id="amount" class="form-control d-none" required>
                                <small class="form-text text-muted" id="saldo-akun">
                                    Saldo Kas: Rp {{ number_format($saldoKas, 2, ',', '.') }}
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
            <table class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Akun Sumber</th>
                        <th>Akun Tujuan</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Data dari backend (akunTanpaParent & akunDenganParent)
        const akunTanpaParent = @json($akunTanpaParent);
        const akunDenganParent = @json($akunDenganParent);

        function populateAkunSelect(type) {
            const select = document.getElementById('akun_keuangan');
            const akunLabel = document.getElementById('akun-label');

            if (!select) return;

            // Reset option
            select.innerHTML = '<option value="">Pilih Akun</option>';

            // Filter tipe_akun
            const filterType = type === 'penerimaan' ? 'revenue' : 'expense';

            // Tambahkan option sesuai tipe
            akunTanpaParent
                .filter(a => a.tipe_akun === filterType)
                .forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a.id;
                    opt.textContent = (a.kode_akun ? a.kode_akun + ' - ' : '') + a.nama_akun;
                    opt.dataset.saldoNormal = a.saldo_normal;
                    select.appendChild(opt);
                });

            // Reset parent-akun
            const parentContainer = document.getElementById('parent-akun-container');
            const parentSelect = document.getElementById('parent_akun_id');
            if (parentSelect && parentContainer) {
                parentSelect.innerHTML = '<option value="">Pilih Akun Parent</option>';
                parentContainer.style.display = 'none';
            }
        }

        function populateChildAkun(parentId) {
            const parentContainer = document.getElementById('parent-akun-container');
            const parentSelect = document.getElementById('parent_akun_id');
            if (!parentSelect || !parentContainer) return;

            parentSelect.innerHTML = '<option value="">Pilih Akun Parent</option>';

            const children = akunDenganParent[parentId] || akunDenganParent[String(parentId)];

            if (children && children.length > 0) {
                children.forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a.id;
                    opt.textContent = (a.kode_akun ? a.kode_akun + ' - ' : '') + a.nama_akun;
                    parentSelect.appendChild(opt);
                });
                parentContainer.style.display = 'block';
            } else {
                parentContainer.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const penerimaanRadio = document.getElementById('penerimaan');
            const pengeluaranRadio = document.getElementById('pengeluaran');
            const akunSelect = document.getElementById('akun_keuangan');

            // ========= Label Asal / Tujuan =========
            const akunLabel = document.getElementById("akun-label");
            const typeRadios = document.querySelectorAll('input[name="type"]');

            function updateAkunLabel() {
                if (!akunLabel) return;
                const checked = document.querySelector('input[name="type"]:checked');
                if (!checked) {
                    akunLabel.textContent = "Asal Akun";
                    return;
                }
                akunLabel.textContent = (checked.value === "penerimaan") ?
                    "Asal Akun" :
                    "Tujuan Akun";
            }

            typeRadios.forEach(r => r.addEventListener("change", () => {
                updateAkunLabel();
            }));

            // ========= Radio: Penerimaan / Pengeluaran =========
            if (penerimaanRadio) {
                penerimaanRadio.addEventListener('change', function() {
                    if (this.checked) {
                        populateAkunSelect('penerimaan');
                        updateAkunLabel();
                    }
                });
            }

            if (pengeluaranRadio) {
                pengeluaranRadio.addEventListener('change', function() {
                    if (this.checked) {
                        populateAkunSelect('pengeluaran');
                        updateAkunLabel();
                    }
                });
            }

            // ========= Change akun_keuangan → load children =========
            if (akunSelect) {
                akunSelect.addEventListener('change', function() {
                    const parentId = this.value;
                    if (parentId) {
                        populateChildAkun(parentId);
                    } else {
                        const parentContainer = document.getElementById('parent-akun-container');
                        const parentSelect = document.getElementById('parent_akun_id');
                        if (parentSelect && parentContainer) {
                            parentSelect.innerHTML = '<option value="">Pilih Akun Parent</option>';
                            parentContainer.style.display = 'none';
                        }
                    }
                });
            }

            // Set label awal
            updateAkunLabel();
        });

        $(document).ready(function() {
            $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('ledger.data') }}",
                columns: [{
                        data: 'tanggal',
                        name: 'tanggal'
                    },
                    {
                        data: 'akun_sumber',
                        name: 'akun_sumber'
                    },
                    {
                        data: 'akun_tujuan',
                        name: 'akun_tujuan'
                    },
                    {
                        data: 'debit',
                        name: 'debit'
                    },
                    {
                        data: 'kredit',
                        name: 'kredit'
                    },
                ]
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

        function toggleVisibility(icon) {
            let parent = icon.closest('.card');
            let hiddenValue = parent.querySelector('.hidden-value');
            let maskedValue = parent.querySelector('.masked-value');

            if (hiddenValue.style.display === 'none') {
                hiddenValue.style.display = 'inline';
                maskedValue.style.display = 'none';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                hiddenValue.style.display = 'none';
                maskedValue.style.display = 'inline';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        function formatInput(input) {
            let rawValue = input.value.replace(/\D/g, "");
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);

            input.value = formatted;
            document.getElementById("amount").value = rawValue;
        }
    </script>
@endpush

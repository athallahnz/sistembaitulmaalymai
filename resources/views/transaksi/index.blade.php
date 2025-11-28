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

        <!-- Button untuk membuka modal Opening Balance -->
        <button type="button" class="btn btn-warning mb-3 me-2 shadow" id="btn-opening-balance" data-bs-toggle="modal"
            data-bs-target="#transactionModal">
            <i class="bi bi-box-arrow-in-down"></i> Opening Balance
        </button>

        <button type="button" class="btn btn-primary mb-3 me-2 shadow" data-bs-toggle="modal"
            data-bs-target="#transferModal">
            <i class="bi bi-arrow-left-right"></i> Transfer Kas / Bank
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
                        <h5 class="modal-title" id="transactionModalLabel">Input Saldo Awal (Opening Balance)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form Opening Balance -->
                        <form action="{{ route('transaksi.opening-balance.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="is_opening_balance" id="is_opening_balance" value="1">

                            {{-- Kode & Tanggal --}}
                            <div class="mb-3">
                                <label for="kode_transaksi" class="form-label mb-2">Kode Transaksi</label>
                                <input type="text" class="form-control" id="kode_transaksi" name="kode_transaksi"
                                    value="{{ $kodeTransaksi }}" readonly>
                            </div>

                            {{-- PILIH KAS / BANK YANG AKAN DIDEbit --}}
                            <div class="mb-3">
                                <label class="mb-2 d-block">Pilih Akun Kas / Bank yang Akan Didebit</label>
                                <div class="row g-2">
                                    @if ($kasAkun)
                                        <div class="col-md-6">
                                            <div class="form-check border rounded p-2 h-100">
                                                <input class="form-check-input" type="radio" name="kas_bank_akun_id"
                                                    id="kasRadio" value="{{ $kasAkun->id }}" checked>
                                                <label class="form-check-label w-100" for="kasRadio">
                                                    <div class="fw-bold">{{ $kasAkun->kode_akun }} —
                                                        {{ $kasAkun->nama_akun }}</div>
                                                    <div><small class="text-muted">Saldo saat ini:
                                                            Rp {{ number_format($saldoKas, 0, ',', '.') }}</small></div>
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($bankAkun)
                                        <div class="col-md-6">
                                            <div class="form-check border rounded p-2 h-100">
                                                <input class="form-check-input" type="radio" name="kas_bank_akun_id"
                                                    id="bankRadio" value="{{ $bankAkun->id }}"
                                                    {{ !$kasAkun ? 'checked' : '' }}>
                                                <label class="form-check-label w-100" for="bankRadio">
                                                    <div class="fw-bold">{{ $bankAkun->kode_akun }} —
                                                        {{ $bankAkun->nama_akun }}</div>
                                                    <div><small class="text-muted">Saldo saat ini:
                                                            Rp {{ number_format($saldoBank, 0, ',', '.') }}</small></div>
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>



                            <div class="mb-3">
                                <label class="mb-2">Tanggal Transaksi</label>
                                <input type="date" name="tanggal_transaksi" class="form-control" required>
                            </div>

                            {{-- Tipe Transaksi tidak dipakai di OB --}}
                            <div class="mb-3 d-none">
                                <label class="form-label mb-2">Tipe Transaksi</label>
                                <div class="d-flex gap-2" id="transaction-type-buttons">
                                    <input type="radio" class="btn-check" name="type" id="penerimaan"
                                        value="penerimaan" autocomplete="off" checked>
                                    <label class="btn btn-outline-success" for="penerimaan">Penerimaan</label>

                                    <input type="radio" class="btn-check" name="type" id="pengeluaran"
                                        value="pengeluaran" autocomplete="off">
                                    <label class="btn btn-outline-danger" for="pengeluaran">Pengeluaran</label>
                                </div>
                            </div>

                            {{-- INDUK ASET NETO --}}
                            <div class="mb-3">
                                <label class="form-label mb-2" id="akun-label">Induk Aset Neto</label>
                                <select class="form-control" name="akun_keuangan_id" id="akun_keuangan" required>
                                    <option value="">Pilih Induk Aset Neto</option>
                                    @foreach ($akunTanpaParent as $akun)
                                        <option value="{{ $akun->id }}">
                                            {{ $akun->kode_akun }} — {{ $akun->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- ANAK ASET NETO (opsional) --}}
                            <div class="mb-3" id="parent-akun-container" style="display: none;">
                                <label class="mb-2">Anak Aset Neto</label>
                                <select class="form-control" name="parent_akun_id" id="parent_akun_id">
                                    <option value="">Pilih Anak Aset Neto</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Deskripsi Transaksi</label>
                                <input type="text" name="deskripsi" class="form-control"
                                    placeholder="Saldo awal per tanggal ..." required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">Jumlah</label>
                                <input type="text" id="formattedAmount" class="form-control"
                                    oninput="formatInput(this)">
                                <input type="number" name="amount" id="amount" class="form-control d-none">
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Transfer Antar Kas / Bank / Bidang --}}
        <div class="modal fade" id="transferModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="transferModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transferModalLabel">Transfer Antar Kas / Bank / Bidang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Tampilkan error global kalau ada --}}
                        @if ($errors->any() && old('is_transfer'))
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $msg)
                                        <li>{{ $msg }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('transaksi.transfer.store') }}" method="POST">
                            @csrf

                            {{-- Flag khusus transfer --}}
                            <input type="hidden" name="is_transfer" value="1">

                            {{-- KODE & TANGGAL --}}
                            <div class="mb-3">
                                <label for="kode_transaksi_transfer" class="form-label mb-2">Kode Transaksi</label>
                                <input type="text" class="form-control" id="kode_transaksi_transfer"
                                    name="kode_transaksi"
                                    value="{{ old('kode_transaksi', $kodeTransaksiTransfer ?? ($kodeTransaksi ?? '')) }}"
                                    readonly>
                            </div>

                            {{-- PILIH SUMBER (KAS/BANK bidang aktif) --}}
                            <div class="mb-3">
                                <label class="mb-2 d-block">Pilih Sumber Dana (Kas / Bank)</label>
                                <div class="row g-2">

                                    {{-- Sumber: Kas --}}
                                    @if ($kasAkun)
                                        <div class="col-md-6">
                                            <div class="form-check border rounded p-2 h-100">
                                                <input class="form-check-input" type="radio" name="sumber_akun_id"
                                                    id="sumberKas" value="{{ $kasAkun->id }}"
                                                    {{ old('sumber_akun_id', $kasAkun->id) == $kasAkun->id ? 'checked' : '' }}>
                                                <label class="form-check-label w-100" for="sumberKas">
                                                    <div class="fw-bold">{{ $kasAkun->nama_akun }}</div>
                                                    <div>
                                                        <small class="text-muted">
                                                            Saldo saat ini: Rp {{ number_format($saldoKas, 0, ',', '.') }}
                                                        </small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Sumber: Bank --}}
                                    @if ($bankAkun)
                                        <div class="col-md-6">
                                            <div class="form-check border rounded p-2 h-100">
                                                <input class="form-check-input" type="radio" name="sumber_akun_id"
                                                    id="sumberBank" value="{{ $bankAkun->id }}"
                                                    {{ old('sumber_akun_id') == $bankAkun->id ? 'checked' : '' }}>
                                                <label class="form-check-label w-100" for="sumberBank">
                                                    <div class="fw-bold">{{ $bankAkun->nama_akun }}</div>
                                                    <div>
                                                        <small class="text-muted">
                                                            Saldo saat ini: Rp {{ number_format($saldoBank, 0, ',', '.') }}
                                                        </small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                                @error('sumber_akun_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Tanggal Transaksi</label>
                                <input type="date" name="tanggal_transaksi" class="form-control"
                                    value="{{ old('tanggal_transaksi') }}" required>
                                @error('tanggal_transaksi')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- TUJUAN DANA --}}
                            <div class="mb-3">
                                <label class="form-label mb-2">Tujuan Dana</label>
                                <select class="form-control" id="tujuan_akun_id" name="tujuan_akun_id" required>
                                    <option value="">— Pilih Akun Tujuan —</option>

                                    @foreach ($kasBankTujuan as $akun)
                                        <option value="{{ $akun->id }}"
                                            {{ old('tujuan_akun_id') == $akun->id ? 'selected' : '' }}>
                                            {{ $akun->nama_akun }}
                                            @if ($akun->id == optional($kasAkun)->id)
                                                — [Kas Bidang Ini]
                                            @elseif ($akun->id == optional($bankAkun)->id)
                                                — [Bank Bidang Ini]
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('tujuan_akun_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- DESKRIPSI --}}
                            <div class="mb-3">
                                <label class="mb-2">Deskripsi Transaksi</label>
                                <input type="text" name="deskripsi" class="form-control"
                                    placeholder="Misal: Transfer Kas Bidang ke Bank Bendahara"
                                    value="{{ old('deskripsi') }}" required>
                                @error('deskripsi')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- JUMLAH --}}
                            <div class="mb-3">
                                <label class="form-label mb-2">Jumlah</label>
                                <input type="text" id="formattedAmountTransfer" class="form-control"
                                    oninput="formatInputTransfer(this)"
                                    value="{{ old('amount') ? number_format(old('amount'), 0, ',', '.') : '' }}">
                                <input type="number" name="amount" id="amountTransfer" class="form-control d-none"
                                    value="{{ old('amount') }}">
                                @error('amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data Transaksi -->
        <div class="p-3 shadow table-responsive rounded">
            <table id="transaksi-table" class="p-2 table table-striped table-bordered rounded yajra-datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode T.</th>
                        <th>Jenis T.</th>
                        <th>Akun Asal</th>
                        <th>Akun Tujuan</th>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
@endsection
@push('script')
    <script>
        // JS label type (masih bisa dipakai kalau nanti modal transaksi umum diaktifkan lagi)
        document.addEventListener("DOMContentLoaded", function() {
            const akunLabel = document.getElementById("akun-label");
            const typeRadios = document.querySelectorAll('input[name="type"]');

            function updateAkunLabel() {
                if (!akunLabel) return;
                const checked = document.querySelector('input[name="type"]:checked');
                if (!checked) {
                    akunLabel.textContent = "Induk Aset Neto";
                    return;
                }
                // Di OB, label tetap Induk Aset Neto, logic ini disimpan kalau nanti type dipakai lagi
                akunLabel.textContent = "Induk Aset Neto";
            }

            typeRadios.forEach(r => r.addEventListener("change", updateAkunLabel));
            updateAkunLabel();

            // JS: handle Anak Aset Neto berdasarkan Induk
            const akunKeuangan = document.getElementById("akun_keuangan");
            const parentAkunContainer = document.getElementById("parent-akun-container");
            const parentAkunSelect = document.getElementById("parent_akun_id");

            if (akunKeuangan && parentAkunContainer && parentAkunSelect) {
                const akunDenganParent = @json($akunDenganParent);

                akunKeuangan.addEventListener("change", function() {
                    const selectedAkunId = this.value;
                    parentAkunSelect.innerHTML = '<option value="">Pilih Anak Aset Neto</option>';

                    if (selectedAkunId && akunDenganParent[selectedAkunId]) {
                        akunDenganParent[selectedAkunId].forEach(akun => {
                            const opt = document.createElement("option");
                            opt.value = akun.id;
                            opt.textContent = `${akun.kode_akun} — ${akun.nama_akun}`;
                            parentAkunSelect.appendChild(opt);
                        });
                        parentAkunContainer.style.display = "block";
                    } else {
                        parentAkunContainer.style.display = "none";
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sumberRadios = document.querySelectorAll('input[name="sumber_akun_id"]');
            const tujuanSelect = document.getElementById('tujuan_akun_id');

            if (!sumberRadios.length || !tujuanSelect) return;

            function filterTujuan() {
                const checked = document.querySelector('input[name="sumber_akun_id"]:checked');
                const sumberId = checked ? checked.value : null;

                Array.from(tujuanSelect.options).forEach(option => {
                    if (option.value === "") return; // placeholder

                    if (sumberId && option.value === sumberId) {
                        option.style.display = 'none';
                        if (tujuanSelect.value === option.value) {
                            tujuanSelect.value = "";
                        }
                    } else {
                        option.style.display = 'block';
                    }
                });
            }

            // pasang listener ke radio sumber
            sumberRadios.forEach(r => {
                r.addEventListener('change', filterTujuan);
            });

            // panggil sekali di awal (pakai default sumber)
            filterTujuan();
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-buka modal transfer kalau error & ini request transfer
            @if ($errors->any() && old('is_transfer'))
                const modalEl = document.getElementById('transferModal');
                if (modalEl) {
                    const transferModal = new bootstrap.Modal(modalEl);
                    transferModal.show();
                }
            @endif
        });

        function formatInputTransfer(input) {
            let rawValue = input.value.replace(/\D/g, ""); // hanya angka
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);
            input.value = formatted;
            document.getElementById("amountTransfer").value = rawValue;
        }

        $(document).ready(function() {
            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.data') }}",
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
                        name: 'type',
                        render: function(data, type, row) {
                            if (data === 'penerimaan') {
                                return '<span class="badge bg-success">Penerimaan</span>';
                            } else if (data === 'pengeluaran') {
                                return '<span class="badge bg-danger">Pengeluaran</span>';
                            } else if (data === 'pendapatan belum diterima') {
                                return '<span class="badge bg-warning">Belum Diterima</span>';
                            } else {
                                return '<span class="badge bg-secondary">Unknown</span>';
                            }
                        }
                    },
                    {
                        data: 'akun_keuangan_id',
                        name: 'akun_keuangan_id',
                        render: function(data, type, row) {
                            return row.akun_keuangan ? row.akun_keuangan.nama_akun : 'N/A';
                        }
                    },
                    {
                        data: 'parent_akun_id',
                        name: 'parent_akun_id',
                        render: function(data, type, row) {
                            return row.parent_akun_keuangan ? row.parent_akun_keuangan.nama_akun :
                                'N/A';
                        }
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        render: function(data, type, row) {
                            return number_format(data);
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
                    console.log(xhr.responseText);
                }
            });
        });

        function formatInput(input) {
            let rawValue = input.value.replace(/\D/g, "");
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);

            input.value = formatted;
            document.getElementById("amount").value = rawValue;
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

        function formatInputTransfer(input) {
            let rawValue = input.value.replace(/\D/g, ""); // hanya angka
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);

            input.value = formatted;
            document.getElementById("amountTransfer").value = rawValue;
        }
    </script>
@endpush

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
        <div>
            <!-- Button untuk membuka modal Opening Balance -->
            <button type="button" class="btn btn-warning mb-3 me-2 shadow" id="btn-opening-balance" data-bs-toggle="modal"
                data-bs-target="#transactionModal">
                <i class="bi bi-box-arrow-in-down"></i> Opening Balance
            </button>

            <button type="button" class="btn btn-success mb-3 me-2 shadow" id="btn-adjustment" data-bs-toggle="modal"
                data-bs-target="#penyesuaianModal">
                <i class="bi bi-box-arrow-in-down"></i> Adjustment Saldo Activities
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
        </div>


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
                                    @foreach ($equityTanpaParent as $akun)
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

        <div class="modal fade" id="penyesuaianModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Penyesuaian Surplus / Defisit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="mb-2">Tanggal Penyesuaian</label>
                            <input type="date" name="tanggal" id="tanggalPenyesuaian" class="form-control" required>
                        </div>

                        <hr>

                        <table class="table table-sm">
                            <tr>
                                <th>Total Pendapatan</th>
                                <td class="text-end" id="totalPendapatan">-</td>
                            </tr>
                            <tr>
                                <th>Total Beban</th>
                                <td class="text-end" id="totalBeban">-</td>
                            </tr>
                            <tr class="fw-bold">
                                <th>Surplus / Defisit</th>
                                <td class="text-end" id="surplusDefisit">-</td>
                            </tr>
                        </table>

                        <div id="statusBadge" class="text-center fw-bold"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" id="btnPostAdjustment" class="btn btn-primary">
                            Posting Penyesuaian
                        </button>
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

        {{-- Modal Edit Transaksi --}}
        <div class="modal fade" id="editTransactionModal" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTransactionModalLabel">Edit Transaksi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <form id="formEditTransaksi" method="POST">
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="bidang_name" id="edit_bidang_name">
                            <input type="hidden" name="akun_keuangan_id" id="edit_akun_keuangan_hidden">

                            <div class="mb-3">
                                <label for="edit_kode_transaksi" class="form-label mb-2">Kode Transaksi</label>
                                <input type="text" class="form-control" id="edit_kode_transaksi"
                                    name="kode_transaksi" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Tanggal Transaksi</label>
                                <input type="date" name="tanggal_transaksi" id="edit_tanggal_transaksi"
                                    class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">Tipe Transaksi</label>
                                <div class="d-flex gap-2" id="edit-transaction-type-buttons">
                                    <input type="radio" class="btn-check" name="type" id="edit_penerimaan"
                                        value="penerimaan" autocomplete="off">
                                    <label class="btn btn-outline-success" for="edit_penerimaan">Penerimaan</label>

                                    <input type="radio" class="btn-check" name="type" id="edit_pengeluaran"
                                        value="pengeluaran" autocomplete="off">
                                    <label class="btn btn-outline-danger" for="edit_pengeluaran">Pengeluaran</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2" id="edit-akun-label">Asal Akun</label>
                                <select class="form-control" name="akun_keuangan_id" id="edit_akun_keuangan" required>
                                    <option value="">Pilih Akun</option>
                                    @foreach ($akunTanpaParent as $akun)
                                        <option value="{{ $akun->id }}">
                                            {{ $akun->kode_akun }} — {{ $akun->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3" id="edit-parent-akun-container" style="display: none;">
                                <label class="mb-2">Akun Parent</label>
                                <select class="form-control" name="parent_akun_id" id="edit_parent_akun_id">
                                    <option value="">Pilih Akun Parent</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Deskripsi Transaksi</label>
                                <input type="text" name="deskripsi" id="edit_deskripsi" class="form-control"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">Jumlah</label>
                                <input type="text" id="edit_formattedAmount" class="form-control"
                                    oninput="formatInputEdit(this)">
                                <input type="number" name="amount" id="edit_amount" class="form-control d-none">
                            </div>

                            <button type="submit" class="btn btn-primary">Update</button>
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
                        <th>Dibuat Oleh</th>
                        <th>Diupdate Oleh</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Tabel Mutasi Antar Bidang/Bendahara -->
        <div class="p-3 shadow table-responsive rounded mt-4">
            <h1>Mutasi <strong>(internal)</strong></h1>
            <p class="text-muted">Menampilkan transaksi transfer antar kas/bank dan bidang/bendahara</p>
            <table id="mutasi-table" class="p-2 table table-striped table-bordered rounded yajra-mutasi">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode T.</th>
                        <th>Jenis T.</th>
                        <th>Akun Asal</th>
                        <th>Akun Tujuan</th>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                        <th>Dibuat Oleh</th>
                        <th>Diupdate Oleh</th>
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
        // ==========================
        // 1) OPENING BALANCE: label & Anak Aset Neto
        // ==========================
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
                // Di OB, label tetap Induk Aset Neto (disimpan kalau nanti mau dipakai lagi)
                akunLabel.textContent = "Induk Aset Neto";
            }

            typeRadios.forEach(r => r.addEventListener("change", updateAkunLabel));
            updateAkunLabel();

            const akunKeuangan = document.getElementById("akun_keuangan");
            const parentAkunContainer = document.getElementById("parent-akun-container");
            const parentAkunSelect = document.getElementById("parent_akun_id");

            if (akunKeuangan && parentAkunContainer && parentAkunSelect) {
                const equityDenganParent = @json($equityDenganParent);

                akunKeuangan.addEventListener("change", function() {
                    const selectedAkunId = this.value;
                    parentAkunSelect.innerHTML = '<option value="">Pilih Anak Aset Neto</option>';

                    if (selectedAkunId && equityDenganParent[selectedAkunId]) {
                        equityDenganParent[selectedAkunId].forEach(akun => {
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

        // ==========================
        // 2) FILTER TUJUAN TRANSFER (hindari tujuan = sumber)
        // ==========================
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

            sumberRadios.forEach(r => r.addEventListener('change', filterTujuan));
            filterTujuan();
        });

        // ==========================
        // 3) AUTO BUKA MODAL TRANSFER JIKA VALIDASI ERROR
        // ==========================
        document.addEventListener('DOMContentLoaded', function() {
            @if ($errors->any() && old('is_transfer'))
                const modalEl = document.getElementById('transferModal');
                if (modalEl) {
                    const transferModal = new bootstrap.Modal(modalEl);
                    transferModal.show();
                }
            @endif
        });

        // ==========================
        // 4) FORMAT INPUT (CREATE & TRANSFER)
        // ==========================
        function formatInput(input) {
            let rawValue = input.value.replace(/\D/g, "");
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);
            input.value = formatted;
            const hidden = document.getElementById("amount");
            if (hidden) hidden.value = rawValue;
        }

        function formatInputTransfer(input) {
            let rawValue = input.value.replace(/\D/g, ""); // hanya angka
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);
            input.value = formatted;
            const hidden = document.getElementById("amountTransfer");
            if (hidden) hidden.value = rawValue;
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

        // ==========================
        // 5) DATATABLE TRANSAKSI
        // ==========================
        $(document).ready(function() {
            $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.data') }}",
                columns: [{
                        data: 'tanggal_transaksi',
                        name: 'tanggal_transaksi',
                        searchable: true
                    },
                    {
                        data: 'kode_transaksi',
                        name: 'kode_transaksi',
                        searchable: true
                    },
                    {
                        data: 'type',
                        name: 'type',
                        searchable: true,
                        render: function(data, type, row) {
                            if (data === 'penerimaan') {
                                return '<span class="badge bg-success">Penerimaan</span>';
                            } else if (data === 'pengeluaran') {
                                return '<span class="badge bg-danger">Pengeluaran</span>';
                            } else if (data === 'pendapatan belum diterima') {
                                return '<span class="badge bg-warning">Belum Diterima</span>';
                            } else if (data === 'pengakuan_pendapatan') {
                                return '<span class="badge bg-info">Pengakuan Pendapatan</span>';
                            } else if (data === 'mutasi') {
                                return '<span class="badge bg-primary">Mutasi</span>';
                            } else if (data === 'penyesuaian') {
                                return '<span class="badge bg-secondary">Penyesuaian</span>';
                            } else {
                                return '<span class="badge bg-secondary">Unknown</span>';
                            }
                        }
                    },
                    {
                        data: 'akun_keuangan_id',
                        name: 'akun_keuangan.nama_akun',
                        searchable: true,
                        render: function(data, type, row) {
                            return row.akun_keuangan ? row.akun_keuangan.nama_akun : 'N/A';
                        }
                    },
                    {
                        data: 'parent_akun_id',
                        name: 'parent_akun_keuangan.nama_akun',
                        searchable: true,
                        render: function(data, type, row) {
                            return row.parent_akun_keuangan ? row.parent_akun_keuangan.nama_akun :
                                'N/A';
                        }
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi',
                        searchable: true
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        searchable: true,
                        render: function(data, type, row) {
                            return number_format(data);
                        }
                    },
                    {
                        data: 'user_name',
                        name: 'user.name',
                        searchable: true
                    },
                    {
                        data: 'updated_by_name',
                        name: 'updatedBy.name',
                        searchable: true
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

        // ==========================
        // PREVIEW PENYESUAIAN SURPLUS / DEFISIT
        // ==========================
        document.addEventListener('DOMContentLoaded', function() {
            const tanggalInput = document.getElementById('tanggalPenyesuaian');
            const totalPendapatan = document.getElementById('totalPendapatan');
            const totalBeban = document.getElementById('totalBeban');
            const surplusDefisit = document.getElementById('surplusDefisit');
            const statusBadge = document.getElementById('statusBadge');
            const btnPost = document.getElementById('btnPostAdjustment');

            let previewData = null;
            let alreadyAdjusted = false;

            // ====================
            // Preview Penyesuaian
            // ====================
            tanggalInput.addEventListener('change', function() {
                const tanggal = this.value;
                if (!tanggal) return;

                fetch("{{ route('transaksi.adjustment.preview') }}?tanggal=" + tanggal, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        previewData = data;
                        totalPendapatan.innerText = new Intl.NumberFormat('id-ID').format(data
                            .pendapatan);
                        totalBeban.innerText = new Intl.NumberFormat('id-ID').format(data.beban);
                        surplusDefisit.innerText = new Intl.NumberFormat('id-ID').format(data
                            .surplus_defisit);

                        // Status badge
                        if (data.status === 'SURPLUS') {
                            statusBadge.innerHTML = '<span class="badge bg-success">SURPLUS</span>';
                        } else if (data.status === 'DEFISIT') {
                            statusBadge.innerHTML = '<span class="badge bg-danger">DEFISIT</span>';
                        } else {
                            statusBadge.innerHTML = '<span class="badge bg-secondary">NOL</span>';
                        }

                        // Disable tombol jika NOL
                        if (data.status === 'NOL') {
                            btnPost.disabled = true;
                            btnPost.classList.add('btn-secondary');
                            btnPost.classList.remove('btn-primary');
                        } else {
                            btnPost.disabled = false;
                            btnPost.classList.remove('btn-secondary');
                            btnPost.classList.add('btn-primary');
                        }

                        // Cek apakah sudah ada penyesuaian tahun ini (guard)
                        fetch("{{ route('transaksi.adjustment.check') }}?tanggal=" + tanggal, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(res => res.json())
                            .then(check => {
                                if (check.exists) {
                                    alreadyAdjusted = true;
                                    btnPost.disabled = true;
                                    btnPost.classList.add('btn-secondary');
                                    btnPost.classList.remove('btn-primary');
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Sudah Pernah Penyesuaian',
                                        text: 'Penyesuaian sudah pernah dilakukan untuk tahun ini.'
                                    });
                                } else {
                                    alreadyAdjusted = false;
                                }
                            });

                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Terjadi kesalahan saat preview.'
                        });
                        totalPendapatan.innerText = totalBeban.innerText = surplusDefisit.innerText =
                            '-';
                        statusBadge.innerHTML = '';
                        previewData = null;
                    });
            });

            // ====================
            // Post Penyesuaian via AJAX
            // ====================
            btnPost.addEventListener('click', function() {
                if (!tanggalInput.value) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Pilih tanggal terlebih dahulu.'
                    });
                    return;
                }
                if (!previewData) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Lakukan preview terlebih dahulu.'
                    });
                    return;
                }
                if (alreadyAdjusted) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Sudah pernah penyesuaian tahun ini.'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi',
                    text: "Apakah Anda yakin ingin memposting penyesuaian ini?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, posting!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ route('transaksi.adjustment.store') }}", {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    tanggal: tanggalInput.value
                                })
                            })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil',
                                        text: res.message ||
                                            'Penyesuaian berhasil diposting.'
                                    }).then(() => {
                                        // reset modal
                                        tanggalInput.value = '';
                                        totalPendapatan.innerText = totalBeban
                                            .innerText = surplusDefisit.innerText = '-';
                                        statusBadge.innerHTML = '';
                                        previewData = null;
                                        alreadyAdjusted = false;
                                        btnPost.disabled = false;
                                        btnPost.classList.add('btn-primary');
                                        btnPost.classList.remove('btn-secondary');

                                        // tutup modal
                                        var modal = bootstrap.Modal.getInstance(document
                                            .getElementById('penyesuaianModal'));
                                        modal.hide();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal',
                                        text: res.message ||
                                            'Terjadi kesalahan saat posting.'
                                    });
                                }
                            })
                            .catch(() => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Terjadi kesalahan saat posting.'
                                });
                            });
                    }
                });
            });

        });

        // ==========================
        // DATATABLE MUTASI (TRANSFER)
        // ==========================
        $(document).ready(function() {
            $('.yajra-mutasi').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.mutasi.data') }}", // ROUTE BARU
                columns: [{
                        data: 'tanggal_transaksi',
                        name: 'tanggal_transaksi',
                        searchable: true
                    },
                    {
                        data: 'kode_transaksi',
                        name: 'kode_transaksi',
                        searchable: true
                    },
                    {
                        data: 'type',
                        name: 'type',
                        searchable: true,
                        render: function(data, type, row) {
                            if (data === 'penerimaan') {
                                return '<span class="badge bg-success">Penerimaan</span>';
                            } else if (data === 'pengeluaran') {
                                return '<span class="badge bg-danger">Pengeluaran</span>';
                            } else if (data === 'pendapatan belum diterima') {
                                return '<span class="badge bg-warning">Belum Diterima</span>';
                            } else if (data === 'pengakuan_pendapatan') {
                                return '<span class="badge bg-info">Pengakuan Pendapatan</span>';
                            } else if (data === 'mutasi') {
                                return '<span class="badge bg-primary">Mutasi</span>';
                            } else if (data === 'penyesuaian') {
                                return '<span class="badge bg-secondary">Penyesuaian</span>';
                            } else {
                                return '<span class="badge bg-secondary">Unknown</span>';
                            }
                        }
                    },
                    {
                        data: 'akun_keuangan_id',
                        name: 'akun_keuangan_id',
                        searchable: true,
                        render: function(data, type, row) {
                            return row.akun_keuangan ? row.akun_keuangan.nama_akun : 'N/A';
                        }
                    },
                    {
                        data: 'parent_akun_id',
                        name: 'parent_akun_id',
                        searchable: true,
                        render: function(data, type, row) {
                            return row.parent_akun_keuangan ? row.parent_akun_keuangan.nama_akun :
                                'N/A';
                        }
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi',
                        searchable: true
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        searchable: true,
                        render: function(data, type, row) {
                            return number_format(data);
                        }
                    },
                    {
                        data: 'user_name',
                        name: 'user.name',
                        searchable: true
                    },
                    {
                        data: 'updated_by_name',
                        name: 'updatedBy.name',
                        searchable: true
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

        // ==========================
        // 6) EDIT MODAL (AJAX + FILL FORM)
        // ==========================
        const akunDenganParentEdit = @json($akunDenganParent);

        function populateParentForEdit(parentId, selectedChildId = null) {
            const container = document.getElementById('edit-parent-akun-container');
            const select = document.getElementById('edit_parent_akun_id');
            if (!container || !select) return;

            select.innerHTML = '<option value="">Pilih Akun Parent</option>';

            const children = akunDenganParentEdit[parentId] || akunDenganParentEdit[String(parentId)];

            if (children && children.length > 0) {
                children.forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a.id;
                    opt.textContent = (a.kode_akun ? a.kode_akun + ' — ' : '') + a.nama_akun;
                    if (selectedChildId && Number(selectedChildId) === Number(a.id)) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function formatInputEdit(input) {
            let rawValue = input.value.replace(/\D/g, '');
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);
            input.value = formatted;
            const hidden = document.getElementById("edit_amount");
            if (hidden) hidden.value = rawValue;
        }

        // Tombol Edit di tabel diklik
        $(document).on('click', '.btn-edit-transaksi', function() {
            const id = $(this).data('id');

            // generate URL dari named route lalu replace :id
            const url = "{{ route('transaksi.json', ['id' => ':id']) }}".replace(':id', id);

            $.get(url, function(res) {
                const modal = $('#editTransactionModal');
                const form = $('#formEditTransaksi');

                form.attr('action', res.update_url);

                // LOG: Cek apakah update_url sudah benar
                console.log('Transaction ID:', id);
                console.log('Update URL:', res.update_url);
                console.log('Response Data:', res);

                $('#edit_bidang_name').val(res.bidang_name ?? '');
                $('#edit_kode_transaksi').val(res.kode_transaksi ?? '');
                $('#edit_tanggal_transaksi').val(res.tanggal_transaksi ?? '');
                $('#edit_deskripsi').val(res.deskripsi ?? '');

                const amt = parseFloat(res.amount) || 0;
                $('#edit_amount').val(amt);
                $('#edit_formattedAmount').val(
                    amt ? new Intl.NumberFormat('id-ID').format(amt) : ''
                );

                if (res.type === 'penerimaan') {
                    $('#edit_penerimaan').prop('checked', true);
                    $('#edit_pengeluaran').prop('checked', false);
                    $('#edit-akun-label').text('Asal Akun');
                } else if (res.type === 'pengeluaran') {
                    $('#edit_penerimaan').prop('checked', false);
                    $('#edit_pengeluaran').prop('checked', true);
                    $('#edit-akun-label').text('Tujuan Akun');
                }

                // set induk akun (Pendapatan/Beban/dll) di dropdown
                $('#edit_akun_keuangan').val(res.akun_keuangan_id || '');
                console.log('Akun Keuangan ID:', res.akun_keuangan_id);

                // hidden: simpan kas/bank sumber asli (untuk backend update)
                $('#edit_akun_keuangan_hidden').val(res.akun_sumber_id || '');
                console.log('Akun Sumber ID (Kas/Bank):', res.akun_sumber_id);

                // isi dropdown anak akun berdasarkan induk + selected child
                populateParentForEdit(res.akun_keuangan_id, res.parent_akun_id);

                modal.modal('show');
            }).fail(function(xhr) {
                console.error('Gagal load JSON transaksi:', xhr.responseText);
                console.error('Status:', xhr.status);
            });
        });

        // Ubah induk akun di modal edit
        $('#edit_akun_keuangan').on('change', function() {
            const parentId = this.value;
            populateParentForEdit(parentId, null);
            $('#edit_akun_keuangan_hidden').val(parentId || '');
        });

        // Ubah label kalau tipe diubah di modal edit
        $('#edit_penerimaan, #edit_pengeluaran').on('change', function() {
            const checked = $('input[name="type"]:checked').val();
            if (checked === 'penerimaan') {
                $('#edit-akun-label').text('Asal Akun');
            } else if (checked === 'pengeluaran') {
                $('#edit-akun-label').text('Tujuan Akun');
            }
        });

        // ==========================
        // 7) DELETE DENGAN SWEETALERT
        // ==========================
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();

            const id = $(this).data('id');
            const formId = '#delete-form-' + id;

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
                    $(formId).submit();
                }
            });
        });
    </script>
@endpush

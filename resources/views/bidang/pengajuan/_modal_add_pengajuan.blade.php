<div class="modal fade" id="modalCreatePengajuan" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #8B4513; color: white; border-color: #8B4513;">
                <h3 class="modal-title text-white"><i class="bi bi-file-earmark-plus"></i> Form Pengajuan Dana</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form action="{{ route('pengajuan.store') }}" method="POST" id="form-pengajuan">
                @csrf
                <div class="modal-body bg-light">

                    {{-- 1. Informasi Dasar --}}
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <h1 class="card-title text-secondary mb-3 fw-bold">Informasi Dasar</h1>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Judul Pengajuan <span class="text-danger">*</span></label>
                                    <input type="text" name="judul" class="form-control"
                                        placeholder="Contoh: Pembelian ATK Bulan November" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Deskripsi / Catatan</label>
                                    <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan tambahan..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Rincian Anggaran --}}
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="card-title text-secondary mb-0 fw-bold">Rincian Item Anggaran</h3>
                                <button type="button" class="btn btn-sm shadow-sm"
                                    style="background-color: #8B4513; color: white; border-color: #8B4513;"
                                    id="btn-add-row">
                                    <i class="bi bi-plus-lg"></i> Tambah Baris
                                </button>
                            </div>

                            <div class="table-responsive rounded border">
                                <table class="table table-bordered align-middle mb-0 bg-white" id="table-items">
                                    <thead class="table-light text-center small text-uppercase">
                                        <tr>
                                            <th style="width: 30%">Pos Akun (CoA)</th>
                                            <th style="width: 25%">Keterangan Item</th>
                                            <th style="width: 10%">Qty</th>
                                            <th style="width: 20%">Harga Satuan (Rp)</th>
                                            <th style="width: 15%">Subtotal</th>
                                            <th style="width: 5%">Aksi</th>
                                        </tr>
                                    </thead>
                                    {{-- ID tbody-items INI PENTING UNTUK JS --}}
                                    <tbody id="tbody-items">
                                        {{-- Baris Default Pertama --}}
                                        <tr class="item-row">
                                            <td>
                                                <select name="details[0][akun_keuangan_id]"
                                                    class="form-select form-select-sm" required>
                                                    <option value="">-- Pilih Akun --</option>
                                                    @foreach ($akunKeuangans as $akun)
                                                        <option value="{{ $akun->id }}">
                                                            {{ $akun->nama_akun }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="details[0][keterangan_item]"
                                                    class="form-control form-select-sm" required>
                                            </td>
                                            <td>
                                                <input type="number" step="1" min="0"
                                                    name="details[0][kuantitas]"
                                                    class="form-control form-select-sm text-center input-qty"
                                                    value="1" required>
                                            </td>
                                            <td>
                                                <input type="number" min="0" name="details[0][harga_pokok]"
                                                    class="form-control form-select-sm text-end input-harga"
                                                    placeholder="0" required>
                                            </td>
                                            <td>
                                                <input type="text"
                                                    class="form-control form-select-sm text-end input-subtotal bg-light fw-bold"
                                                    value="0" readonly>
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm border-0 btn-remove"><i
                                                        class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold text-uppercase">Total Estimasi
                                                Dana</td>
                                            <td class="text-end fw-bold text-primary fs-5">
                                                <span id="grand-total-display">Rp 0</span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn px-4"
                        style="background-color: #8B4513; color: white; border-color: #8B4513;">
                        <i class="bi bi-send"></i> Simpan & Ajukan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

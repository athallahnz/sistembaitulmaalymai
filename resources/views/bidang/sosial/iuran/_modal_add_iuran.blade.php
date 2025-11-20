<!-- Modal: Tambah Iuran Bulanan -->
<div class="modal fade" id="modalCreateIuran" tabindex="-1" aria-labelledby="modalCreateIuranLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="form-iuran-modal" method="POST" action="{{ route('sosial.iuran.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateIuranLabel">Tambah Infaq Sinoman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    {{-- IDENTITAS KEPALA KELUARGA --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kepala Keluarga <span class="text-danger">*</span></label>
                            <select name="warga_kepala_id" class="form-select" id="select-warga-kepala" required>
                                <option value="">-- Pilih Kepala Keluarga --</option>
                                @foreach (\App\Models\Warga::kepalaKeluarga()->orderBy('nama')->get() as $kk)
                                    <option value="{{ $kk->id }}">
                                        {{ $kk->nama }} (RT {{ $kk->rt ?? '-' }}, {{ $kk->hp ?? 'tanpa HP' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="tahun" class="form-control"
                                value="{{ $tahun ?? now()->year }}" min="2020" max="2100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bulan <span class="text-danger">*</span></label>
                            <select name="bulan" class="form-select" required>
                                @php
                                    $bulanAktif = (int) request('bulan', now()->month);
                                    $namaBulan = [
                                        1 => 'Januari',
                                        2 => 'Februari',
                                        3 => 'Maret',
                                        4 => 'April',
                                        5 => 'Mei',
                                        6 => 'Juni',
                                        7 => 'Juli',
                                        8 => 'Agustus',
                                        9 => 'September',
                                        10 => 'Oktober',
                                        11 => 'November',
                                        12 => 'Desember',
                                    ];
                                @endphp
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i === $bulanAktif ? 'selected' : '' }}>
                                        {{ $namaBulan[$i] }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- 👇 Tambahan: info anggota keluarga --}}
                        <div class="col-12">
                            <div id="info-anggota-keluarga" class="small text-muted">
                                Pilih kepala keluarga untuk melihat daftar anggota/peserta.
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- PEMBAYARAN --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nominal Tagihan (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal_tagihan" class="form-control" min="0"
                                    step="10000" value="{{ old('nominal_tagihan', 0) }}" required>
                            </div>
                            <div class="form-text">
                                Contoh: 10.000 per bulan, atau sesuai kesepakatan RT.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nominal Dibayar (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal_bayar" id="nominal-bayar-modal" class="form-control"
                                    min="0" step="1000" value="{{ old('nominal_bayar', 0) }}" required>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                @foreach ([10000, 20000, 500000, 100000, 200000] as $n)
                                    <button type="button" class="btn btn-sm btn-outline-primary set-nominal-iuran"
                                        data-amount="{{ $n }}">
                                        {{ number_format($n, 0, ',', '.') }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="metode_bayar" class="form-select">
                                <option value="">-- Pilih Metode Pembayaran --</option>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                                {{-- <option value="lainnya">Lainnya</option> --}}
                            </select>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-iuran">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

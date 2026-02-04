<!-- Modal: Tambah/Edit Iuran Bulanan -->
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
                            <input type="number" name="tahun" id="tahun-modal" class="form-control"
                                value="{{ $tahun ?? now()->year }}" min="2020" max="2100" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Bulan <span class="text-danger">*</span></label>
                            <select name="bulan" id="bulan-modal" class="form-select" required>
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
                                <input type="number" name="nominal_tagihan" id="nominal-tagihan-modal"
                                    class="form-control" min="0" step="10000"
                                    value="{{ old('nominal_tagihan', 0) }}" required>
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
                                @foreach ([10000, 20000, 50000, 100000, 200000, 500000] as $n)
                                    <button type="button" class="btn btn-sm btn-outline-primary set-nominal-iuran"
                                        data-amount="{{ $n }}">
                                        {{ number_format($n, 0, ',', '.') }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Metode Pembayaran
                                <span class="text-danger" id="metode-required-star" style="display:none">*</span>
                            </label>

                            <select name="metode_bayar" id="metode-bayar-modal" class="form-select">
                                <option value="">-- Pilih Metode Pembayaran --</option>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                            </select>

                            <div class="invalid-feedback">
                                Metode pembayaran wajib dipilih jika ada nominal dibayar.
                            </div>
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

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('modalCreateIuran');
            if (!modalEl) return;

            const form = document.getElementById('form-iuran-modal');

            const modalTitle = document.getElementById('modalCreateIuranLabel');

            const wargaSelect = document.getElementById('select-warga-kepala');
            const tahunInput = document.getElementById('tahun-modal');
            const bulanSelect = document.getElementById('bulan-modal');

            const tagihanInput = document.getElementById('nominal-tagihan-modal');
            const bayarInput = document.getElementById('nominal-bayar-modal');
            const metodeSelect = document.getElementById('metode-bayar-modal');
            const star = document.getElementById('metode-required-star');

            function syncMetodeRequired() {
                const bayar = parseInt((bayarInput?.value || '0'), 10);

                if (bayar > 0) {
                    if (metodeSelect) metodeSelect.required = true;
                    if (star) star.style.display = 'inline';
                } else {
                    if (metodeSelect) {
                        metodeSelect.required = false;
                        metodeSelect.classList.remove('is-invalid');
                    }
                    if (star) star.style.display = 'none';
                }
            }

            document.querySelectorAll('.set-nominal-iuran').forEach(btn => {
                btn.addEventListener('click', () => {
                    const amount = parseInt(btn.dataset.amount || '0', 10);
                    if (bayarInput) bayarInput.value = amount;
                    syncMetodeRequired();
                });
            });

            if (bayarInput) bayarInput.addEventListener('input', syncMetodeRequired);

            // Helper set select value
            function setSelectValue(selectEl, value) {
                if (!selectEl) return;
                selectEl.value = String(value ?? '');
                selectEl.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            // Mode edit: kunci identitas agar tidak pindah record
            function lockIdentity(lock = true) {
                if (wargaSelect) wargaSelect.disabled = lock;
                if (tahunInput) tahunInput.readOnly = lock;
                if (bulanSelect) bulanSelect.disabled = lock;
            }

            // Isi data saat modal dibuka (works karena tombol pakai data-bs-toggle/target)
            modalEl.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                if (!btn) return;

                const warga = btn.getAttribute('data-warga') || '';
                const tahun = btn.getAttribute('data-tahun') || '';
                const bulan = btn.getAttribute('data-bulan') || '';
                const tagihan = btn.getAttribute('data-tagihan') || '0';
                const bayar = btn.getAttribute('data-bayar') || '0';
                const metode = btn.getAttribute('data-metode') || '';

                const isEdit = btn.classList.contains('btn-edit-iuran');

                if (modalTitle) modalTitle.textContent = isEdit ? 'Edit Infaq Sinoman' :
                    'Tambah Infaq Sinoman';

                setSelectValue(wargaSelect, warga);
                if (tahunInput) tahunInput.value = tahun;
                setSelectValue(bulanSelect, bulan);

                if (tagihanInput) tagihanInput.value = parseInt(tagihan, 10) || 0;
                if (bayarInput) bayarInput.value = parseInt(bayar, 10) || 0;
                setSelectValue(metodeSelect, metode);

                lockIdentity(isEdit);
                syncMetodeRequired();
            });

            // Validasi saat submit
            if (form) {
                form.addEventListener('submit', function(e) {
                    syncMetodeRequired();

                    const bayar = parseInt((bayarInput?.value || '0'), 10);
                    const metode = (metodeSelect?.value || '').trim();

                    if (bayar > 0 && !metode) {
                        e.preventDefault();
                        metodeSelect?.classList.add('is-invalid');
                        metodeSelect?.focus();
                        return;
                    }

                    metodeSelect?.classList.remove('is-invalid');

                    // disabled/readOnly tidak ikut submit -> enable dulu
                    if (wargaSelect) wargaSelect.disabled = false;
                    if (bulanSelect) bulanSelect.disabled = false;
                    if (tahunInput) tahunInput.readOnly = false;
                });
            }

            modalEl.addEventListener('hidden.bs.modal', function() {
                lockIdentity(false);
                if (modalTitle) modalTitle.textContent = 'Tambah Infaq Sinoman';
                syncMetodeRequired();
            });

            // init
            syncMetodeRequired();
        });
    </script>
@endpush

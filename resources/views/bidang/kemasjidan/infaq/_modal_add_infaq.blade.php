<!-- Modal: Tambah Infaq -->
<div class="modal fade" id="modalCreateInfaq" tabindex="-1" aria-labelledby="modalCreateInfaqLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="form-infaq-modal" method="POST" action="{{ route('kemasjidan.infaq.store') }}">
                @csrf

                {{-- WAJIB: karena store() require warga_id --}}
                <input type="hidden" name="warga_id" id="warga-id-modal" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateInfaqLabel">Tambah Infaq Bulanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    {{-- STATUS LOOKUP --}}
                    <div id="status-warga-modal" class="mb-2 small text-muted"></div>

                    {{-- IDENTITAS / WARGA --}}
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nomor HP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="hp" id="hp-modal"
                                placeholder="Masukkan No. Hp..." required>
                            <div class="form-text">Ketik nomor lalu keluar dari kolom ini (blur) untuk auto-lookup.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="nama" id="nama-modal"
                                placeholder="Masukkan Nama warga...">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">RT</label>
                            <input type="text" class="form-control" name="rt" id="rt-modal"
                                placeholder="Masukkan RT...">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">No Rumah</label>
                            <input type="text" class="form-control" name="no" id="no-modal"
                                placeholder="Masukkan No. Rumah...">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Alamat</label>
                            <input type="text" class="form-control" name="alamat" id="alamat-modal"
                                placeholder="Masukkan Alamat lengkap...">
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- PEMBAYARAN --}}
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="tahun" id="tahun-modal" min="2020"
                                max="2100" value="{{ now()->year }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Bulan <span class="text-danger">*</span></label>
                            <select class="form-select" name="bulan" id="bulan-modal" required>
                                @php
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
                                    $bulanAktif = (int) now()->month;
                                @endphp
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i === $bulanAktif ? 'selected' : '' }}>
                                        {{ $namaBulan[$i] }}
                                    </option>
                                @endfor
                            </select>

                            <div id="paid-hint-modal" class="form-text text-danger d-none">
                                Infaq bulan & tahun ini SUDAH ADA untuk warga tersebut.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="nominal" id="nominal-modal"
                                    step="10000" min="0" required>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                @foreach ([50000, 100000, 200000, 500000] as $n)
                                    <button type="button" class="btn btn-sm btn-outline-primary set-nominal-modal"
                                        data-amount="{{ $n }}">
                                        {{ number_format($n, 0, ',', '.') }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Metode Pembayaran</label>
                            <select class="form-select" name="metode_bayar" id="metode-bayar-modal">
                                <option value="">-- Pilih Metode Pembayaran --</option>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                            </select>
                            <div class="form-text">Menentukan apakah masuk ke Kas atau Bank.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sumber (opsional)</label>
                            <input type="text" class="form-control" name="sumber" id="sumber-modal"
                                placeholder="Kotak infaq / transfer / dll">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Keterangan (opsional)</label>
                            <input type="text" class="form-control" name="keterangan" id="keterangan-modal"
                                placeholder="Catatan tambahan">
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- PIN (opsional) --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">PIN (opsional)</label>
                            <input type="text" class="form-control mb-2" name="pin" id="pin-modal"
                                maxlength="16" placeholder="mis: 123456">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="auto-pin-modal"
                                    name="auto_pin">
                                <label class="form-check-label" for="auto-pin-modal">Generate PIN otomatis (6
                                    digit)</label>
                            </div>
                        </div>

                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-text">
                                Catatan: Dengan store() baru Anda yang wajib <code>warga_id</code>,
                                jika HP belum terdaftar maka submit akan diblok sampai warga dibuat/diimport dulu.
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-modal">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const elHp = document.getElementById('hp-modal');
            const elWargaId = document.getElementById('warga-id-modal');

            const fields = ['nama-modal', 'rt-modal', 'no-modal', 'alamat-modal'];
            const statusEl = document.getElementById('status-warga-modal');

            const paidHint = document.getElementById('paid-hint-modal');
            const selBulan = document.getElementById('bulan-modal');
            const inpTahun = document.getElementById('tahun-modal');

            const btnSubmit = document.getElementById('btn-submit-modal');

            const inpPin = document.getElementById('pin-modal');
            const chkAuto = document.getElementById('auto-pin-modal');

            // tombol nominal cepat
            document.querySelectorAll('.set-nominal-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('nominal-modal').value = btn.dataset.amount;
                });
            });

            function setDisabledOthers(disabled) {
                fields.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = disabled;
                });
            }

            function setSubmitEnabled(enabled) {
                if (!btnSubmit) return;
                btnSubmit.disabled = !enabled;
            }

            // Reset tiap modal dibuka
            const modalEl = document.getElementById('modalCreateInfaq');
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', () => {
                    const form = document.getElementById('form-infaq-modal');
                    if (form) form.reset();

                    if (elWargaId) elWargaId.value = '';

                    setDisabledOthers(false);
                    if (statusEl) statusEl.textContent = '';
                    paidHint?.classList.add('d-none');

                    // default: submit diblok sampai warga_id valid (sesuai store Anda)
                    setSubmitEnabled(false);

                    // PIN behavior
                    if (chkAuto) chkAuto.checked = false;
                    if (inpPin) inpPin.removeAttribute('disabled');
                });
            }

            // toggle auto PIN
            if (chkAuto && inpPin) {
                chkAuto.addEventListener('change', () => {
                    if (chkAuto.checked) {
                        inpPin.value = '';
                        inpPin.setAttribute('disabled', 'disabled');
                    } else {
                        inpPin.removeAttribute('disabled');
                    }
                });
            }

            async function checkPaid() {
                const wargaId = (elWargaId?.value || '').trim();
                const tahun = (inpTahun?.value || '').trim();
                const bulan = (selBulan?.value || '').trim();

                if (!wargaId || !tahun || !bulan) {
                    paidHint?.classList.add('d-none');
                    // submit tetap tergantung wargaId (kalau kosong ya disable)
                    setSubmitEnabled(!!wargaId);
                    return;
                }

                try {
                    const url =
                        `{{ route('kemasjidan.infaq.check') }}?warga_id=${encodeURIComponent(wargaId)}&tahun=${encodeURIComponent(tahun)}&bulan=${encodeURIComponent(bulan)}`;
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();

                    const paid = !!data.paid;
                    paidHint?.classList.toggle('d-none', !paid);

                    // kalau sudah ada → disable submit
                    setSubmitEnabled(!paid);

                } catch (e) {
                    paidHint?.classList.add('d-none');
                    setSubmitEnabled(true);
                }
            }

            // lookup by HP saat blur
            if (elHp) {
                elHp.addEventListener('blur', async function() {
                    const hp = (elHp.value || '').trim();
                    if (!hp) return;

                    if (statusEl) statusEl.textContent = 'Mencari data warga...';
                    setSubmitEnabled(false);

                    try {
                        const url =
                            `{{ route('kemasjidan.infaq.lookup') }}?hp=${encodeURIComponent(hp)}`;
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();

                        if (data.found) {
                            // isi hidden warga_id (WAJIB)
                            if (elWargaId) elWargaId.value = data.data.id ?? '';

                            // fill identitas
                            document.getElementById('nama-modal').value = data.data.nama ?? '';
                            document.getElementById('rt-modal').value = data.data.rt ?? '';
                            document.getElementById('no-modal').value = data.data.no ?? '';
                            document.getElementById('alamat-modal').value = data.data.alamat ?? '';

                            setDisabledOthers(true);

                            if (statusEl) {
                                statusEl.innerHTML =
                                    '<span class="text-success">Data ditemukan. Field identitas dikunci.</span>';
                            }

                            // warga valid → boleh submit (tapi masih harus cek paid)
                            setSubmitEnabled(true);

                            await checkPaid();

                        } else {
                            // reset warga_id & field
                            if (elWargaId) elWargaId.value = '';
                            fields.forEach(id => {
                                const f = document.getElementById(id);
                                if (f) f.value = '';
                            });

                            setDisabledOthers(false);

                            if (statusEl) {
                                statusEl.innerHTML =
                                    '<span class="text-warning">Nomor belum terdaftar. Karena store() Anda wajib warga_id, submit diblok. Tambahkan warga terlebih dahulu.</span>';
                            }

                            paidHint?.classList.add('d-none');
                            setSubmitEnabled(false);
                        }

                    } catch (e) {
                        setDisabledOthers(false);
                        if (statusEl) statusEl.innerHTML =
                            '<span class="text-danger">Gagal cek data. Coba lagi.</span>';
                        setSubmitEnabled(false);
                    }
                });
            }

            // ganti bulan/tahun → cek paid
            if (selBulan) selBulan.addEventListener('change', checkPaid);
            if (inpTahun) inpTahun.addEventListener('input', checkPaid);

            // hard guard sebelum submit (kalau warga_id kosong, jangan sampai lolos)
            const form = document.getElementById('form-infaq-modal');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const wargaId = (elWargaId?.value || '').trim();
                    if (!wargaId) {
                        e.preventDefault();
                        if (statusEl) {
                            statusEl.innerHTML =
                                '<span class="text-danger">Warga belum valid. Pastikan HP sudah terdaftar (lookup berhasil) sehingga warga_id terisi.</span>';
                        }
                        setSubmitEnabled(false);
                    }
                });
            }
        });
    </script>
@endpush

<!-- Modal: Tambah Infaq -->
<div class="modal fade" id="modalCreateInfaq" tabindex="-1" aria-labelledby="modalCreateInfaqLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="form-infaq-modal" method="POST" action="{{ route('sosial.infaq.store') }}">
                @csrf

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
                                placeholder="08xxxxxxxxxx" required>
                            <div class="form-text">Ketik nomor lalu keluar dari kolom ini (blur) untuk auto-lookup.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="nama" id="nama-modal">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">RT</label>
                            <input type="text" class="form-control" name="rt" id="rt-modal">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">No Rumah</label>
                            <input type="text" class="form-control" name="no" id="no-modal">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Alamat</label>
                            <input type="text" class="form-control" name="alamat" id="alamat-modal">
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- PIN --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">PIN (opsional)</label>
                            <input type="text" class="form-control" name="pin" id="pin-modal" maxlength="16"
                                placeholder="mis: 123456">
                            <div class="form-text">Jika diisi, akan menjadi PIN login warga. Jika centang "Generate",
                                PIN manual akan diabaikan.</div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="auto-pin-modal"
                                    name="auto_pin">
                                <label class="form-check-label" for="auto-pin-modal">Generate PIN otomatis (6
                                    digit)</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- PEMBAYARAN --}}
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Bulan <span class="text-danger">*</span></label>
                            <select class="form-select" name="bulan" id="bulan-modal" required>
                                @foreach (\App\Models\InfaqSosial::monthColumns() as $b)
                                    <option value="{{ $b }}">{{ ucfirst($b) }}</option>
                                @endforeach
                            </select>
                            <div id="paid-hint-modal" class="form-text text-danger d-none">
                                Bulan ini SUDAH LUNAS untuk nomor HP tersebut.
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="nominal" id="nominal-modal"
                                    min="1000" step="1000" required>
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
            const fields = ['nama-modal', 'rt-modal', 'no-modal', 'alamat-modal'];
            const statusEl = document.getElementById('status-warga-modal');
            const paidHint = document.getElementById('paid-hint-modal');
            const selBulan = document.getElementById('bulan-modal');
            const btnSubmit = document.getElementById('btn-submit-modal');
            const inpPin = document.getElementById('pin-modal');
            const chkAuto = document.getElementById('auto-pin-modal');

            // tombol nominal cepat
            document.querySelectorAll('.set-nominal-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('nominal-modal').value = btn.dataset.amount;
                });
            });

            // lock/unlock field identitas
            function setDisabledOthers(disabled) {
                fields.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.disabled = disabled;
                });
            }

            // reset form setiap kali modal dibuka
            const modalEl = document.getElementById('modalCreateInfaq');
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', () => {
                    const form = document.getElementById('form-infaq-modal');
                    if (form) form.reset();
                    setDisabledOthers(false);
                    statusEl.textContent = '';
                    paidHint.classList.add('d-none');
                    btnSubmit.disabled = false;
                    // pastikan input PIN aktif saat awal
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

            // cek lunas untuk (hp, bulan)
            async function checkPaid() {
                const hp = (elHp.value || '').trim();
                const bulan = selBulan.value;
                if (!hp || !bulan) return;

                try {
                    const url =
                        `{{ route('sosial.infaq.check') }}?hp=${encodeURIComponent(hp)}&bulan=${encodeURIComponent(bulan)}`;
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();

                    const paid = !!data.paid;
                    paidHint.classList.toggle('d-none', !paid);
                    btnSubmit.disabled = paid;
                } catch (e) {
                    // jika gagal cek, jangan blokir submit
                    paidHint.classList.add('d-none');
                    btnSubmit.disabled = false;
                }
            }

            // lookup warga by HP saat blur
            if (elHp) {
                elHp.addEventListener('blur', async function() {
                    const hp = elHp.value.trim();
                    if (!hp) return;

                    statusEl.textContent = 'Mencari data warga...';

                    try {
                        const url = `{{ route('sosial.infaq.lookup') }}?hp=${encodeURIComponent(hp)}`;
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();

                        if (data.found) {
                            document.getElementById('nama-modal').value = data.data.nama ?? '';
                            document.getElementById('rt-modal').value = data.data.rt ?? '';
                            document.getElementById('no-modal').value = data.data.no ?? '';
                            document.getElementById('alamat-modal').value = data.data.alamat ?? '';

                            setDisabledOthers(true);
                            statusEl.innerHTML =
                                '<span class="text-success">Data ditemukan. Field otomatis dikunci agar tidak duplikat.</span>';
                        } else {
                            fields.forEach(id => {
                                const f = document.getElementById(id);
                                if (f) f.value = '';
                            });
                            setDisabledOthers(false);
                            statusEl.innerHTML =
                                '<span class="text-warning">Nomor belum terdaftar. Silakan isi data warga baru.</span>';
                        }
                    } catch (e) {
                        setDisabledOthers(false);
                        statusEl.innerHTML =
                            '<span class="text-danger">Gagal cek data. Coba lagi.</span>';
                    }

                    // setelah lookup, cek paid
                    checkPaid();
                });
            }

            // ganti bulan → cek paid
            if (selBulan) selBulan.addEventListener('change', checkPaid);
        });
    </script>
@endpush

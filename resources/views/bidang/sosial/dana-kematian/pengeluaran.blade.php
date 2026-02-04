@extends('layouts.app')

@section('content')
    <div class="container py-3">
        <h3 class="mb-4">Pengeluaran Dana Kematian</h3>

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#santunan">
                    Santunan Kematian
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#operasional">
                    Operasional Dana Kematian
                </button>
            </li>
        </ul>

        {{-- Modal Konfirmasi --}}
        <div class="modal fade" id="dkConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Pengeluaran Dana Kematian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-2">Mohon periksa kembali sebelum menyimpan:</div>
                        <ul class="mb-0">
                            <li><b>Tanggal:</b> <span id="dkConfirmTanggal">-</span></li>
                            <li><b>Jenis:</b> <span id="dkConfirmJenis">-</span></li>
                            <li><b>Nominal:</b> Rp <span id="dkConfirmAmount">0</span></li>
                            <li><b>Metode:</b> <span id="dkConfirmMetode">-</span></li>
                            <li><b>Warga:</b> <span id="dkConfirmWarga">-</span></li>
                            <li><b>Akun Beban:</b> <span id="dkConfirmAkunBeban">-</span></li>
                            <li><b>Akun Kas/Bank (Kredit):</b> <span id="dkConfirmAkunKasBank">-</span></li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="dkConfirmSubmit">Ya, Simpan</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content">
            @include('bidang.sosial.dana-kematian._form-santunan')
            @include('bidang.sosial.dana-kematian._form-operasional')
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        (function() {
            // =========================
            // Modal bootstrap
            // =========================
            const modalEl = document.getElementById('dkConfirmModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

            let pendingForm = null;
            let saldoWarga = 0;

            // =========================
            // State per-form (2 form: santunan & operasional)
            // =========================
            const saldoKasBankByForm = new WeakMap(); // form -> number
            const saldoKasBankLoaded = new WeakMap(); // form -> boolean
            const kasBankInfoByForm = new WeakMap(); // form -> { akun_id, akun_nama }

            function fmt(n) {
                n = Number(n || 0);
                return n.toLocaleString('id-ID');
            }

            function setText(id, value) {
                const el = document.getElementById(id);
                if (el) el.textContent = value ?? '-';
            }

            function selectedOptionText(sel) {
                if (!sel) return '-';
                const opt = sel.options[sel.selectedIndex];
                return opt ? opt.textContent.trim() : '-';
            }

            // =========================
            // Resolve akun beban operasional (child > parent)
            // =========================
            function resolveAkunBebanOperasionalText(form) {
                const parentSel = form.querySelector('#akun_keuangan_operasional');
                const childSel = form.querySelector('#parent_akun_id_operasional');
                const childWrap = document.getElementById(
                    'parent-akun-container-operasional'); // wrapper memang global id

                // kalau child sedang tampil dan dipilih, gunakan child
                if (childWrap && childWrap.style.display !== 'none' && childSel && childSel.value) {
                    return selectedOptionText(childSel);
                }
                return selectedOptionText(parentSel);
            }

            // =========================
            // Fetch saldo kas/bank + info akun dari endpoint
            // =========================
            async function fetchSaldoKasBank(metode) {
                const urlTpl = @json(route('dana_kematian.saldo_kasbank', ['metode' => 'METODE']));
                const url = urlTpl.replace('METODE', metode);

                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const msg = json?.error || 'Gagal memuat saldo kas/bank';
                    throw new Error(msg);
                }

                return {
                    saldo: Number(json?.saldo || 0),
                    akun_id: json?.akun_id ?? null,
                    akun_nama: (json?.akun_nama || '').trim(),
                };
            }

            // =========================
            // Validasi form (POOL + Warga (santunan) + Kas/Bank)
            // =========================
            function validateForm(form) {
                const saldoPool = Number(form.dataset.saldo || 0);
                const amount = Number(form.querySelector('.dk-amount')?.value || 0);

                const warnPool = form.querySelector('.dk-warning-saldo');
                const warnWarga = form.querySelector('.dk-warning-warga');
                const warnKasBank = form.querySelector('.dk-warning-kasbank');
                const btn = form.querySelector('.dk-submit-btn');

                const saldoKasBank = Number(saldoKasBankByForm.get(form) || 0);
                const kasBankReady = Boolean(saldoKasBankLoaded.get(form));

                let ok = true;

                // 1) cek saldo POOL
                if (amount > 0 && amount > saldoPool) {
                    warnPool?.classList.remove('d-none');
                    ok = false;
                } else {
                    warnPool?.classList.add('d-none');
                }

                // 2) cek saldo warga khusus santunan
                if (form.dataset.jenis === 'santunan') {
                    if (amount > 0 && saldoWarga > 0 && amount > saldoWarga) {
                        warnWarga?.classList.remove('d-none');
                        ok = false;
                    } else {
                        warnWarga?.classList.add('d-none');
                    }
                } else {
                    warnWarga?.classList.add('d-none');
                }

                // 3) cek saldo kas/bank (hanya jika saldo kas/bank sudah berhasil dimuat)
                if (kasBankReady && amount > 0 && amount > saldoKasBank) {
                    warnKasBank?.classList.remove('d-none');
                    ok = false;
                } else {
                    warnKasBank?.classList.add('d-none');
                }

                if (btn) btn.disabled = !ok;
                return ok;
            }

            function getTanggalTransaksi(form) {
                return form.querySelector('input[name="tanggal_transaksi"]')?.value || '-';
            }

            // =========================
            // Refresh saldo kas/bank per form
            // =========================
            async function refreshSaldoKasBankForForm(form) {
                const metode = form.querySelector('.dk-metode')?.value || 'tunai';
                const out = form.querySelector('[data-dk-saldo-kasbank]');

                saldoKasBankLoaded.set(form, false);
                saldoKasBankByForm.set(form, 0);
                kasBankInfoByForm.set(form, {
                    akun_id: null,
                    akun_nama: '-'
                });

                if (out) out.value = 'Memuat...';

                try {
                    const data = await fetchSaldoKasBank(metode);

                    saldoKasBankByForm.set(form, data.saldo);
                    saldoKasBankLoaded.set(form, true);
                    kasBankInfoByForm.set(form, {
                        akun_id: data.akun_id,
                        akun_nama: data.akun_nama || '-',
                    });

                    if (out) out.value = 'Rp ' + fmt(data.saldo);
                } catch (e) {
                    saldoKasBankByForm.set(form, 0);
                    saldoKasBankLoaded.set(form, false);
                    kasBankInfoByForm.set(form, {
                        akun_id: null,
                        akun_nama: '-'
                    });

                    if (out) out.value = e?.message || 'Gagal memuat saldo';
                }

                validateForm(form);
            }

            // =========================
            // INIT semua form
            // =========================
            document.querySelectorAll('.dana-kematian-form').forEach(form => {
                saldoKasBankByForm.set(form, 0);
                saldoKasBankLoaded.set(form, false);
                kasBankInfoByForm.set(form, {
                    akun_id: null,
                    akun_nama: '-'
                });

                // init kas/bank
                refreshSaldoKasBankForForm(form);

                // realtime validate amount
                form.querySelector('.dk-amount')?.addEventListener('input', () => validateForm(form));

                // change metode => refresh saldo kas/bank
                form.querySelector('.dk-metode')?.addEventListener('change', () => refreshSaldoKasBankForForm(
                    form));

                // click submit => validate then show modal
                form.querySelector('.dk-submit-btn')?.addEventListener('click', () => {
                    // extra guard untuk operasional: wajib pilih akun sebelum modal
                    if (form.dataset.jenis === 'operasional') {
                        const akunSel = form.querySelector('#akun_keuangan_operasional');
                        if (!akunSel || !akunSel.value) {
                            akunSel?.focus();
                            return;
                        }
                    }

                    if (!validateForm(form)) return;

                    pendingForm = form;

                    const tgl = form.querySelector('input[name="tanggal_transaksi"]');
                    if (!tgl || !tgl.value) {
                        tgl?.focus();
                        return;
                    }

                    const jenis = form.dataset.jenis;
                    const amount = form.querySelector('.dk-amount')?.value || 0;
                    const metode = form.querySelector('select[name="metode_bayar"]')?.value || '-';

                    let wargaText = '-';
                    if (jenis === 'santunan') {
                        const opt = document.querySelector('#dk-warga option:checked');
                        wargaText = opt ? opt.textContent.trim() : '-';
                    }

                    // isi modal dasar
                    setText('dkConfirmTanggal', getTanggalTransaksi(form));
                    setText('dkConfirmJenis', (jenis === 'santunan') ? 'Santunan' : 'Operasional');
                    setText('dkConfirmAmount', fmt(amount));
                    setText('dkConfirmMetode', metode);
                    setText('dkConfirmWarga', wargaText);

                    // isi akun beban
                    if (jenis === 'operasional') {
                        setText('dkConfirmAkunBeban', resolveAkunBebanOperasionalText(form));
                    } else {
                        setText('dkConfirmAkunBeban', 'Beban Santunan Dana Kematian');
                    }

                    // isi akun kas/bank (kredit)
                    const kasInfo = kasBankInfoByForm.get(form) || {
                        akun_nama: '-'
                    };
                    setText('dkConfirmAkunKasBank', kasInfo.akun_nama || '-');

                    modal?.show();
                });
            });

            // submit on confirm
            document.getElementById('dkConfirmSubmit')?.addEventListener('click', () => {
                if (!pendingForm) return;
                modal?.hide();
                pendingForm.submit();
            });

            // =========================
            // AJAX saldo warga (khusus santunan)
            // =========================
            const wargaSelect = document.getElementById('dk-warga');
            if (wargaSelect) {
                wargaSelect.addEventListener('change', async function() {
                    saldoWarga = 0;
                    const out = document.getElementById('dk-saldo-warga');
                    const wargaId = this.value;

                    if (!wargaId) {
                        if (out) out.value = '—';
                        document.querySelectorAll('.dana-kematian-form').forEach(validateForm);
                        return;
                    }

                    try {
                        const urlTpl = @json(route('sosial.dana_kematian.saldo', ['warga' => 'WARGA_ID']));
                        const res = await fetch(urlTpl.replace('WARGA_ID', wargaId), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const json = await res.json().catch(() => ({}));

                        saldoWarga = Number(json?.saldo || 0);
                        if (out) out.value = 'Rp ' + fmt(saldoWarga);
                    } catch (e) {
                        if (out) out.value = 'Gagal memuat saldo';
                    }

                    document.querySelectorAll('.dana-kematian-form').forEach(validateForm);
                });
            }
        })();
    </script>
@endpush

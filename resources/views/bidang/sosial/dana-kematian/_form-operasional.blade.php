<div class="tab-pane fade" id="operasional" role="tabpanel">
    <form method="POST" action="{{ route('sosial.dana-kematian.store', 'operasional') }}"
        class="card card-body dana-kematian-form" data-jenis="operasional" data-saldo="{{ (float) ($saldoPool ?? 0) }}">

        @csrf

        <h5 class="mb-3">Form Operasional Dana Kematian</h5>

        {{-- Warning saldo POOL --}}
        <div class="alert alert-warning d-none dk-warning-saldo small">
            <i class="bi bi-exclamation-triangle"></i>
            Saldo Dana Kematian (POOL) tidak mencukupi untuk nominal ini.
        </div>

        {{-- Warning saldo kas/bank --}}
        <div class="alert alert-danger d-none dk-warning-kasbank small">
            <i class="bi bi-exclamation-triangle"></i>
            Saldo kas/bank tidak mencukupi untuk metode pembayaran yang dipilih.
        </div>

        {{-- Saldo POOL (readonly) --}}
        <div class="mb-3">
            <label class="form-label">Saldo Dana Kematian (POOL) Saat Ini</label>
            <input type="text" class="form-control text-end fw-bold"
                value="Rp {{ number_format((float) ($saldoPool ?? 0), 0, ',', '.') }}" readonly>
        </div>
        
        <div class="row">
            {{-- Nominal (Kolom Kiri) --}}
            <div class="col-md-6">
                {{-- Tanggal Transaksi --}}
                <div class="mb-3">
                    <label class="form-label">Tanggal Transaksi</label>
                    <input type="date" name="tanggal_transaksi" class="form-control dk-tanggal"
                        value="{{ now()->toDateString() }}" required>
                </div>
            </div>
            <div class="col-md-6">
                {{-- Nominal --}}
                <div class="mb-3">
                    <label class="form-label">Nominal Pengeluaran</label>
                    <input type="number" name="amount" class="form-control dk-amount" min="1" required>
                    <div class="form-text">
                        Sistem akan menolak transaksi jika nominal melebihi saldo POOL maupun saldo kas/bank sesuai
                        metode.
                    </div>
                </div>
            </div>
        </div>

        {{-- Asal Akun (khusus beban/expense) --}}
        <div class="mb-3">
            <label class="form-label mb-2" id="akun-label-operasional">Asal Akun (Beban)</label>
            <select class="form-control" name="akun_keuangan_id" id="akun_keuangan_operasional" required>
                <option value="">Pilih Akun</option>
            </select>
        </div>

        <div class="mb-3" id="parent-akun-container-operasional" style="display:none;">
            <label class="mb-2">Sub Akun (Opsional)</label>
            <select class="form-control" name="parent_akun_id" id="parent_akun_id_operasional">
                <option value="">Pilih Sub Akun</option>
            </select>
        </div>


        <div class="row">
            {{-- Nominal (Kolom Kiri) --}}
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Metode Pembayaran</label>
                    <select name="metode_bayar" class="form-select dk-metode" required>
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
            </div>

            {{-- Metode pembayaran (Kolom Kanan) --}}
            <div class="col-md-6">
                {{-- Saldo kas/bank (AJAX) --}}
                <div class="mb-3">
                    <label class="form-label">Saldo Kas/Bank Sesuai Metode</label>
                    <input type="text" class="form-control text-end" data-dk-saldo-kasbank value="—" readonly>
                </div>
            </div>
        </div>

        {{-- Deskripsi --}}
        <div class="mb-3">
            <label class="form-label">Keterangan Operasional</label>
            <textarea name="deskripsi" rows="2" class="form-control" required>Biaya operasional kegiatan pemakaman</textarea>
        </div>

        <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle"></i>
            Pengeluaran ini <b>tidak mengurangi hutang warga</b>, hanya mengurangi dana kematian (POOL).
        </div>

        <button type="button" class="btn btn-warning dk-submit-btn">
            <i class="bi bi-cash-stack me-1"></i>
            Simpan Operasional
        </button>
    </form>
</div>

<script>
    // Data dari backend (global)
    const akunTanpaParent = @json($akunTanpaParent);
    const akunDenganParent = @json($akunDenganParent);

    let operasionalAkunLoaded = false;

    function resetSubAkunOperasional() {
        const parentContainer = document.getElementById('parent-akun-container-operasional');
        const parentSelect = document.getElementById('parent_akun_id_operasional');
        if (!parentContainer || !parentSelect) return;

        parentSelect.innerHTML = '<option value="">Pilih Sub Akun</option>';
        parentContainer.style.display = 'none';
    }

    function populateAkunOperasionalExpenseOnly() {
        const select = document.getElementById('akun_keuangan_operasional');
        if (!select) return;

        select.innerHTML = '<option value="">Pilih Akun</option>';

        akunTanpaParent
            .filter(a => a.tipe_akun === 'expense')
            .forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = (a.kode_akun ? a.kode_akun + ' - ' : '') + a.nama_akun;
                select.appendChild(opt);
            });

        resetSubAkunOperasional();
    }

    function populateSubAkunOperasional(parentId) {
        const parentContainer = document.getElementById('parent-akun-container-operasional');
        const parentSelect = document.getElementById('parent_akun_id_operasional');
        if (!parentSelect || !parentContainer) return;

        parentSelect.innerHTML = '<option value="">Pilih Sub Akun</option>';

        const children = akunDenganParent[parentId] || akunDenganParent[String(parentId)] || [];

        // jika Anda ingin child juga dipaksa expense (opsional, aman)
        const filtered = children.filter(a => a.tipe_akun === 'expense');

        if (filtered.length > 0) {
            filtered.forEach(a => {
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

    function bindOperasionalAkunEventsOnce() {
        const select = document.getElementById('akun_keuangan_operasional');
        if (!select) return;

        // Hindari double-binding kalau tab dibuka berkali-kali
        if (select.dataset.bound === '1') return;
        select.dataset.bound = '1';

        select.addEventListener('change', (e) => {
            const parentId = e.target.value;

            if (!parentId) {
                resetSubAkunOperasional();
                return;
            }

            populateSubAkunOperasional(parentId);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Saat tab operasional dibuka baru populate + bind event
        const tabTrigger =
            document.querySelector('[data-bs-toggle="tab"][data-bs-target="#operasional"]') ||
            document.querySelector('[data-bs-toggle="pill"][data-bs-target="#operasional"]') ||
            document.querySelector('[data-bs-toggle="tab"][href="#operasional"]') ||
            document.querySelector('[data-bs-toggle="pill"][href="#operasional"]');

        if (tabTrigger) {
            tabTrigger.addEventListener('shown.bs.tab', () => {
                if (!operasionalAkunLoaded) {
                    populateAkunOperasionalExpenseOnly();
                    bindOperasionalAkunEventsOnce();
                    operasionalAkunLoaded = true;
                }
            });
        }

        // Jika tab operasional adalah tab default aktif saat load, langsung isi
        const operasionalPane = document.getElementById('operasional');
        if (operasionalPane && operasionalPane.classList.contains('active') && !operasionalAkunLoaded) {
            populateAkunOperasionalExpenseOnly();
            bindOperasionalAkunEventsOnce();
            operasionalAkunLoaded = true;
        }
    });
</script>

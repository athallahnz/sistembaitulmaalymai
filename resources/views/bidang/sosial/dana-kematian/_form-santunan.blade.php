<div class="tab-pane fade show active" id="santunan" role="tabpanel">
    <form method="POST" action="{{ route('sosial.dana-kematian.store', 'santunan') }}"
        class="card card-body dana-kematian-form" data-jenis="santunan" data-saldo="{{ (float) ($saldoPool ?? 0) }}">

        @csrf

        <h5 class="mb-3">Form Santunan Kematian</h5>

        {{-- Warning saldo POOL --}}
        <div class="alert alert-warning d-none dk-warning-saldo small">
            <i class="bi bi-exclamation-triangle"></i>
            Saldo Dana Kematian (POOL) tidak mencukupi untuk nominal ini.
        </div>

        {{-- Warning saldo warga --}}
        <div class="alert alert-danger d-none dk-warning-warga small">
            <i class="bi bi-exclamation-triangle"></i>
            Saldo hutang warga tidak mencukupi untuk santunan ini.
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

        {{-- Saldo hutang warga (AJAX) --}}
        <div class="mb-3">
            <label class="form-label">Saldo Hutang Dana Kematian Warga (Outstanding)</label>
            <input type="text" class="form-control text-end" id="dk-saldo-warga" value="—" readonly>
            <div class="form-text">Akan otomatis terisi setelah memilih warga.</div>
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
            {{-- Metode pembayaran (Kolom Kanan) --}}
            <div class="col-md-6">
                {{-- Warga --}}
                <div class="mb-3">
                    <label class="form-label">Warga (Kepala Keluarga)</label>
                    <select name="warga_kepala_id" class="form-select" id="dk-warga" required>
                        <option value="">— Pilih Warga —</option>
                        @foreach ($wargas as $w)
                            <option value="{{ $w->id }}">
                                {{ $w->nama }} (RT {{ $w->rt }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Nominal --}}
        <div class="mb-3">
            <label class="form-label">Nominal Santunan</label>
            <input type="number" name="amount" class="form-control dk-amount" min="1" required>
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
            <label class="form-label">Keterangan</label>
            <textarea name="deskripsi" rows="2" class="form-control" required>Santunan kematian warga</textarea>
        </div>

        <button type="button" class="btn btn-danger dk-submit-btn">
            <i class="bi bi-heart-fill me-1"></i>
            Simpan Santunan
        </button>
    </form>
</div>

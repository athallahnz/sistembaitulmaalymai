@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-2">Dashboard <strong>Infaq Bulanan</strong></h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateInfaq">
                + Tambah Infaq Bulanan
            </button>
        </div>

        @include('bidang.sosial.infaq._modal_add_infaq')

        @if (session('generated_pin'))
            <div class="alert alert-info">
                PIN warga baru: <strong>{{ session('generated_pin') }}</strong><br>
                Berikan PIN ini ke warga untuk login di <a href="{{ route('warga.login.form') }}">halaman tracking</a>.
            </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Status Infaq</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($wargas as $warga)
                    <tr>
                        <td>{{ $warga->nama }}</td>
                        <td>
                            {{ optional($warga->infaq)->total > 0 ? 'Sudah Bayar' : 'Belum Bayar' }}
                        </td>
                        <td>
                            <a href="{{ route('sosial.infaq.detail', $warga->id) }}" class="btn btn-info btn-sm">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Belum ada data warga</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- ============== MODAL CREATE INFAQ ============== --}}
        <div class="modal fade" id="modalCreateInfaq" tabindex="-1" aria-labelledby="modalCreateInfaqLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('sosial.infaq.store') }}" id="form-infaq-modal">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalCreateInfaqLabel">Tambah Infaq Bulanan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            {{-- badge status di bawah field --}}
                            @php $paid = isset($infaq) ? ((float)$infaq->$bulan > 0) : false; @endphp
                            @if ($paid)
                                <div class="form-text text-danger">Bulan ini sudah LUNAS. Nilai tidak bisa diubah.</div>
                            @endif

                            {{-- ALERT sukses dari session (opsional) --}}
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            {{-- DATA WARGA --}}
                            <div class="card mb-3">
                                <div class="card-header fw-semibold">Data Warga</div>
                                <div class="card-body row g-3">

                                    <div class="col-md-4">
                                        <label class="form-label">Nomor HP <span class="text-danger">*</span></label>
                                        <input type="text" name="hp" id="hp-modal" class="form-control"
                                            placeholder="08xxxxxxxxxx" required>
                                        <div class="form-text">Ketik nomor lalu pindah fokus untuk cek otomatis.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Nama</label>
                                        <input type="text" name="nama" id="nama-modal" class="form-control"
                                            placeholder="Nama Warga">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">RT</label>
                                        <input type="text" name="rt" id="rt-modal" class="form-control"
                                            placeholder="RT">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">No Rumah</label>
                                        <input type="text" name="no" id="no-modal" class="form-control"
                                            placeholder="No Rumah">
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Alamat</label>
                                        <input type="text" name="alamat" id="alamat-modal" class="form-control"
                                            placeholder="Alamat">
                                    </div>

                                    <div class="col-12">
                                        <div id="status-warga-modal" class="small text-muted"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- PEMBAYARAN --}}
                            <div class="card">
                                <div class="card-header fw-semibold">Pembayaran Infaq</div>
                                <div class="card-body row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Bulan <span class="text-danger">*</span></label>
                                        <select name="bulan" id="bulan-modal" class="form-select" required>
                                            <option value="" selected disabled>Pilih bulan</option>
                                            @foreach (['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember'] as $b)
                                                <option value="{{ $b }}">{{ ucfirst($b) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Nominal <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="nominal" id="nominal-modal" class="form-control"
                                                placeholder="0" min="0" step="1000" required>
                                        </div>

                                        <div class="mt-2 d-flex gap-2 flex-wrap">
                                            @foreach ([50000, 100000, 200000, 500000] as $quick)
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm set-nominal-modal"
                                                    data-amount="{{ $quick }}">
                                                    {{ number_format($quick, 0, ',', '.') }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <div class="form-text">Klik tombol cepat atau ketik manual.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-primary" type="submit">Simpan</button>
                            <button class="btn btn-outline-secondary" type="button"
                                data-bs-dismiss="modal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const elHp = document.getElementById('hp-modal');
            const fields = ['nama-modal', 'rt-modal', 'no-modal', 'alamat-modal'];
            const statusEl = document.getElementById('status-warga-modal');

            // tombol nominal cepat
            document.querySelectorAll('.set-nominal-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('nominal-modal').value = btn.dataset.amount;
                });
            });

            function setDisabledOthers(disabled) {
                fields.forEach(id => {
                    const el = document.getElementById(id);
                    el.disabled = disabled;
                });
            }

            // reset form setiap kali modal dibuka (opsional)
            const modalEl = document.getElementById('modalCreateInfaq');
            modalEl.addEventListener('show.bs.modal', () => {
                document.getElementById('form-infaq-modal').reset();
                setDisabledOthers(false);
                statusEl.textContent = '';
            });

            // lookup warga by HP saat blur
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
                        fields.forEach(id => document.getElementById(id).value = '');
                        setDisabledOthers(false);
                        statusEl.innerHTML =
                            '<span class="text-warning">Nomor belum terdaftar. Silakan isi data warga baru.</span>';
                    }
                } catch (e) {
                    setDisabledOthers(false);
                    statusEl.innerHTML = '<span class="text-danger">Gagal cek data. Coba lagi.</span>';
                }
            });
        });
    </script>
@endpush

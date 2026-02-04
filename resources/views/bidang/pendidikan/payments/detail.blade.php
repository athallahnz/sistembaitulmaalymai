@extends('layouts.app')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('payment.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2">Detail Pembayaran PMB</h1>
                <p class="text-muted mb-0">
                    <strong>{{ $student->name }}</strong> /
                    {{ $student->eduClass->name }} -
                    {{ $student->eduClass->tahun_ajaran }}
                </p>
            </div>
            <form id="form-recognize-pmb-{{ $student->id }}" action="{{ route('payment.recognize_pmb', $student->id) }}"
                method="POST">
                @csrf
                <input type="hidden" name="akun_keuangan_id" id="selected-akun-pmb-{{ $student->id }}">
                @php $canRecognize = ($remainingRecognizable ?? 0) > 0; @endphp

                <button type="button" class="btn btn-success {{ $canRecognize ? '' : 'disabled' }}"
                    {{ $canRecognize ? '' : 'disabled' }}
                    onclick="{{ $canRecognize ? "confirmRecognizePMB('{$student->name}', {$student->id})" : '' }}">
                    <i class="bi bi-check-circle"></i> Recognize Pendapatan PMB
                </button>

                @if (!$canRecognize)
                    <small class="text-muted d-block mt-1">
                        Tidak ada nominal yang bisa diakui saat ini (sudah diakui semua / belum ada pembayaran yang bisa
                        diakui).
                    </small>
                @endif
            </form>
        </div>

        {{-- Ringkasan Pembayaran --}}
        <div class="row mb-2">
            <div class="col-md-3">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <h4>Total Biaya</h4>
                        <h2 class="text-primary"><strong>Rp {{ number_format($totalBiaya, 0, ',', '.') }}</strong></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <h4>Total Dibayar</h4>
                        <h2 class="text-success"><strong>Rp {{ number_format($totalBayar, 0, ',', '.') }}</strong></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger shadow-sm">
                    <div class="card-body text-center">
                        <h4>Sisa Tanggungan</h4>
                        <h2 class="text-danger"><strong>Rp {{ number_format($sisa, 0, ',', '.') }}</strong></h2>
                        @if ($sisa <= 0)
                            <span
                                class="position-absolute top-100 start-50 translate-middle badge rounded-pill bg-success px-3 py-2">Lunas</span>
                        @else
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">Belum
                                Lunas</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info shadow-sm">
                    <div class="card-body text-center">
                        <h4>Total Diakui</h4>
                        <h2 class="text-info">
                            <strong>Rp {{ number_format($totalRecognized, 0, ',', '.') }}</strong>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Riwayat Pembayaran --}}
        <h1 class="mb-4">Riwayat Pembayaran</h1>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $index => $bayar)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($bayar->tanggal)->format('d-m-Y') }}</td>
                                <td>Rp {{ number_format($bayar->jumlah, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('payments.kwitansi.per', $bayar->id) }}" target="_blank"
                                        class="btn btn-sm btn-primary">
                                        <i class="bi bi-printer"></i> Cetak
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada pembayaran</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <a href="{{ route('payment.dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>
@endsection
@push('scripts')
    <script>
        async function confirmRecognizePMB(studentName, studentId) {
            try {
                const previewUrl = `{{ url('/payment/pmb') }}/${studentId}/recognize-preview`;
                const res = await fetch(previewUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) throw new Error('Preview gagal diambil');

                const data = await res.json();
                const amount = Number(data.amount_to_recognize || 0);
                let items = (data.coa_candidates || []).map(x => normalizeItem(x));

                if (amount <= 0) {
                    return Swal.fire({
                        title: 'Tidak Ada yang Bisa Diakui',
                        html: `Tidak ada nominal PMB yang dapat diakui untuk <b>${escapeHtml(studentName)}</b>.`,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }

                if (items.length === 0) {
                    return Swal.fire({
                        title: 'POS CoA Habis',
                        html: `Semua POS CoA PMB sudah terpenuhi.`,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }

                // Default sorting ala ERP: remaining terbesar dulu (cepat untuk filling)
                items = sortItems(items, 'remaining_desc');

                const html = renderERPModal(studentName, amount, items);

                await Swal.fire({
                    title: 'Pilih POS CoA Pengakuan',
                    html,
                    icon: 'question',
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Batal',
                    cancelButtonColor: '#6c757d',
                    width: 980,
                    didOpen: () => initERPEvents(studentName, studentId, amount, items)
                });

            } catch (e) {
                console.error(e);
                Swal.fire({
                    title: 'Terjadi Kesalahan',
                    text: 'Tidak dapat menampilkan pilihan POS CoA.',
                    icon: 'error'
                });
            }
        }

        /* ---------------- ERP UI helpers ---------------- */

        function initERPEvents(studentName, studentId, amount, initialItems) {
            const $ = (sel) => document.querySelector(sel);

            let items = [...initialItems];
            let selectedAkun = null;

            const search = $('#coaSearch');
            const sort = $('#coaSort');
            const list = $('#coaList');
            const selectedBadge = $('#selectedBadge');
            const selectedText = $('#selectedText');

            function repaint() {
                const q = (search.value || '').trim().toLowerCase();
                let filtered = items.filter(it => {
                    if (!q) return true;
                    return (
                        it.kode.toLowerCase().includes(q) ||
                        it.nama.toLowerCase().includes(q) ||
                        it.label.toLowerCase().includes(q)
                    );
                });

                filtered = sortItems(filtered, sort.value);

                list.innerHTML = filtered.map(it => renderERPCard(it, selectedAkun)).join('');

                // attach click
                list.querySelectorAll('.coa-erp-card').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        selectedAkun = Number(btn.dataset.akun);

                        // visual selected
                        repaint();

                        const picked = items.find(x => x.akun_id === selectedAkun);
                        if (!picked) return;

                        selectedBadge.classList.remove('d-none');
                        selectedText.innerHTML = `
          Dipilih: <b>${escapeHtml(picked.kode)}</b> — ${escapeHtml(picked.nama)}
          <span class="text-muted"> (Sisa: Rp ${fmt(picked.remaining)})</span>
        `;

                        // Confirmation modal (ERP-style)
                        const willFill = Math.min(amount, picked.remaining);
                        const leftover = Math.max(0, amount - willFill);

                        const confirmHtml = `
          <div class="erp-confirm">
            <div class="erp-confirm-row">
              <div class="erp-kv">
                <div class="erp-k">POS CoA</div>
                <div class="erp-v"><b>${escapeHtml(picked.kode)}</b> — ${escapeHtml(picked.nama)}</div>
              </div>
              <div class="erp-kv">
                <div class="erp-k">Sisa CoA</div>
                <div class="erp-v">Rp ${fmt(picked.remaining)}</div>
              </div>
            </div>

            <hr class="my-2"/>

            <div class="erp-confirm-row">
              <div class="erp-kv">
                <div class="erp-k">Nominal Pengakuan</div>
                <div class="erp-v"><b>Rp ${fmt(amount)}</b></div>
              </div>
              <div class="erp-kv">
                <div class="erp-k">Masuk ke CoA ini</div>
                <div class="erp-v"><b>Rp ${fmt(willFill)}</b></div>
              </div>
            </div>

            ${leftover > 0 ? `
                              <div class="mt-2 erp-note">
                                Sisa <b>Rp ${fmt(leftover)}</b> akan otomatis dialihkan ke POS CoA berikutnya yang belum terpenuhi.
                              </div>` : `
                              <div class="mt-2 erp-note">
                                Tidak ada sisa. Pengakuan selesai di POS CoA ini.
                              </div>`
            }
          </div>
        `;

                        const result = await Swal.fire({
                            title: 'Konfirmasi Pengakuan',
                            html: confirmHtml,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Proses',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#198754',
                            cancelButtonColor: '#6c757d',
                            width: 820
                        });

                        if (result.isConfirmed) {
                            const hidden = document.getElementById('selected-akun-pmb-' + studentId);
                            const form = document.getElementById('form-recognize-pmb-' + studentId);

                            if (!hidden || !form) {
                                console.error('Form / hidden input tidak ditemukan.');
                                return;
                            }

                            hidden.value = String(selectedAkun);
                            form.submit();
                        }
                    });
                });
            }

            search.addEventListener('input', repaint);
            sort.addEventListener('change', repaint);

            // first paint
            repaint();
        }

        function renderERPModal(studentName, amount, items) {
            const styles = `
  <style>
    .erp-wrap { text-align:left; }
    .erp-topbar{
      display:flex; gap:10px; align-items:center; justify-content:space-between;
      padding:10px 12px; border:1px solid #e9ecef; border-radius:10px; background:#fff;
      margin-bottom:12px;
    }
    .erp-meta { font-size:.9rem; color:#495057; }
    .erp-amount { font-size:1rem; }
    .erp-amount b{ color:#198754; }
    .erp-controls { display:flex; gap:10px; align-items:center; }
    .erp-input, .erp-select{
      border:1px solid #ced4da; border-radius:10px; padding:10px 12px;
      background:#fff; outline:none; width:100%;
    }
    .erp-input:focus, .erp-select:focus { border-color:#198754; box-shadow:0 0 0 .15rem rgba(25,135,84,.15); }
    .erp-grid{
      display:grid; grid-template-columns: repeat(2, 1fr);
      gap:12px; max-height:360px; overflow:auto; padding-right:2px;
    }
    .coa-erp-card{
      border:1px solid #dee2e6; border-radius:12px; padding:12px 14px;
      background:#fff; cursor:pointer; transition:all .12s ease-in-out;
      position:relative;
    }
    .coa-erp-card:hover{
      border-color:#198754; box-shadow:0 .25rem .75rem rgba(0,0,0,.06);
      transform:translateY(-1px);
    }
    .coa-erp-card.selected{
      border-color:#198754; box-shadow:0 0 0 .18rem rgba(25,135,84,.18);
      background: #f8fdf9;
    }
    .erp-code{ font-size:.78rem; color:#6c757d; letter-spacing:.2px; }
    .erp-name{ font-weight:650; margin-top:2px; margin-bottom:8px; color:#212529; }
    .erp-row{ display:flex; justify-content:space-between; gap:10px; align-items:center; }
    .erp-kpi{ font-size:.82rem; color:#495057; }
    .erp-kpi b{ color:#0d6efd; }
    .erp-progress{
      height:8px; background:#e9ecef; border-radius:999px; overflow:hidden; flex:1;
      margin-left:10px;
    }
    .erp-progress > div{
      height:100%; background:#198754; border-radius:999px;
      width:0%;
    }
    .erp-pill{
      font-size:.72rem; padding:4px 8px; border-radius:999px;
      background:#f1f3f5; color:#495057; border:1px solid #e9ecef;
    }
    .erp-check{
      position:absolute; top:10px; right:10px;
      width:20px; height:20px; border-radius:999px;
      border:1px solid #ced4da; display:flex; align-items:center; justify-content:center;
      font-size:.8rem; color:#198754; background:#fff;
    }
    .coa-erp-card.selected .erp-check{ border-color:#198754; background:#198754; color:#fff; }
    .erp-footer{
      margin-top:12px; padding:10px 12px; border:1px solid #e9ecef; border-radius:10px; background:#fff;
      display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;
    }
    .erp-note{ font-size:.85rem; color:#6c757d; }
    .erp-selected{ font-size:.9rem; }
    .d-none{ display:none !important; }

    /* confirm */
    .erp-confirm { text-align:left; }
    .erp-confirm-row{ display:flex; gap:12px; justify-content:space-between; flex-wrap:wrap; }
    .erp-kv{ flex:1; min-width:240px; padding:10px 12px; border:1px solid #e9ecef; border-radius:10px; background:#fff; }
    .erp-k{ font-size:.78rem; color:#6c757d; }
    .erp-v{ font-size:.95rem; color:#212529; margin-top:2px; }
    .erp-note{ padding:10px 12px; border-radius:10px; background:#f8f9fa; border:1px solid #e9ecef; }
    @media (max-width: 576px){
      .erp-grid{ grid-template-columns: 1fr; }
      .erp-topbar{ flex-direction:column; align-items:flex-start; }
      .erp-controls{ width:100%; }
    }
  </style>`;

            const topbar = `
    <div class="erp-topbar">
      <div>
        <div class="erp-meta">Siswa: <b>${escapeHtml(studentName)}</b></div>
        <div class="erp-amount">Nominal akan diakui: <b>Rp ${fmt(amount)}</b></div>
      </div>
      <div class="erp-controls" style="min-width: 420px; max-width: 520px; width:100%;">
        <input id="coaSearch" class="erp-input" placeholder="Cari kode/nama akun..." />
        <select id="coaSort" class="erp-select" style="max-width: 220px;">
          <option value="remaining_desc" selected>Sisa terbesar</option>
          <option value="remaining_asc">Sisa terkecil</option>
          <option value="kode_asc">Kode A-Z</option>
          <option value="kode_desc">Kode Z-A</option>
          <option value="progress_desc">Progress tertinggi</option>
          <option value="progress_asc">Progress terendah</option>
        </select>
      </div>
    </div>`;

            const list = `
    <div id="coaList" class="erp-grid">
      ${items.map(it => renderERPCard(it, null)).join('')}
    </div>`;

            const footer = `
    <div class="erp-footer">
      <div class="erp-note">
        Pilih 1 POS CoA. Jika sisa CoA tidak cukup, sistem akan otomatis melanjutkan ke CoA berikutnya yang belum terpenuhi.
      </div>
      <div id="selectedBadge" class="erp-selected d-none">
        <div id="selectedText"></div>
      </div>
    </div>`;

            return `${styles}<div class="erp-wrap">${topbar}${list}${footer}</div>`;
        }

        function renderERPCard(it, selectedAkun) {
            const selected = selectedAkun && Number(selectedAkun) === Number(it.akun_id);
            const pct = Math.max(0, Math.min(100, Math.round(it.progress * 100)));
            const pill = pct >= 100 ? 'Terpenuhi' : pct >= 75 ? 'Hampir' : pct >= 35 ? 'Berjalan' : 'Baru';
            const check = selected ? '✓' : '';

            return `
            <div class="coa-erp-card ${selected ? 'selected' : ''}"
            data-akun="${it.akun_id}"
            data-label="${escapeHtml(it.label)}"
            data-remaining="${it.remaining}">
            <div class="erp-check">${check}</div>
            <div class="erp-code">${escapeHtml(it.kode)}</div>
            <div class="erp-name">${escapeHtml(it.nama)}</div>

            <div class="erp-row">
                <div class="erp-kpi">Sisa: <b>Rp ${fmt(it.remaining)}</b></div>
                <div class="erp-pill">${pill}</div>
            </div>

            <div class="erp-row" style="margin-top:10px;">
                <div class="erp-kpi text-muted" style="font-size:.78rem;">
                ${fmt(it.recognized)} / ${fmt(it.total)} diakui
                </div>
                <div class="erp-progress" title="Progress pemenuhan">
                <div style="width:${pct}%;"></div>
                </div>
            </div>
            </div>
        `;
        }

        /* ---------------- Data normalization & sorting ---------------- */

        function normalizeItem(c) {
            const kode = String(c.kode_akun || '').trim();
            const nama = String(c.nama_akun || '').trim();
            const label = String(c.label_akun || `${kode} - ${nama}`).trim();

            const total = Number(c.biaya_total || 0);
            const recognized = Number(c.recognized_total || 0);
            const remaining = Number(c.remaining || 0);

            const progress = total > 0 ? Math.min(1, Math.max(0, recognized / total)) : 0;

            return {
                akun_id: Number(c.akun_id),
                label,
                kode: kode || label,
                nama: nama || label,
                total: round2(total),
                recognized: round2(recognized),
                remaining: round2(remaining),
                progress
            };
        }

        function sortItems(items, mode) {
            const arr = [...items];

            const byKode = (a, b) => a.kode.localeCompare(b.kode, 'id', {
                numeric: true,
                sensitivity: 'base'
            });
            const byRemaining = (a, b) => a.remaining - b.remaining;
            const byProgress = (a, b) => a.progress - b.progress;

            switch (mode) {
                case 'remaining_asc':
                    return arr.sort(byRemaining);
                case 'remaining_desc':
                    return arr.sort((a, b) => byRemaining(b, a));
                case 'kode_asc':
                    return arr.sort(byKode);
                case 'kode_desc':
                    return arr.sort((a, b) => byKode(b, a));
                case 'progress_asc':
                    return arr.sort(byProgress);
                case 'progress_desc':
                    return arr.sort((a, b) => byProgress(b, a));
                default:
                    return arr.sort((a, b) => byRemaining(b, a));
            }
        }

        /* ---------------- Formatting ---------------- */

        function fmt(n) {
            return Number(n || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function round2(n) {
            return Math.round(Number(n || 0) * 100) / 100;
        }

        function escapeHtml(str) {
            return String(str || '').replace(/[&<>"']/g, m =>
                ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                } [m])
            );
        }
    </script>
@endpush

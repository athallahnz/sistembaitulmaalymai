@extends('layouts.app')

@section('content')
    <div class="container py-4">
        {{-- ===== Heading + Filter + Actions ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3">

            <div class="mb-2 mb-md-2">
                <h1 class="mb-1">Dashboard <strong>Infaq Sinoman</strong></h1>
                <div class="small text-muted">Bidang Sosial • Tahun {{ $tahun }}</div>
            </div>

            <div class="d-flex flex-wrap align-items-end gap-2">

                {{-- Filter --}}
                <form action="{{ route('sosial.iuran.index') }}" method="GET" class="d-flex gap-2 align-items-end">

                    <div class="input-group">
                        <span class="input-group-text">Tahun</span>
                        <input type="number" name="tahun" class="form-control" min="2020" style="width: 50px;"
                            value="{{ $tahun }}">
                    </div>

                    <div class="input-group">
                        <span class="input-group-text">Cari</span>
                        <input type="text" name="q" class="form-control" placeholder="Nama / HP / RT / Alamat"
                            value="{{ $q }}">
                    </div>

                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                {{-- Tambah --}}
                <button type="button" class="btn btn-primary shadow" data-bs-toggle="modal"
                    data-bs-target="#modalCreateIuran">
                    <i class="bi bi-plus-circle"></i> Tambah Infaq Sinoman
                </button>

            </div>
        </div>

        {{-- ===== Ringkasan Tahun ===== --}}
        <div class="row g-3 mb-3">

            {{-- Jumlah KK --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Jumlah Kepala Keluarga</div>
                            <div class="display-6 fw-bold mb-0">{{ number_format($ringkas['jumlah_kk'] ?? 0) }}</div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-people-fill" style="font-size:2rem; color:#9a9a9a;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Jumlah Anggota Keluarga --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Jumlah Anggota Keluarga</div>
                            <div class="display-6 fw-bold mb-0">{{ number_format($ringkas['total_anggota'] ?? 0) }}</div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-people" style="font-size:2rem; color:#9a9a9a;"></i>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Total Tagihan --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Total Tagihan Sinoman</div>
                            <div class="h1 fw-bold mb-0">
                                Rp{{ number_format($ringkas['total_tagihan'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-receipt" style="font-size:2rem; color:#9a9a9a;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Terbayar --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Total Infaq Sinoman</div>
                            <div class="h1 fw-bold mb-0 text-success">
                                Rp{{ number_format($ringkas['total_terbayar'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-cash-stack" style="font-size:2rem; color:#28a745;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sisa Belum Terbayar --}}
            @php
                $sisa = ($ringkas['total_tagihan'] ?? 0) - ($ringkas['total_terbayar'] ?? 0);
            @endphp
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Sisa yang Belum Terbayar</div>
                            <div class="h1 fw-bold mb-0 text-danger">
                                Rp{{ number_format(max($sisa, 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-exclamation-circle" style="font-size:2rem; color:#dc3545;"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== Modal Tambah Iuran ===== --}}
        @include('bidang.sosial.iuran._modal_add_iuran')

        {{-- ===== Tabel Kepala Keluarga / Iuran ===== --}}
        <div class="p-3 shadow table-responsive rounded glass">
            <table id="iuran-table" class="table table-striped table-bordered align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>Nama KK</th>
                        <th>Anggota/Peserta</th>
                        <th>RT</th>
                        <th>No. HP</th>
                        <th>Status</th>
                        <th>Total Tagihan</th>
                        <th>Total Terbayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="modal fade" id="modalAnggota" tabindex="-1">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Daftar Anggota</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="fw-bold mb-2" id="modal-kk-nama"></div>
                        <ul id="modal-anggota-list" class="list-group mb-2"></ul>

                        <div class="small text-muted mt-3">
                            Total Peserta: <span id="modal-total"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // =======================
            // 1) QUICK AMOUNT BUTTONS
            // =======================
            document.querySelectorAll('.set-nominal-iuran').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = document.getElementById('nominal-bayar-modal');
                    if (target) {
                        target.value = btn.dataset.amount;
                    }
                });
            });

            // =======================
            // 2) DATATABLES IURAN KK
            // =======================
            let tahun = {{ $tahun }};

            let table = $('#iuran-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('sosial.iuran.datatable') }}",
                    data: function(d) {
                        d.tahun = tahun;
                        // kirim nilai dari form cari
                        d.q = document.querySelector('input[name="q"]')?.value || '';
                    }
                },
                columns: [{
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'peserta',
                        name: 'peserta'
                    },
                    {
                        data: 'rt',
                        name: 'rt'
                    },
                    {
                        data: 'hp',
                        name: 'hp'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'tagihan',
                        name: 'tagihan',
                        className: 'text-end'
                    },
                    {
                        data: 'bayar',
                        name: 'bayar',
                        className: 'text-end'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            // Form filter "Cari" → trigger reload DataTables, bukan reload page
            const filterForm = document.querySelector('form[action="{{ route('sosial.iuran.index') }}"]');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    table.ajax.reload();
                });
            }

            // =======================================
            // 3) TOMBOL (!) - MODAL DETAIL ANGGOTA KK
            // =======================================
            $(document).on('click', '.btn-anggota', function() {
                let id = $(this).data('id');

                $.get("{{ url('bidang/sosial/iuran/anggota') }}/" + id, function(res) {

                    let statusKepala = res.status_kepala ?
                        res.status_kepala.charAt(0).toUpperCase() + res.status_kepala.slice(1) :
                        'Tidak diketahui';

                    $('#modal-kk-nama').html(`
                    Kepala Keluarga: <strong>${res.kepala}</strong>
                    <span class="badge ${res.status_kepala === 'meninggal' ? 'text-bg-danger' : 'text-bg-success'} ms-2">
                        ${statusKepala}
                    </span>
                `);

                    let list = '';
                    if (res.anggota && res.anggota.length > 0) {
                        res.anggota.forEach((item, idx) => {
                            let badge = item.status === 'meninggal' ?
                                '<span class="badge text-bg-danger ms-2">Meninggal</span>' :
                                '<span class="badge text-bg-success ms-2">Aktif</span>';

                            list += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${idx + 1}. ${item.nama}</span>
                                ${badge}
                            </li>
                        `;
                        });
                    } else {
                        list = `<li class="list-group-item text-muted">Tidak ada anggota.</li>`;
                    }

                    $('#modal-anggota-list').html(list);
                    $('#modal-total').text((res.jumlah || 1) + " orang");

                    new bootstrap.Modal(document.getElementById('modalAnggota')).show();
                });
            });

            // ==================================================
            // 4) PREVIEW ANGGOTA DI DALAM MODAL "TAMBAH IURAN"
            // ==================================================
            const selectKepala = document.getElementById('select-warga-kepala');
            const infoAnggota = document.getElementById('info-anggota-keluarga');
            const modalCreate = document.getElementById('modalCreateIuran');

            if (selectKepala && infoAnggota) {
                const anggotaUrlTemplate = @json(route('sosial.iuran.anggota', ['kk' => '__ID__']));

                async function loadAnggota(kepalaId) {
                    if (!kepalaId) {
                        infoAnggota.classList.remove('text-danger');
                        infoAnggota.innerHTML = 'Pilih kepala keluarga untuk melihat daftar anggota/peserta.';
                        return;
                    }

                    infoAnggota.classList.remove('text-danger');
                    infoAnggota.innerHTML = 'Memuat data anggota keluarga...';

                    const url = anggotaUrlTemplate.replace('__ID__', kepalaId);

                    try {
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) throw new Error('Respon server tidak valid');

                        const data = await res.json();
                        const anggota = data.anggota || [];
                        const jumlah = data.jumlah || 1;
                        const kepala = data.kepala || '';

                        if (anggota.length === 0) {
                            infoAnggota.innerHTML = `
                            <div class="alert alert-warning small mb-0">
                                <strong>${kepala}</strong> tercatat sebagai kepala keluarga tanpa anggota tambahan.
                                <br>Jumlah peserta: <strong>${jumlah}</strong> (hanya kepala keluarga).
                            </div>
                        `;
                        } else {
                            const items = anggota.map((nama, idx) =>
                                `<li>${idx + 1}. ${nama}</li>`
                            ).join('');

                            infoAnggota.innerHTML = `
                            <div class="alert alert-info small mb-0">
                                <div class="fw-semibold mb-1">
                                    Kepala Keluarga: ${kepala}
                                </div>
                                <div class="mb-1">
                                    Jumlah peserta (kepala + anggota): <strong>${jumlah}</strong> orang
                                </div>
                                <div>Anggota keluarga:</div>
                                <ul class="mb-0 ms-3">
                                    ${items}
                                </ul>
                            </div>
                        `;
                        }

                    } catch (e) {
                        console.error(e);
                        infoAnggota.classList.add('text-danger');
                        infoAnggota.innerHTML =
                            'Gagal memuat data anggota keluarga. Coba pilih ulang kepala keluarga.';
                    }
                }

                selectKepala.addEventListener('change', function() {
                    loadAnggota(this.value);
                });

                if (modalCreate) {
                    modalCreate.addEventListener('shown.bs.modal', function() {
                        if (selectKepala.value) {
                            loadAnggota(selectKepala.value);
                        } else {
                            infoAnggota.classList.remove('text-danger');
                            infoAnggota.innerHTML =
                                'Pilih kepala keluarga untuk melihat daftar anggota/peserta.';
                        }
                    });

                    modalCreate.addEventListener('hidden.bs.modal', function() {
                        infoAnggota.classList.remove('text-danger');
                        infoAnggota.innerHTML =
                            'Pilih kepala keluarga untuk melihat daftar anggota/peserta.';
                        selectKepala.value = '';
                    });
                }
            }

        });
    </script>
@endpush

@extends('layouts.app')
@section('title', 'Manajemen Akun Keuangan')

@section('content')
    <style>
        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-10px);
            }

            40% {
                transform: translateX(10px);
            }

            60% {
                transform: translateX(-10px);
            }

            80% {
                transform: translateX(10px);
            }

            100% {
                transform: translateX(0);
            }
        }
    </style>
    <div class="container">
        <h2 class="mb-4">Daftar Akun Keuangan</h2>

        <!-- Button untuk membuka modal Create -->
        <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#akunModal">
            Tambah Akun Keuangan
        </button>

        <div class="p-3 shadow table-responsive rounded">
            <table class="table table-striped table-bordered" id="akunTable">
                <thead class="table-light">
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th>Tipe Akun</th>
                        <th>Induk Akun</th>
                        <th>Saldo Normal</th>
                        <th>Kas / Bank</th>
                        <th>Kategori Arus Kas</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Modal Create Akun Keuangan -->
        <div class="modal fade" id="akunModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="akunModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="akunForm" action="{{ route('admin.akun_keuangan.store') }}" method="POST">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title" id="akunModalLabel">Tambah Akun</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">

                                {{-- Core --}}
                                <div class="col-md-3">
                                    <label class="form-label">ID</label>
                                    <input type="number" name="id" id="create_id" class="form-control" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Kode Akun</label>
                                    <input type="text" name="kode_akun" id="create_kode_akun" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Nama Akun</label>
                                    <input type="text" name="nama_akun" id="create_nama_akun" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Tipe Akun</label>
                                    <select name="tipe_akun" id="create_tipe_akun" class="form-select" required>
                                        <option value="asset">asset</option>
                                        <option value="liability">liability</option>
                                        <option value="revenue">revenue</option>
                                        <option value="expense">expense</option>
                                        <option value="equity">equity</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Kategori PSAK</label>
                                    <select name="kategori_psak" id="create_kategori_psak" class="form-select">
                                        <option value="">-</option>
                                        <option value="aset_lancar">aset_lancar</option>
                                        <option value="aset_tidak_lancar">aset_tidak_lancar</option>
                                        <option value="liabilitas_jangka_pendek">liabilitas_jangka_pendek</option>
                                        <option value="liabilitas_jangka_panjang">liabilitas_jangka_panjang</option>
                                        <option value="aset_neto_tidak_terikat">aset_neto_tidak_terikat</option>
                                        <option value="aset_neto_terikat_temporer">aset_neto_terikat_temporer</option>
                                        <option value="aset_neto_terikat_permanen">aset_neto_terikat_permanen</option>
                                        <option value="pendapatan">pendapatan</option>
                                        <option value="beban">beban</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Pembatasan</label>
                                    <select name="pembatasan" id="create_pembatasan" class="form-select">
                                        <option value="">-</option>
                                        <option value="tidak_terikat">tidak_terikat</option>
                                        <option value="terikat_temporer">terikat_temporer</option>
                                        <option value="terikat_permanen">terikat_permanen</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Saldo Normal</label>
                                    <select name="saldo_normal" id="create_saldo_normal" class="form-select" required>
                                        <option value="debit">debit</option>
                                        <option value="kredit">kredit</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Parent</label>
                                    <select name="parent_id" id="create_parent_id" class="form-select">
                                        <option value="">-</option>
                                        @foreach ($akunKeuanganTanpaParent as $p)
                                            <option value="{{ $p->id }}">{{ $p->kode_akun }} - {{ $p->nama_akun }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label d-block">Kas / Bank?</label>
                                    <input type="hidden" name="is_kas_bank" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="create_is_kas_bank"
                                            name="is_kas_bank" value="1">
                                        <label class="form-check-label" for="create_is_kas_bank">Centang jika akun
                                            Kas/Bank</label>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Cashflow Category</label>
                                    <select name="cashflow_category" id="create_cashflow_category" class="form-select">
                                        <option value="">-</option>
                                        <option value="operasional">operasional</option>
                                        <option value="investasi">investasi</option>
                                        <option value="pendanaan">pendanaan</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Icon</label>
                                    <input type="text" name="icon" id="create_icon" class="form-control">
                                </div>

                                {{-- Dashboard block --}}
                                <div class="col-12">
                                    <hr class="my-2">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label d-block">Show on Dashboard?</label>
                                    <input type="hidden" name="show_on_dashboard" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="create_show_on_dashboard"
                                            name="show_on_dashboard" value="1">
                                        <label class="form-check-label" for="create_show_on_dashboard">Tampilkan di
                                            Dashboard</label>
                                    </div>
                                </div>

                                {{-- WRAPPER DASHBOARD FIELDS --}}
                                <div id="createDashboardFieldsWrap" class="row g-3 mt-1 d-none">

                                    <div class="col-md-3">
                                        <label class="form-label">Dashboard Scope</label>
                                        <select name="dashboard_scope" id="create_dashboard_scope" class="form-select">
                                            <option value="">-</option>
                                            <option value="BIDANG">BIDANG</option>
                                            <option value="BENDAHARA">BENDAHARA</option>
                                            <option value="YAYASAN">YAYASAN</option>
                                            <option value="BOTH">BOTH</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Dashboard Section</label>
                                        <select name="dashboard_section" id="create_dashboard_section"
                                            class="form-select">
                                            <option value="">-</option>
                                            <option value="asset">asset</option>
                                            <option value="liability">liability</option>
                                            <option value="revenue">revenue</option>
                                            <option value="expense">expense</option>
                                            <option value="kpi">kpi</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Dashboard Calc</label>
                                        <select name="dashboard_calc" id="create_dashboard_calc" class="form-select">
                                            <option value="">-</option>
                                            <option value="rollup_children_period">rollup_children_period</option>
                                            <option value="rollup_children_ytd">rollup_children_ytd</option>
                                            <option value="balance_asof">balance_asof</option>
                                            <option value="custom">custom</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Dashboard Order</label>
                                        <input type="number" name="dashboard_order" id="create_dashboard_order"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Dashboard Format</label>
                                        <select name="dashboard_format" id="create_dashboard_format" class="form-select"
                                            required>
                                            <option value="currency">currency</option>
                                            <option value="number">number</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label d-block">Dashboard Masked?</label>
                                        <input type="hidden" name="dashboard_masked" value="0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="create_dashboard_masked"
                                                name="dashboard_masked" value="1">
                                            <label class="form-check-label" for="create_dashboard_masked">Sembunyikan
                                                nilai (mask)</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Dashboard Title</label>
                                        <input type="text" name="dashboard_title" id="create_dashboard_title"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Dashboard Link Route</label>
                                        <input type="text" name="dashboard_link_route"
                                            id="create_dashboard_link_route" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Dashboard Link Param</label>
                                        <input type="text" name="dashboard_link_param"
                                            id="create_dashboard_link_param" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Dashboard Icon</label>
                                        <input type="text" name="dashboard_icon" id="create_dashboard_icon"
                                            class="form-control">
                                    </div>

                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Simpan
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal Detail Akun Keuangan -->
        <div class="modal fade" id="akunDetailModal" tabindex="-1" aria-labelledby="akunDetailModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="akunDetailModalLabel">Detail Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div id="akunDetailLoading" class="text-muted">Memuat data...</div>

                        <div id="akunDetailContent" class="d-none">
                            <div class="mb-3">
                                <div class="fw-semibold" id="detailHeaderTitle">-</div>
                                <div class="text-muted small" id="detailHeaderSub">-</div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle">
                                    <tbody id="akunDetailTableBody">
                                        {{-- diisi via JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="akunDetailError" class="alert alert-danger d-none mb-0"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" id="detailEditBtn" class="btn btn-warning" data-id="">
                            <i class="bi bi-pencil-square me-1"></i> Edit
                        </button>

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Edit Akun Keuangan -->
        <div class="modal fade" id="akunEditModal" tabindex="-1" aria-labelledby="akunEditModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="akunEditForm" method="POST" action="">
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title" id="akunEditModalLabel">Edit Akun</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body">
                            <div id="akunEditLoading" class="text-muted">Memuat data...</div>
                            <div id="akunEditError" class="alert alert-danger d-none mb-3"></div>

                            <div id="akunEditFields" class="d-none">
                                <div class="row g-3">
                                    {{-- Core --}}
                                    <div class="col-md-3">
                                        <label class="form-label">ID</label>
                                        <input type="number" name="id" id="edit_id" class="form-control"
                                            required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Kode Akun</label>
                                        <input type="text" name="kode_akun" id="edit_kode_akun" class="form-control"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Akun</label>
                                        <input type="text" name="nama_akun" id="edit_nama_akun" class="form-control"
                                            required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Tipe Akun</label>
                                        <select name="tipe_akun" id="edit_tipe_akun" class="form-select" required>
                                            <option value="asset">asset</option>
                                            <option value="liability">liability</option>
                                            <option value="revenue">revenue</option>
                                            <option value="expense">expense</option>
                                            <option value="equity">equity</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Kategori PSAK</label>
                                        <select name="kategori_psak" id="edit_kategori_psak" class="form-select">
                                            <option value="">-</option>
                                            <option value="aset_lancar">aset_lancar</option>
                                            <option value="aset_tidak_lancar">aset_tidak_lancar</option>
                                            <option value="liabilitas_jangka_pendek">liabilitas_jangka_pendek</option>
                                            <option value="liabilitas_jangka_panjang">liabilitas_jangka_panjang</option>
                                            <option value="aset_neto_tidak_terikat">aset_neto_tidak_terikat</option>
                                            <option value="aset_neto_terikat_temporer">aset_neto_terikat_temporer</option>
                                            <option value="aset_neto_terikat_permanen">aset_neto_terikat_permanen</option>
                                            <option value="pendapatan">pendapatan</option>
                                            <option value="beban">beban</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Pembatasan</label>
                                        <select name="pembatasan" id="edit_pembatasan" class="form-select">
                                            <option value="">-</option>
                                            <option value="tidak_terikat">tidak_terikat</option>
                                            <option value="terikat_temporer">terikat_temporer</option>
                                            <option value="terikat_permanen">terikat_permanen</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Saldo Normal</label>
                                        <select name="saldo_normal" id="edit_saldo_normal" class="form-select" required>
                                            <option value="debit">debit</option>
                                            <option value="kredit">kredit</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Parent</label>
                                        <select name="parent_id" id="edit_parent_id" class="form-select">
                                            <option value="">-</option>
                                            @foreach ($akunKeuanganTanpaParent as $p)
                                                <option value="{{ $p->id }}">{{ $p->kode_akun }} -
                                                    {{ $p->nama_akun }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label d-block">Kas / Bank?</label>
                                        <input type="hidden" name="is_kas_bank" value="0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="edit_is_kas_bank"
                                                name="is_kas_bank" value="1">
                                            <label class="form-check-label" for="edit_is_kas_bank">Centang jika akun
                                                Kas/Bank</label>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Cashflow Category</label>
                                        <select name="cashflow_category" id="edit_cashflow_category" class="form-select">
                                            <option value="">-</option>
                                            <option value="operasional">operasional</option>
                                            <option value="investasi">investasi</option>
                                            <option value="pendanaan">pendanaan</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Icon</label>
                                        <input type="text" name="icon" id="edit_icon" class="form-control">
                                    </div>

                                    {{-- Dashboard block --}}
                                    <div class="col-12">
                                        <hr class="my-2">
                                    </div>

                                    {{-- Switch Show on Dashboard --}}
                                    <div class="col-md-3">
                                        <label class="form-label d-block">Show on Dashboard?</label>
                                        <input type="hidden" name="show_on_dashboard" value="0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="edit_show_on_dashboard"
                                                name="show_on_dashboard" value="1">
                                            <label class="form-check-label" for="edit_show_on_dashboard">Tampilkan di
                                                Dashboard</label>
                                        </div>
                                    </div>

                                    {{-- WRAPPER DASHBOARD FIELDS (DISEMBUNYIKAN KETIKA OFF) --}}
                                    <div id="dashboardFieldsWrap" class="row g-3 mt-1 d-none">
                                        <div class="col-md-3">
                                            <label class="form-label">Dashboard Scope</label>
                                            <select name="dashboard_scope" id="edit_dashboard_scope" class="form-select">
                                                <option value="">-</option>
                                                <option value="BIDANG">BIDANG</option>
                                                <option value="BENDAHARA">BENDAHARA</option>
                                                <option value="YAYASAN">YAYASAN</option>
                                                <option value="BOTH">BOTH</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Dashboard Section</label>
                                            <select name="dashboard_section" id="edit_dashboard_section"
                                                class="form-select">
                                                <option value="">-</option>
                                                <option value="asset">asset</option>
                                                <option value="liability">liability</option>
                                                <option value="revenue">revenue</option>
                                                <option value="expense">expense</option>
                                                <option value="kpi">kpi</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Dashboard Calc</label>
                                            <select name="dashboard_calc" id="edit_dashboard_calc" class="form-select">
                                                <option value="">-</option>
                                                <option value="rollup_children_period">rollup_children_period</option>
                                                <option value="rollup_children_ytd">rollup_children_ytd</option>
                                                <option value="balance_asof">balance_asof</option>
                                                <option value="custom">custom</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Dashboard Order</label>
                                            <input type="number" name="dashboard_order" id="edit_dashboard_order"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Dashboard Format</label>
                                            <select name="dashboard_format" id="edit_dashboard_format"
                                                class="form-select" required>
                                                <option value="currency">currency</option>
                                                <option value="number">number</option>
                                            </select>
                                        </div>

                                        {{-- Switch Dashboard Masked --}}
                                        <div class="col-md-3">
                                            <label class="form-label d-block">Dashboard Masked?</label>
                                            <input type="hidden" name="dashboard_masked" value="0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                    id="edit_dashboard_masked" name="dashboard_masked" value="1">
                                                <label class="form-check-label" for="edit_dashboard_masked">Sembunyikan
                                                    nilai (mask)</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Dashboard Title</label>
                                            <input type="text" name="dashboard_title" id="edit_dashboard_title"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Dashboard Link Route</label>
                                            <input type="text" name="dashboard_link_route"
                                                id="edit_dashboard_link_route" class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Dashboard Link Param</label>
                                            <input type="text" name="dashboard_link_param"
                                                id="edit_dashboard_link_param" class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Dashboard Icon</label>
                                            <input type="text" name="dashboard_icon" id="edit_dashboard_icon"
                                                class="form-control">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Simpan
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
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

            /* =========================
             * DOM REFERENCES (WAJIB DI ATAS)
             * ========================= */
            const detailModalEl = document.getElementById('akunDetailModal');
            const editModalEl = document.getElementById('akunEditModal');
            const detailEditBtn = document.getElementById('detailEditBtn');

            const detailLoading = document.getElementById('akunDetailLoading');
            const detailContent = document.getElementById('akunDetailContent');
            const detailError = document.getElementById('akunDetailError');
            const detailTbody = document.getElementById('akunDetailTableBody');

            const editLoading = document.getElementById('akunEditLoading');
            const editFields = document.getElementById('akunEditFields');
            const editError = document.getElementById('akunEditError');

            const akunCache = new Map();

            /* =========================
             * DataTables
             * ========================= */
            $('#akunTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.akun_keuangan.datatable') }}",
                columns: [{
                        data: 'kode_akun',
                        name: 'kode_akun'
                    },
                    {
                        data: 'nama_akun',
                        name: 'nama_akun'
                    },
                    {
                        data: 'tipe_akun',
                        name: 'tipe_akun'
                    },
                    {
                        data: 'parent',
                        name: 'parent',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'saldo_normal',
                        name: 'saldo_normal'
                    },
                    {
                        data: 'kas_bank',
                        name: 'is_kas_bank',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'cashflow',
                        name: 'cashflow_category',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                ],
            });

            /* =========================
             * Helpers
             * ========================= */
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.value = (val ?? '');
            };

            const setChecked = (id, val) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.checked = !!val;
            };

            const yesNo = v => v ?
                '<span class="badge bg-success">Ya</span>' :
                '<span class="badge bg-secondary">Tidak</span>';

            const badge = (v, map) => {
                if (!v) return '-';
                const m = map[v] || {
                    cls: 'bg-secondary',
                    text: v
                };
                return `<span class="badge ${m.cls}">${m.text}</span>`;
            };

            async function fetchAkun(id) {
                if (akunCache.has(id)) return akunCache.get(id);

                const url = `{{ route('admin.akun_keuangan.detail_json', ':id') }}`.replace(':id', id);
                const res = await fetch(url, {
                    headers: {
                        Accept: 'application/json'
                    }
                });

                if (!res.ok) throw new Error('Gagal memuat data akun.');

                const payload = await res.json();
                akunCache.set(id, payload);
                return payload;
            }

            function toggleDashboardFields() {
                const on = document.getElementById('edit_show_on_dashboard')?.checked;
                const wrap = document.getElementById('dashboardFieldsWrap');
                if (!wrap) return;

                wrap.classList.toggle('d-none', !on);
                wrap.querySelectorAll('input,select,textarea').forEach(el => {
                    el.disabled = !on;
                });
            }

            document.getElementById('edit_show_on_dashboard')
                ?.addEventListener('change', toggleDashboardFields);

            (function() {
                const sw = document.getElementById('create_show_on_dashboard');
                const wrap = document.getElementById('createDashboardFieldsWrap');
                if (!sw || !wrap) return;

                function toggle() {
                    const on = sw.checked;
                    wrap.classList.toggle('d-none', !on);
                    wrap.querySelectorAll('input,select,textarea').forEach(el => el.disabled = !on);
                }

                sw.addEventListener('change', toggle);
                toggle(); // initial
            })();


            /* =========================
             * Detail Modal (render cepat + loading)
             * ========================= */
            if (detailModalEl) {
                detailModalEl.addEventListener('show.bs.modal', async (e) => {
                    const id = e.relatedTarget?.getAttribute('data-id');
                    if (!id) return;

                    // set id ke tombol Edit
                    if (detailEditBtn) detailEditBtn.dataset.id = id;

                    // reset UI
                    detailLoading?.classList.remove('d-none');
                    detailContent?.classList.add('d-none');
                    detailError?.classList.add('d-none');
                    if (detailError) detailError.textContent = '';
                    if (detailTbody) detailTbody.innerHTML = '';

                    try {
                        const payload = await fetchAkun(id);
                        const a = payload.data;

                        const titleEl = document.getElementById('detailHeaderTitle');
                        if (titleEl) titleEl.textContent = `${a.kode_akun} — ${a.nama_akun}`;

                        const subEl = document.getElementById('detailHeaderSub');
                        if (subEl) subEl.textContent = `Parent: ${payload.parent ?? '-'}`;

                        const rows = [
                            ['ID', a.id],
                            ['Kode Akun', a.kode_akun],
                            ['Nama Akun', a.nama_akun],
                            ['Tipe Akun', badge(a.tipe_akun, {
                                asset: {
                                    cls: 'bg-primary',
                                    text: 'Asset'
                                },
                                liability: {
                                    cls: 'bg-warning text-dark',
                                    text: 'Liability'
                                },
                                revenue: {
                                    cls: 'bg-success',
                                    text: 'Revenue'
                                },
                                expense: {
                                    cls: 'bg-danger',
                                    text: 'Expense'
                                },
                                equity: {
                                    cls: 'bg-secondary',
                                    text: 'Equity'
                                },
                            })],
                            ['Kategori PSAK', a.kategori_psak || '-'],
                            ['Pembatasan', a.pembatasan || '-'],
                            ['Saldo Normal', badge(a.saldo_normal, {
                                debit: {
                                    cls: 'bg-primary',
                                    text: 'Debit'
                                },
                                kredit: {
                                    cls: 'bg-success',
                                    text: 'Kredit'
                                },
                            })],
                            ['Kas / Bank', yesNo(a.is_kas_bank)],
                            ['Cashflow Category', badge(a.cashflow_category, {
                                operasional: {
                                    cls: 'bg-primary',
                                    text: 'Operasional'
                                },
                                investasi: {
                                    cls: 'bg-success',
                                    text: 'Investasi'
                                },
                                pendanaan: {
                                    cls: 'bg-warning text-dark',
                                    text: 'Pendanaan'
                                },
                            })],
                            ['Icon', a.icon || '-'],

                            // Dashboard fields (tampilkan di detail)
                            ['Show on Dashboard', yesNo(a.show_on_dashboard)],
                            ['Dashboard Scope', a.dashboard_scope || '-'],
                            ['Dashboard Section', a.dashboard_section || '-'],
                            ['Dashboard Calc', a.dashboard_calc || '-'],
                            ['Dashboard Order', (a.dashboard_order ?? '-')],
                            ['Dashboard Title', a.dashboard_title || '-'],
                            ['Dashboard Link Route', a.dashboard_link_route || '-'],
                            ['Dashboard Link Param', a.dashboard_link_param || '-'],
                            ['Dashboard Format', a.dashboard_format || '-'],
                            ['Dashboard Masked', yesNo(a.dashboard_masked)],
                            ['Dashboard Icon', a.dashboard_icon || '-'],

                            ['Created At', a.created_at || '-'],
                            ['Updated At', a.updated_at || '-'],
                        ];

                        if (detailTbody) {
                            detailTbody.innerHTML = rows.map(([k, v]) => `
                        <tr>
                            <th style="width:240px" class="bg-light">${k}</th>
                            <td>${(v === null || v === undefined || v === '') ? '-' : v}</td>
                        </tr>
                    `).join('');
                        }

                        detailLoading?.classList.add('d-none');
                        detailContent?.classList.remove('d-none');

                    } catch (err) {
                        detailLoading?.classList.add('d-none');
                        if (detailError) {
                            detailError.textContent = err.message || 'Terjadi kesalahan.';
                            detailError.classList.remove('d-none');
                        }
                    }
                });
            }

            /* =========================
             * Klik Edit dari Detail -> buka Edit Modal
             * ========================= */
            if (detailEditBtn && editModalEl) {
                detailEditBtn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    if (!id) return;

                    bootstrap.Modal.getInstance(detailModalEl)?.hide();

                    editModalEl.dataset.triggerId = id;
                    new bootstrap.Modal(editModalEl).show();
                });
            }

            /* =========================
             * Edit Modal (non-blocking: modal tampil dulu, data menyusul)
             * ========================= */
            if (editModalEl) {
                editModalEl.addEventListener('show.bs.modal', (e) => {
                    const id =
                        e.relatedTarget?.getAttribute('data-id') ||
                        editModalEl.dataset.triggerId;

                    delete editModalEl.dataset.triggerId;

                    // reset UI edit
                    editError?.classList.add('d-none');
                    if (editError) editError.textContent = '';
                    editLoading?.classList.remove('d-none');
                    editFields?.classList.add('d-none');

                    fetchAkun(id)
                        .then(payload => {
                            const a = payload.data;

                            // set action form update kalau ada
                            const form = document.getElementById('akunEditForm');
                            if (form) {
                                const updateUrl = `{{ route('admin.akun_keuangan.update', ':id') }}`
                                    .replace(':id', id);
                                form.setAttribute('action', updateUrl);
                            }

                            // Fill core
                            setVal('edit_id', a.id);
                            setVal('edit_kode_akun', a.kode_akun);
                            setVal('edit_nama_akun', a.nama_akun);
                            setVal('edit_tipe_akun', a.tipe_akun);
                            setVal('edit_kategori_psak', a.kategori_psak);
                            setVal('edit_pembatasan', a.pembatasan);
                            setVal('edit_saldo_normal', a.saldo_normal);
                            setVal('edit_parent_id', a.parent_id);
                            setChecked('edit_is_kas_bank', a.is_kas_bank);
                            setVal('edit_cashflow_category', a.cashflow_category);
                            setVal('edit_icon', a.icon);

                            // Dashboard
                            setChecked('edit_show_on_dashboard', a.show_on_dashboard);
                            setVal('edit_dashboard_scope', a.dashboard_scope);
                            setVal('edit_dashboard_section', a.dashboard_section);
                            setVal('edit_dashboard_calc', a.dashboard_calc);
                            setVal('edit_dashboard_order', a.dashboard_order);
                            setVal('edit_dashboard_title', a.dashboard_title);
                            setVal('edit_dashboard_link_route', a.dashboard_link_route);
                            setVal('edit_dashboard_link_param', a.dashboard_link_param);
                            setVal('edit_dashboard_format', a.dashboard_format || 'currency');
                            setChecked('edit_dashboard_masked', a.dashboard_masked);
                            setVal('edit_dashboard_icon', a.dashboard_icon);

                            // apply hide/show wrapper
                            toggleDashboardFields();

                            editLoading?.classList.add('d-none');
                            editFields?.classList.remove('d-none');
                        })
                        .catch(err => {
                            editLoading?.classList.add('d-none');
                            if (editError) {
                                editError.textContent = err.message || 'Terjadi kesalahan.';
                                editError.classList.remove('d-none');
                            }
                        });
                });
            }

        });
    </script>

    {{-- SweetAlert dari Session --}}
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: @json(session('error'))
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                html: `{!! implode('<br>', $errors->all()) !!}`
            });
        </script>
    @endif
@endpush

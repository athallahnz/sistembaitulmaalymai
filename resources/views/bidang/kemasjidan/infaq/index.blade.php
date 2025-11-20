@extends('layouts.app')

@section('title', 'Dashboard Infaq Bulanan')

@section('content')
    <div class="container py-4">
        {{-- ===== Heading + Filter + Actions ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3">

            <div class="mb-2 mb-md-2">
                <h1 class="mb-1">Dashboard <strong>Infaq Bulanan</strong></h1>
                <div class="small text-muted">Bidang Kemasjidan • Rekap Infaq Warga</div>
            </div>

            <div class="d-flex flex-wrap align-items-end gap-2">

                {{-- Filter --}}
                <form action="{{ route('kemasjidan.infaq.index') }}" method="GET" class="d-flex gap-2 align-items-end">

                    <div class="input-group">
                        <span class="input-group-text">Cari</span>
                        <input type="text" name="q" class="form-control" placeholder="Nama / HP / RT / Alamat"
                            value="{{ $q }}">
                    </div>

                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                {{-- Tambah Modal --}}
                <button type="button" class="btn btn-primary shadow" data-bs-toggle="modal"
                    data-bs-target="#modalCreateInfaq">
                    <i class="bi bi-plus-circle"></i> Tambah Infaq
                </button>

            </div>
        </div>

        {{-- ===== Ringkasan / Summary Cards ===== --}}
        <div class="row g-3 mb-3">

            {{-- Jumlah Warga Terdata --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Jumlah Warga Terdata</div>
                            <div class="display-6 fw-bold mb-0">
                                {{ number_format($ringkas['jumlah_warga'] ?? 0) }}
                                <span class="small text-muted">warga</span>
                            </div>
                        </div>

                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-people-fill" style="font-size:2rem; color:#9a9a9a;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Infaq Terkumpul --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Total Infaq Terkumpul</div>
                            <div class="h1 fw-bold mb-0 text-success">
                                Rp{{ number_format($ringkas['total_infaq'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-cash-stack" style="font-size:2rem; color:#28a745;"></i>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Warga yang Pernah Bayar --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Warga yang Pernah Bayar</div>
                            <div class="display-6 fw-bold mb-0">
                                {{ $jumlahSudahBayar }}
                                <span class="small text-muted">warga</span>
                            </div>
                        </div>

                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                            style="width:70px;height:70px;">
                            <i class="bi bi-check-circle-fill" style="font-size:2rem; color:#0d6efd;"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== Modal Add Infaq (punyamu) ===== --}}
        @include('bidang.kemasjidan.infaq._modal_add_infaq')

        {{-- ===== Flash info PIN baru ===== --}}
        @if (session('generated_pin'))
            <div class="alert alert-info shadow-sm lift">
                PIN warga baru: <strong>{{ session('generated_pin') }}</strong><br>
                Berikan PIN ini ke warga untuk login di
                <a href="{{ route('warga.login.form') }}" class="text-brown">halaman tracking</a>.
            </div>
        @endif

        {{-- ===== Tabel Warga / Infaq (DataTables) ===== --}}
        <div class="p-3 shadow table-responsive rounded glass">
            <table id="infaq-table" class="table table-striped table-bordered rounded-2 align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>No. Handphone</th>
                        <th>Status Infaq</th>
                        <th>Total (Rp)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody> {{-- biarkan kosong, akan diisi oleh DataTables --}}
            </table>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = $('#infaq-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('kemasjidan.infaq.datatable') }}",
                    data: function(d) {
                        d.q = document.querySelector('input[name="q"]').value || '';
                    }
                },
                columns: [{
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'hp',
                        name: 'hp'
                    },
                    {
                        data: 'status_infaq',
                        name: 'status_infaq',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_infaq',
                        name: 'total_infaq',
                        className: 'text-end'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    },
                ],
                pageLength: 25,
                // dom default "frtip" → F = filter/search bawaan DataTables
                dom: 'frtip',
            });

            // Form filter ↑ pakai AJAX reload, bukan reload page
            const filterForm = document.querySelector('form[action="{{ route('kemasjidan.infaq.index') }}"]');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    table.ajax.reload();
                });
            }

            // Quick amount di modal (biar tetap jalan)
            document.querySelectorAll('.set-nominal-modal')?.forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = document.getElementById('nominal-modal');
                    if (target) target.value = btn.dataset.amount;
                });
            });
        });
    </script>
@endpush

@extends('layouts.app')

@section('content')
    {{-- <div class="container">
        <div class="row">
            <div class="col-6">
                <h1>Input Tagihan SPP/Bulan</h1>
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('tagihan-spp.store') }}">
                    @csrf
                    <div class="form-group mb-3">
                        <label><strong>Pilih Kelas</strong></label>
                        <div class="row">
                            @foreach ($classes as $class)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="edu_class_ids[]"
                                            value="{{ $class->id }}" id="class-{{ $class->id }}">
                                        <label class="form-check-label" for="class-{{ $class->id }}">
                                            {{ $class->name }} - {{$class->tahun_ajaran}}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label>Pilih Tahun</label>
                            <input type="number" name="tahun" min="2025" placeholder="Tahun" class="form-control"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label>Pilih Bulan</label>
                            <input type="number" name="bulan" min="1" max="12" placeholder="Bulan (1-12)"
                                class="form-control" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label>Jumlah Tagihan per siswa</label>
                            <input type="number" name="jumlah" required class="form-control"
                                placeholder="Masukkan Nominal..">
                        </div>
                        <div class="col-md-6">
                            <label>Tanggal Aktif</label>
                            <input type="date" name="tanggal_aktif" required class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Buat Tagihan</button>
                </form>
            </div>
            <div class="col-6">
                <h1>Export Tagihan Excel</h1>
                <form action="{{ route('tagihan-spp.export') }}" method="GET">
                    <div class="form-group">
                        <label><strong>Pilih Kelas</strong></label>
                        <div class="row">
                            @foreach ($classes as $class)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="edu_class_ids[]"
                                            value="{{ $class->id }}" id="export-class-{{ $class->id }}">
                                        <label class="form-check-label" for="export-class-{{ $class->id }}">
                                            {{ $class->name }} - {{$class->tahun_ajaran}}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label>Pilih Tahun</label>
                            <input type="number" name="tahun" placeholder="Tahun" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Pilih Bulan</label>
                            <input type="number" name="bulan" placeholder="Bulan (1-12)" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-success">Export Excel</button>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}
    <h1>Input Tagihan SPP/Bulan</h1>
    <div class="container my-3">
    <div class="row">
        <!-- Form Input Tagihan -->
        <div class="col-md-6">
            <div class="card shadow rounded-4 border-0">
                <div class="card-body">
                    <h4 class="mb-4">🧾 Buat Tagihan SPP</h4>

                    @if (session('success'))
                        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('tagihan-spp.store') }}">
                        @csrf

                        <!-- Pilih Kelas -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Pilih Kelas</strong></label>
                            <div class="row">
                                @foreach ($classes as $class)
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input create-check" type="checkbox" name="edu_class_ids[]"
                                        value="{{ $class->id }}" id="class-{{ $class->id }}">
                                        <label class="form-check-label" for="class-{{ $class->id }}">
                                            {{ $class->name }} - {{ $class->tahun_ajaran }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="form-check my-2">
                                <input type="checkbox" class="form-check-input" id="selectAllCreate">
                                <label class="form-check-label" for="selectAllCreate">Pilih Semua</label>
                            </div>
                        </div>

                        <!-- Tahun, Bulan -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Tahun</label>
                                <input type="number" name="tahun" min="2025" placeholder="Tahun" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pilih Bulan</label>
                                <input type="number" name="bulan" min="1" max="12" placeholder="Bulan (1-12)"
                                    class="form-control" required>
                            </div>
                        </div>

                        <!-- Nominal dan Tanggal -->
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Tagihan / Siswa</label>
                                <input type="number" name="jumlah" required class="form-control"
                                    placeholder="Masukkan Nominal...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Aktif</label>
                                <input type="date" name="tanggal_aktif" required class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-4 w-100">🚀 Buat Tagihan</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form Export -->
        <div class="col-md-6 mt-4 mt-md-0">
            <div class="card shadow rounded-4 border-0">
                <div class="card-body">
                    <h4 class="mb-4">📤 Export Tagihan Excel</h4>

                    <form action="{{ route('tagihan-spp.export') }}" method="GET">
                        <!-- Pilih Kelas -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Pilih Kelas</strong></label>
                            <div class="row">
                                @foreach ($classes as $class)
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input export-check" type="checkbox" name="edu_class_ids[]"
                                        value="{{ $class->id }}" id="export-class-{{ $class->id }}">
                                        <label class="form-check-label" for="export-class-{{ $class->id }}">
                                            {{ $class->name }} - {{ $class->tahun_ajaran }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="form-check my-2">
                                <input type="checkbox" class="form-check-input" id="selectAllExport">
                                <label class="form-check-label" for="selectAllExport">Pilih Semua</label>
                            </div>
                        </div>

                        <!-- Tahun, Bulan -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Tahun</label>
                                <input type="number" name="tahun" placeholder="Tahun" min="2025" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pilih Bulan</label>
                                <input type="number" name="bulan" placeholder="Bulan (1-12)" class="form-control" required>
                            </div>
                        </div>

                        <button class="btn btn-success mt-4 w-100">📥 Export Excel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    // Checkbox "Pilih Semua" untuk form create
    document.getElementById('selectAllCreate').addEventListener('change', function () {
        document.querySelectorAll('.create-check').forEach(cb => cb.checked = this.checked);
    });

    // Checkbox "Pilih Semua" untuk form export
    document.getElementById('selectAllExport').addEventListener('change', function () {
        document.querySelectorAll('.export-check').forEach(cb => cb.checked = this.checked);
    });
</script>
@endpush

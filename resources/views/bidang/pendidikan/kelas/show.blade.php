@extends('layouts.app')

@section('content')
    <div class="container">

        {{-- HEADER (short & consistent seperti index) --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('edu_classes.index') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('edu_classes.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a>Detail Kelas</a></li>
                    </ol>
                </nav>

                <h1 class="mb-2">Detail Kelas</h1>
                <div class="text-muted small">
                    {{ $eduClass->name }} • Tahun Ajaran {{ $eduClass->tahun_ajaran }}
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('edu_classes.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        {{-- CONTENT --}}
        <div class="row g-3">
            {{-- POS BIAYA PENDIDIKAN --}}
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">Pos Biaya Pendidikan</div>
                            <span class="badge text-bg-secondary">
                                {{ $eduClass->akunKeuangans->count() }} akun
                            </span>
                        </div>
                        <div class="text-muted small mb-3">
                            Mapping akun biaya pendidikan untuk kelas ini.
                        </div>

                        @if ($eduClass->akunKeuangans->count())
                            <div class="border rounded-3 p-2" style="max-height: 360px; overflow:auto;">
                                <ul class="list-group list-group-flush">
                                    @foreach ($eduClass->akunKeuangans->sortBy('kode_akun') as $a)
                                        <li class="list-group-item d-flex align-items-start gap-2">
                                            <div class="text-muted small" style="min-width: 70px;">
                                                {{ $a->kode_akun ?? '-' }}
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">{{ $a->nama_akun }}</div>
                                                @if (!empty($a->parent_id))
                                                    <div class="text-muted small">Parent ID: {{ $a->parent_id }}</div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">
                                <div class="fw-semibold">Belum ada mapping</div>
                                <div class="text-muted small">
                                    Silakan mapping akun biaya dari menu Edit (di halaman Daftar Kelas).
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- DAFTAR MURID --}}
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">Daftar Murid</div>
                            <span class="badge text-bg-primary">
                                {{ $eduClass->students->count() }} murid
                            </span>
                        </div>
                        <div class="text-muted small mb-3">
                            Ringkasan murid yang terdaftar pada kelas ini.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th style="width: 220px;">RFID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($eduClass->students->sortBy('name') as $s)
                                        <tr>
                                            <td class="fw-semibold">{{ $s->name }}</td>
                                            <td class="text-muted">{{ $s->rfid_uid ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">
                                                Belum ada murid di kelas ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

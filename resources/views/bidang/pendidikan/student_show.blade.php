@extends('layouts.app')

@section('content')
    <div class="container">
        <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a>Detail</a></li>
            </ol>
        <div class="row">
            <div class="col-md-6">
                <h2 class="mb-4"><strong>{{ $student->no_induk }} - {{ $student->name }}</strong></h2>
            </div>
            <div class="col-md-6">
                <h2 class="mb-4">Kelas: <strong>{{ $student->eduClass->name }} -
                        {{ $student->eduClass->tahun_ajaran }}</strong></h2>
            </div>
        </div>

        <div class="accordion" id="accordionStudent">
            {{-- Accordion Item 1: Data Diri Murid --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingMurid">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseMurid" aria-expanded="true" aria-controls="collapseMurid">
                        <strong>Data Diri Murid</strong>
                    </button>
                </h2>
                <div id="collapseMurid" class="accordion-collapse collapse show" aria-labelledby="headingMurid"
                    data-bs-parent="#accordionStudent">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><strong>Jenis Kelamin:</strong>
                                        {{ $student->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</li>
                                    <li class="list-group-item"><strong>Tempat, Tanggal Lahir:</strong>
                                        {{ $student->tempat_lahir }},
                                        {{ \Carbon\Carbon::parse($student->ttl)->format('d F Y') }}
                                    </li>
                                    <li class="list-group-item"><strong>Usia:</strong> {{ $student->usia }}</li>
                                    <li class="list-group-item"><strong>NIK:</strong> {{ $student->nik }}</li>
                                    <li class="list-group-item"><strong>No. Akta:</strong> {{ $student->no_akte }}</li>
                                    <li class="list-group-item"><strong>No. KK:</strong> {{ $student->no_kk }}</li>
                                    <li class="list-group-item"><strong>Alamat KK:</strong> {{ $student->alamat_kk }}</li>
                                    <li class="list-group-item"><strong>Alamat Tinggal:</strong>
                                        {{ $student->alamat_tinggal }}
                                    </li>
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul>
                                    <li class="list-group-item mb-3">
                                        <strong>Pas Foto:</strong><br>
                                        @if ($student->pas_photo)
                                            <img class="mt-3" src="{{ asset('storage/' . $student->pas_photo) }}" alt="Pas Photo"
                                                class="img-fluid rounded" style="max-width: 150px;">
                                        @else
                                            <em>Tidak ada</em>
                                        @endif
                                    </li>
                                    <li class="list-group-item mb-3">
                                        <strong>Dokumen Akta:</strong><br>
                                        @if ($student->akte)
                                            <a class="mt-3" href="{{ asset('storage/' . $student->akte) }}" target="_blank">Lihat
                                                Akta</a>
                                        @else
                                            <em>Tidak ada</em>
                                        @endif
                                    </li>
                                    <li class="list-group-item mb-3">
                                        <strong>Dokumen KK:</strong><br>
                                        @if ($student->kk)
                                            <a class="mt-3" href="{{ asset('storage/' . $student->kk) }}" target="_blank">Lihat KK</a>
                                        @else
                                            <em>Tidak ada</em>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Accordion Item 2: Data Wali Murid --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingWali">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseWali" aria-expanded="false" aria-controls="collapseWali">
                            <strong>Data Wali Murid</strong>
                        </button>
                    </h2>
                    <div id="collapseWali" class="accordion-collapse collapse" aria-labelledby="headingWali"
                        data-bs-parent="#accordionStudent">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Nama:</strong>
                                            {{ $student->waliMurid->nama ?? '-' }}
                                        </li>
                                        <li class="list-group-item"><strong>Jenis Kelamin:</strong>
                                            @if ($student->waliMurid)
                                                {{ $student->waliMurid->jenis_kelamin == 'L' ? 'Laki-laki' : ($student->waliMurid->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}
                                            @else
                                                -
                                            @endif
                                        </li>
                                        <li class="list-group-item"><strong>Hubungan:</strong>
                                            {{ $student->waliMurid->hubungan ?? '-' }}</li>
                                        <li class="list-group-item"><strong>NIK:</strong>
                                            {{ $student->waliMurid->nik ?? '-' }}
                                        </li>
                                        <li class="list-group-item"><strong>No. Handphone:</strong>
                                            {{ $student->waliMurid->no_hp ?? '-' }}</li>
                                        <li class="list-group-item"><strong>Email:</strong>
                                            {{ $student->waliMurid->email ?? '-' }}
                                        </li>
                                        <li class="list-group-item"><strong>Pendidikan Terakhir:</strong>
                                            {{ $student->waliMurid->pendidikan_terakhir ?? '-' }}</li>
                                        <li class="list-group-item"><strong>Pekerjaan:</strong>
                                            {{ $student->waliMurid->pekerjaan ?? '-' }}</li>
                                        <li class="list-group-item"><strong>Alamat:</strong>
                                            {{ $student->waliMurid->alamat ?? '-' }}
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-6">
                                    <ul>
                                        <li class="list-group-item mb-3">
                                            <strong>Foto KTP:</strong><br>
                                            @if ($student->waliMurid)
                                                <img class="mt-3" src="{{ asset('storage/' . $student->waliMurid->foto_ktp) }}"
                                                    alt="Foto KTP" class="img-fluid rounded" style="max-width: 150px;">
                                            @else
                                                <em>Tidak ada</em>
                                            @endif
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="my-3 mx-3">
                    <a href="{{ route('students.index') }}" class="btn btn-secondary">Kembali</a>
                    <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary">Edit Data</a>
                </div>
            </div>
        @endsection
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @endpush

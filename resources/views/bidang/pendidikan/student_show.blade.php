@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h2 class="mb-4"><strong>{{ $student->no_induk }} - {{ $student->name }}</strong></h2>
            </div>
            <div class="col-md-6">
                <h2 class="mb-4">Kelas: <strong>{{ $student->eduClass->name }} - {{ $student->eduClass->tahun_ajaran }}</strong></h2>
            </div>
        </div>

        <div class="row">
            {{-- Kolom 1: Data Diri Murid --}}
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Data Diri Murid</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Jenis Kelamin:</strong>
                                {{ $student->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</li>
                            <li class="list-group-item"><strong>Tempat, Tanggal Lahir:</strong>
                                {{ $student->tempat_lahir }}, {{ \Carbon\Carbon::parse($student->ttl)->format('d F Y') }}
                            </li>
                            <li class="list-group-item"><strong>Usia:</strong> {{ $student->usia }}</li>
                            <li class="list-group-item"><strong>NIK:</strong> {{ $student->nik }}</li>
                            <li class="list-group-item"><strong>No. Akta:</strong> {{ $student->no_akte }}</li>
                            <li class="list-group-item"><strong>No. KK:</strong> {{ $student->no_kk }}</li>
                            <li class="list-group-item"><strong>Alamat KK:</strong> {{ $student->alamat_kk }}</li>
                            <li class="list-group-item"><strong>Alamat Tinggal:</strong> {{ $student->alamat_tinggal }}
                            </li>
                            <li class="list-group-item">
                                <strong>Pas Foto:</strong><br>
                                @if ($student->pas_photo)
                                    <img src="{{ asset('storage/' . $student->pas_photo) }}" alt="Pas Photo"
                                        class="img-fluid rounded" style="max-width: 150px;">
                                @else
                                    <em>Tidak ada</em>
                                @endif
                            </li>
                            <li class="list-group-item">
                                <strong>Dokumen Akta:</strong><br>
                                @if ($student->akte)
                                    <a href="{{ asset('storage/' . $student->akte) }}" target="_blank">Lihat Akta</a>
                                @else
                                    <em>Tidak ada</em>
                                @endif
                            </li>
                            <li class="list-group-item">
                                <strong>Dokumen KK:</strong><br>
                                @if ($student->kk)
                                    <a href="{{ asset('storage/' . $student->kk) }}" target="_blank">Lihat KK</a>
                                @else
                                    <em>Tidak ada</em>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Kolom 2: Data Wali Murid --}}
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Data Wali Murid</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Nama:</strong> {{ $student->waliMurid->nama ?? '-' }}</li>
                            <li class="list-group-item"><strong>Jenis Kelamin:</strong>
                                {{ $student->waliMurid->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' ?? '-' }}</li>
                            <li class="list-group-item"><strong>Hubungan:</strong>
                                {{ $student->waliMurid->hubungan ?? '-' }}</li>
                            <li class="list-group-item"><strong>NIK:</strong> {{ $student->waliMurid->nik ?? '-' }}</li>
                            <li class="list-group-item"><strong>No. Handphone:</strong>
                                {{ $student->waliMurid->no_hp ?? '-' }}</li>
                            <li class="list-group-item"><strong>Email:</strong> {{ $student->waliMurid->email ?? '-' }}
                            </li>
                            <li class="list-group-item"><strong>Pendidikan Terakhir:</strong>
                                {{ $student->waliMurid->pendidikan_terakhir ?? '-' }}</li>
                            <li class="list-group-item"><strong>Pekerjaan:</strong>
                                {{ $student->waliMurid->pekerjaan ?? '-' }}</li>
                            <li class="list-group-item">
                                <strong>Foto KTP:</strong><br>
                                @if ($student->waliMurid->foto_ktp)
                                    <img src="{{ asset('storage/' . $student->waliMurid->foto_ktp) }}" alt="Foto KTP"
                                        class="img-fluid rounded" style="max-width: 150px;">
                                @else
                                    <em>Tidak ada</em>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('students.index') }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary">Edit Data</a>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Detail Murid: {{ $student->name }}</h2>

    <div class="row">
        {{-- Kolom 1: Data Diri Murid --}}
        <div class="col-md-4">
            <h4>Data Diri Murid</h4>

            <p><strong>Kelas:</strong> {{ $student->eduClass->name }} - {{ $student->eduClass->tahun_ajaran }}</p>
            <p><strong>Nama Lengkap:</strong> {{ $student->name }}</p>
            <p><strong>Jenis Kelamin:</strong> {{ $student->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
            <p><strong>Tempat, Tanggal Lahir:</strong> {{ $student->tempat_lahir }}, {{ \Carbon\Carbon::parse($student->ttl)->format('d/m/Y') }}</p>
            <p><strong>Usia:</strong> {{ $student->usia }} tahun</p>
            <p><strong>NIK:</strong> {{ $student->nik }}</p>
            <p><strong>No. Akta:</strong> {{ $student->no_akte }}</p>
            <p><strong>No. KK:</strong> {{ $student->no_kk }}</p>
            <p><strong>Alamat KK:</strong> {{ $student->alamat_kk }}</p>
            <p><strong>Alamat Tinggal:</strong> {{ $student->alamat_tinggal }}</p>

            <p><strong>Pas Photo:</strong><br>
                @if ($student->pas_photo)
                    <img src="{{ asset('storage/' . $student->pas_photo) }}" alt="Pas Photo" class="img-thumbnail" width="150">
                @else
                    <em>Tidak ada</em>
                @endif
            </p>

            <p><strong>Upload Akta:</strong><br>
                @if ($student->akte)
                    <a href="{{ asset('storage/' . $student->akte) }}" target="_blank">Lihat Akta</a>
                @else
                    <em>Tidak ada</em>
                @endif
            </p>

            <p><strong>Upload KK:</strong><br>
                @if ($student->kk)
                    <a href="{{ asset('storage/' . $student->kk) }}" target="_blank">Lihat KK</a>
                @else
                    <em>Tidak ada</em>
                @endif
            </p>
        </div>

        {{-- Kolom 2: Data Wali Murid --}}
        <div class="col-md-4">
            <h4>Data Wali Murid</h4>

            <p><strong>Nama:</strong> {{ $students->waliMurid->nama ?? '-' }}</p>
            <p><strong>Jenis Kelamin:</strong> {{ $students->waliMurid->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' ?? '-' }}</p>
            <p><strong>Hubungan:</strong> {{ $students->waliMurid->hubungan ?? '-'}}</p>
            <p><strong>NIK:</strong> {{ $students->waliMurid->nik ?? '-'}}</p>
            <p><strong>No. Handphone:</strong> {{ $students->waliMurid->no_hp ?? '-'}}</p>
            <p><strong>Email:</strong> {{ $students->waliMurid->email ?? '-'}}</p>
            <p><strong>Pendidikan Terakhir:</strong> {{ $students->waliMurid->pendidikan_terakhir ?? '-'}}</p>
            <p><strong>Pekerjaan:</strong> {{ $students->waliMurid->pekerjaan ?? '-'}}</p>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('students.index') }}" class="btn btn-secondary">Kembali</a>
        <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary">Edit Data</a>
    </div>
</div>
@endsection

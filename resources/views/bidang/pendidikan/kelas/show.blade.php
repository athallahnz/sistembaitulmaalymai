@extends('layouts.app')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('edu_classes.index') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
            </ol>
        </nav>

        <h2 class="mb-4">Detail Kelas: {{ $eduClass->name }} {{ $eduClass->tahun_ajaran }}</h2>

        <div class="mb-4">
            <h3><strong>Jumlah Siswa:</strong> {{ $eduClass->students->count() }}</h3>
        </div>

        @if ($eduClass->students->isEmpty())
            <p class="text-muted">Belum ada siswa di kelas ini.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Nama</th>
                            <th>NISN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eduClass->students as $index => $siswa)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $siswa->name }}</td>
                                <td>{{ $siswa->nisn ?? '-'}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <a href="{{ route('edu_classes.index') }}" class="btn btn-secondary mt-3">Kembali</a>
    </div>
@endsection

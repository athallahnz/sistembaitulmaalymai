@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Detail Kelas: {{ $kelas->name }}</h2>
        <p><strong>Tahun Ajaran:</strong> {{ $kelas->tahun_ajaran }}</p>
        <p><strong>Jumlah Siswa:</strong> {{ $kelas->students->count() }}</p>

        <h5>Daftar Siswa</h5>
        @if ($kelas->students->isEmpty())
            <p>Belum ada siswa di kelas ini.</p>
        @else
            <ul class="list-group">
                @foreach ($kelas->students as $siswa)
                    <li class="list-group-item">{{ $siswa->nama }} - NISN: {{ $siswa->nisn }}</li>
                @endforeach
            </ul>
        @endif

        <a href="{{ route('kelas.index') }}" class="btn btn-secondary mt-3">Kembali</a>
    </div>
@endsection

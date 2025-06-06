@extends('layouts.app')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page"><a>Home</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a>Dashboard</a></li>
            </ol>
        </nav>
        <h1>Daftar Kelas</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nama Kelas</th>
                    <th>Tahun Ajaran</th>
                    <th>Jumlah Siswa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kelasList as $kelas)
                    <tr>
                        <td>{{ $kelas->name }}</td>
                        <td>{{ $kelas->tahun_ajaran }}</td>
                        <td>{{ $kelas->students_count }}</td>
                        <td>
                            <a href="{{ route('edu_classes.show', $kelas->id) }}" class="btn btn-info btn-sm">Lihat</a>
                            <a href="{{ route('edu_classes.edit', $kelas->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('edu_classes.destroy', $kelas->id) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Yakin hapus kelas ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Daftar Wali Murid</h4>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Hubungan</th>
                <th>No HP</th>
                <th>Jenis Kelamin</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($waliMurids as $wali)
                <tr>
                    <td>{{ $wali->nama }}</td>
                    <td>{{ $wali->hubungan }}</td>
                    <td>{{ $wali->no_hp }}</td>
                    <td>{{ $wali->jenis_kelamin }}</td>
                    <td>
                        <a href="{{ route('wali-murids.show', $wali->id) }}" class="btn btn-info btn-sm">Detail</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

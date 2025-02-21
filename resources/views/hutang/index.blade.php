@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Daftar Hutang <strong>Bidang {{ auth()->user()->bidang_name }}</strong></h1>
    <a href="{{ route('hutangs.create') }}" class="btn btn-primary mb-3"><i class="bi bi-plus-circle"></i>  Catat Hutang!</a>
    <table class="table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Penghutang</th>
                <th>Jumlah</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hutangs as $hutang)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $hutang->user->name }}</td>
                <td>Rp {{ number_format($hutang->jumlah, 2) }}</td>
                <td>{{ $hutang->tanggal_jatuh_tempo }}</td>
                <td>{{ ucfirst($hutang->status) }}</td>
                <td>
                    <a href="{{ route('hutangs.edit', $hutang->id) }}" class="btn btn-warning">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <form action="{{ route('hutangs.destroy', $hutang->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Hapus piutang ini?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

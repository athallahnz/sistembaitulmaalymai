@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Daftar Piutang <strong>Bidang {{ auth()->user()->bidang_name }}</strong></h1>
    <a href="{{ route('piutangs.create') }}" class="btn btn-primary mb-3"><i class="bi bi-plus-circle"></i>  Catat Piutang!</a>
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
            @foreach($piutangs as $piutang)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $piutang->user->name }}</td>
                <td>Rp {{ number_format($piutang->jumlah, 2) }}</td>
                <td>{{ $piutang->tanggal_jatuh_tempo }}</td>
                <td>{{ ucfirst($piutang->status) }}</td>
                <td>
                    <a href="{{ route('piutangs.edit', $piutang->id) }}" class="btn btn-warning">Edit</a>
                    <form action="{{ route('piutangs.destroy', $piutang->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Hapus piutang ini?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

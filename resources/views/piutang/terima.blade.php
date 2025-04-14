@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Daftar Hutang <strong>yang Harus Dilunasi</strong></h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($piutangs as $piutang)
                <tr>
                    <td>{{ $piutang->deskripsi }}</td>
                    <td>Rp{{ number_format($piutang->jumlah, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge bg-danger">Belum Lunas</span>
                    </td>
                    <td>
                        <a href="{{ route('piutangs.showPayForm', $piutang->id) }}" class="btn btn-sm btn-success">Lunasi</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada piutang aktif yang perlu dilunasi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

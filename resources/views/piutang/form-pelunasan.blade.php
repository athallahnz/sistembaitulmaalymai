@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Form Pelunasan Piutang</h1>

    {{-- Tampilkan error jika ada --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Flash message --}}
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @elseif (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('piutangs.storePayment', $piutang->id) }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="mb-2">Deskripsi Piutang</label>
            <input type="text" class="form-control" value="{{ $piutang->deskripsi }}" disabled>
        </div>

        <div class="mb-3">
            <label class="mb-2">Sisa Piutang</label>
            <input type="text" class="form-control" value="Rp{{ number_format($piutang->jumlah, 0, ',', '.') }}" disabled>
        </div>

        <div class="mb-3">
            <label class="mb-2">Jumlah Pelunasan</label>
            <input type="number" name="jumlah_bayar" class="form-control" max="{{ $piutang->jumlah }}" required>
        </div>

        <div class="mb-3">
            <label class="mb-2">Pilih Akun Keuangan</label>
            <select name="akun_keuangan_id" class="form-select" required>
                <option value="">-- Pilih Akun --</option>
                @foreach ($akunKeuanganOptions as $label => $id)
                    <option value="{{ $id }}">{{ $label }} (ID: {{ $id }})</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Lunasi Sekarang</button>
        <a href="{{ route('piutangs.penerima') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection

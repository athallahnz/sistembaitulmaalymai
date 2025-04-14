@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Form Pelunasan Piutang</h1>

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
            <input type="number" name="jumlah" class="form-control" max="{{ $piutang->jumlah }}" required>
        </div>

        <div class="mb-3">
            <label class="mb-2">Deskripsi Tambahan (opsional)</label>
            <textarea name="deskripsi" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Lunasi Sekarang</button>
        <a href="{{ route('piutangs.penerima') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection

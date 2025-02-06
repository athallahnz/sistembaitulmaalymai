@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Transaksi</h1>
    <form action="{{ route('transaksi.update', $transaksi->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="bidang_name" class="form-label">Bidang</label>
            <input type="text" name="bidang_name" class="form-control" value="{{ $transaksi->bidang_name }}" readonly>
        </div>

        <div class="mb-3">
            <label for="kode_transaksi" class="form-label">Kode Transaksi</label>
            <input type="text" name="kode_transaksi" class="form-control" value="{{ $transaksi->kode_transaksi }}" readonly>
        </div>

        <div class="mb-3">
            <label for="tanggal_transaksi" class="form-label">Tanggal Transaksi</label>
            <input type="date" name="tanggal_transaksi" class="form-control" value="{{ $transaksi->tanggal_transaksi }}" required>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Tipe Transaksi</label>
            <select name="type" class="form-control" required>
                <option value="penerimaan" {{ $transaksi->type == 'penerimaan' ? 'selected' : '' }}>Penerimaan</option>
                <option value="pengeluaran" {{ $transaksi->type == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="akun_keuangan_id" class="form-label">Akun Keuangan</label>
            <select name="akun_keuangan_id" class="form-control" required>
                @foreach ($akunTanpaParent as $akun)
                    <option value="{{ $akun->id }}" {{ $transaksi->akun_keuangan_id == $akun->id ? 'selected' : '' }}>
                        {{ $akun->nama_akun }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <input type="text" name="deskripsi" class="form-control" value="{{ $transaksi->deskripsi }}" required>
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Jumlah</label>
            <input type="number" name="amount" class="form-control" value="{{ $transaksi->amount }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection

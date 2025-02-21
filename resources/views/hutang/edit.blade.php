@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Edit Hutang</h2>

        <form action="{{ route('hutangs.update', $hutang->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Bidang Name (Teks readonly) -->
            <div class="mb-3 d-none">
                <label class="mb-2">Bidang</label>
                <input type="text" name="bidang_name" class="form-control" value="{{ auth()->user()->bidang_name }}" readonly>
            </div>

            <!-- Pilihan User yang memiliki role "Bendahara" & "Bidang" -->
            <div class="mb-3">
                <label for="user_id" class="form-label mb-2">Hutang ke</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">Pilih Tujuan</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ $hutang->user_id == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} - {{ $user->role }} {{ $user->bidang_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Akun Keuangan -->
            <div class="mb-3">
                <label class="form-label mb-2">Asal Akun</label>
                <select class="form-control" name="akun_keuangan_id" id="akun_keuangan" required>
                    <option value="{{ $akunHutang->id }}" data-saldo-normal="{{ $akunHutang->saldo_normal }}" selected>
                        {{ $akunHutang->nama_akun }}
                    </option>
                </select>
            </div>

            <!-- Sub Akun Hutang -->
            <div class="mb-3" id="parent-akun-container">
                <label class="mb-2">Sub Akun Hutang</label>
                <select class="form-control" name="parent_akun_id" id="parent_akun_id">
                    <option value="">Pilih Sub Akun Hutang</option>
                    @foreach ($parentAkunHutang as $akun)
                        <option value="{{ $akun->id }}" {{ $hutang->parent_akun_id == $akun->id ? 'selected' : '' }}>
                            {{ $akun->nama_akun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Jumlah Hutang -->
            <div class="form-group mb-3">
                <label for="jumlah" class="form-label mb-2">Jumlah</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control @error('jumlah') is-invalid @enderror" value="{{ old('jumlah', $hutang->jumlah) }}">
                @error('jumlah')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tanggal Jatuh Tempo -->
            <div class="form-group mb-3">
                <label class="form-label mb-2" for="tanggal_jatuh_tempo">Tanggal Jatuh Tempo</label>
                <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo" class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror" value="{{ old('tanggal_jatuh_tempo', $hutang->tanggal_jatuh_tempo) }}">
                @error('tanggal_jatuh_tempo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Deskripsi -->
            <div class="form-group mb-3">
                <label for="deskripsi" class="form-label mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi', $hutang->deskripsi) }}</textarea>
                @error('deskripsi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status Hutang -->
            <div class="form-group mb-3">
                <label for="status" class="form-label mb-2">Status</label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                    <option value="belum_lunas" {{ $hutang->status == 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="lunas" {{ $hutang->status == 'lunas' ? 'selected' : '' }}>Lunas</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('hutangs.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection

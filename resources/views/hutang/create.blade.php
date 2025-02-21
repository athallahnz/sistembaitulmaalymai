@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Tambah Hutang</h2>

        <form action="{{ route('hutangs.store') }}" method="POST">
            @csrf

            <!-- Bidang Name (Teks readonly) -->
            <div class="mb-3 d-none">
                <label class="mb-2">Bidang</label>
                <input type="text" name="bidang_name" class="form-control" value="{{ auth()->user()->bidang_name }}"
                    readonly>
            </div>

            <!-- Field untuk memilih user yang memiliki role "Bendahara" & "Bidang" serta bidang yang sama -->
            <div class="mb-3">
                <label for="user_id" class="form-label mb-2">Hutang ke</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">Pilih Tujuan</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->role }}
                            {{ $user->bidang_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Pilihan Akun Keuangan (Hanya Akun Hutang 201) -->
            <div class="mb-3">
                <label class="form-label mb-2" id="akun-label">Asal Akun</label>
                <select class="form-control" name="akun_keuangan_id" id="akun_keuangan" required>
                    <option value="{{ $akunHutang->id }}" data-saldo-normal="{{ $akunHutang->saldo_normal }}" selected>
                        {{ $akunHutang->nama_akun }}
                    </option>
                </select>
            </div>

            <!-- Pilihan Akun Parent (Hanya yang memiliki parent_id = 201) -->
            <div class="mb-3" id="parent-akun-container">
                <label class="mb-2">Sub Akun Hutang</label>
                <select class="form-control" name="parent_akun_id" id="parent_akun_id">
                    <option value="">Pilih Sub Akun Hutang</option>
                    @foreach ($parentAkunHutang as $akun)
                        <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Field lainnya (Jumlah, Tanggal, Deskripsi, Status, dll) -->
            <div class="form-group mb-3">
                <label for="jumlah" class="form-label mb-2">Jumlah</label>
                <input type="number" name="jumlah" id="jumlah"
                    class="form-control @error('jumlah') is-invalid @enderror" value="{{ old('jumlah') }}">
                @error('jumlah')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label class="form-label mb-2" for="tanggal_jatuh_tempo">Tanggal Jatuh Tempo</label>
                <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo"
                    class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror"
                    value="{{ old('tanggal_jatuh_tempo') }}">
                @error('tanggal_jatuh_tempo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="deskripsi" class="form-label mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-3">
                <label for="status" class="form-label mb-2">Status</label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                    <option value="belum_lunas" {{ old('status') == 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="lunas" {{ old('status') == 'lunas' ? 'selected' : '' }}>Lunas</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>

    </div>
    <script>
        $(document).ready(function() {
            var parentAkunContainer = $('#parent-akun-container');

            // Secara default tampilkan parent akun jika ada
            if ($('#akun_keuangan').val() == '201') {
                parentAkunContainer.show();
            } else {
                parentAkunContainer.hide();
            }

            // Jquery untuk menampilkan Akun Parent berdasarkan akun_keuangan_id
            $('#akun_keuangan').on('change', function() {
                var akunId = $(this).val();

                // Jika akun yang dipilih adalah 201 (Hutang)
                if (akunId == '201') {
                    parentAkunContainer.show();

                    // Kosongkan dropdown Parent Akun dan tambahkan opsi baru
                    $('#parent_akun_id').empty();
                    $('#parent_akun_id').append('<option value="">Pilih Akun Parent</option>');

                    @foreach ($parentAkunHutang as $akun)
                        $('#parent_akun_id').append(
                            '<option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>');
                    @endforeach
                } else {
                    parentAkunContainer.hide();
                }
            });
        });
    </script>
@endsection

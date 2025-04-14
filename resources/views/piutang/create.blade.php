@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Tambah Piutang</h2>

        <form action="{{ route('piutangs.store') }}" method="POST">
            @csrf

            <!-- Bidang Name (Teks readonly) -->
            <div class="mb-3 d-none">
                <label class="mb-2">Bidang</label>
                <input type="hidden" name="bidang_name" value="{{ auth()->user()->bidang_name }}">
            </div>

            <!-- Field untuk memilih user yang memiliki role "Bendahara" & "Bidang" serta bidang yang sama -->
            <div class="mb-3">
                <label for="user_id" class="form-label mb-2">Nama Pengutang</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">Pilih Tujuan</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->name }} - {{ $user->roles->pluck('name')->implode(', ') }}
                            {{ $user->bidang->name ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="akun_keuangan_id" class="form-label">Pilih Sumber Keuangan</label>
                <select name="akun_keuangan_id" id="akun_keuangan_id" class="form-control">
                    <option value="">-- Pilih Akun --</option>
                    @foreach ($akunKeuanganOptions as $key => $akun)
                        <option value="{{ $akun }}">{{ $key }}</option>
                    @endforeach
                </select>
                <small id="saldo-info" class="form-text text-muted mt-1"></small>
            </div>

            <!-- Pilihan Akun Keuangan (Hanya Akun Piutang 103) -->
            <div class="mb-3 d-none">
                <label class="form-label mb-2" id="akun-label">Asal Akun</label>
                <select class="form-control" id="akun_keuangan" required>
                    <option value="{{ $akunPiutang->id }}" data-saldo-normal="{{ $akunPiutang->saldo_normal }}" selected>
                        {{ $akunPiutang->nama_akun }}
                    </option>
                </select>
            </div>

            <!-- Pilihan Akun Parent (Hanya yang memiliki parent_id = 103) -->
            <div class="mb-3" id="parent-akun-container">
                <label class="mb-2">Tujuan Akun Piutang</label>
                <select class="form-control" name="parent_akun_id" id="parent_akun_id">
                    <option value="">Pilih Tujuan Akun Piutang</option>
                    @foreach ($parentAkunPiutang as $akun)
                        <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Field lainnya (Jumlah, Tanggal, Deskripsi, Status, dll) -->
            <div class="form-group mb-3">
                <label for="jumlah" class="form-label mb-2">Jumlah</label>
                <input type="text" id="formattedAmount" class="form-control" oninput="formatInput(this)">
                <input type="number" name="jumlah" id="jumlah"
                class="form-control d-none @error('jumlah') is-invalid @enderror" value="{{ old('jumlah') }}">
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
            <a href="{{ route('piutangs.index') }}" class="btn btn-secondary">Cancel</a>
        </form>

    </div>
    <script>
        $(document).ready(function() {
            var parentAkunContainer = $('#parent-akun-container');

            // Secara default tampilkan parent akun jika ada
            if ($('#akun_keuangan').val() == '103') {
                parentAkunContainer.show();
            } else {
                parentAkunContainer.hide();
            }

            // Jquery untuk menampilkan Akun Parent berdasarkan akun_keuangan_id
            $('#akun_keuangan').on('change', function() {
                var akunId = $(this).val();

                // Jika akun yang dipilih adalah 103 (Piutang)
                if (akunId == '103') {
                    parentAkunContainer.show();

                    // Kosongkan dropdown Parent Akun dan tambahkan opsi baru
                    $('#parent_akun_id').empty();
                    $('#parent_akun_id').append('<option value="">Pilih Akun Parent</option>');

                    @foreach ($parentAkunPiutang as $akun)
                        $('#parent_akun_id').append(
                            '<option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>');
                    @endforeach
                } else {
                    parentAkunContainer.hide();
                }
            });
        });

        const saldos = @json($saldos);

        function updateSaldoInfo() {
            const select = document.getElementById('akun_keuangan_id');
            const selectedId = select.value;
            const saldoInfo = document.getElementById('saldo-info');

            if (selectedId && saldos[selectedId] !== undefined) {
                const saldoFormatted = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }).format(saldos[selectedId]);

                saldoInfo.innerText = `Saldo terakhir: ${saldoFormatted}`;
            } else {
                saldoInfo.innerText = '';
            }
        }

        function formatInput(input) {
            let rawValue = input.value.replace(/\D/g, ""); // Hanya angka
            let formatted = new Intl.NumberFormat("id-ID").format(rawValue);

            input.value = formatted; // Tampilkan angka dengan separator
            document.getElementById("jumlah").value = rawValue; // Simpan angka asli tanpa separator
        }
    </script>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Tambah Hutang</h2>

        <form action="{{ route('hutangs.store') }}" method="POST">
            @csrf

            <!-- Simpan bidang user secara tersembunyi -->
            <input type="hidden" name="bidang_name" value="{{ auth()->user()->bidang_name }}">

            <!-- Tujuan Hutang (User dengan Role Bendahara/Bidang di bidang yang sama) -->
            <div class="mb-3">
                <label for="user_id" class="form-label">Hutang ke</label>
                <select name="user_id" id="user_id" class="form-control" required>
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

            <!-- Akun Hutang (Hanya akun dengan kode 201) -->
            <div class="mb-3">
                <label for="akun_keuangan" class="form-label">Asal Akun</label>
                <select id="akun_keuangan" class="form-control" required>
                    <option value="{{ $akunHutang->id }}" selected>
                        {{ $akunHutang->nama_akun }}
                    </option>
                </select>
            </div>

            <!-- Sub Akun Hutang (parent_id = 201) -->
            <div class="mb-3" id="parent-akun-container">
                <label for="parent_akun_id" class="form-label">Tujuan Akun Hutang</label>
                <select name="parent_akun_id" id="parent_akun_id" class="form-control">
                    <option value="">Pilih Sub Akun</option>
                    @foreach ($parentAkunHutang as $akun)
                        <option value="{{ $akun->id }}">{{ $akun->nama_akun }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Jumlah -->
            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah</label>
                <select name="jumlah" id="jumlah" class="form-select" required>
                    <option value="">Pilih Nominal Hutangmu</option>
                    @foreach($piutangs as $piutang)
                        <option value="{{ $piutang->jumlah }}">{{ number_format($piutang->jumlah, 0, ',', '.') }} - {{ $piutang->bidang->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tanggal Jatuh Tempo -->
            <div class="mb-3">
                <label for="tanggal_jatuh_tempo" class="form-label">Tanggal Jatuh Tempo</label>
                <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo"
                    class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror"
                    value="{{ old('tanggal_jatuh_tempo') }}">
                @error('tanggal_jatuh_tempo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Deskripsi -->
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="3" class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status -->
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                    <option value="belum_lunas" {{ old('status') == 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="lunas" {{ old('status') == 'lunas' ? 'selected' : '' }}>Lunas</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('hutangs.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            const parentAkunContainer = $('#parent-akun-container');

            if ($('#akun_keuangan').val() == '201') {
                parentAkunContainer.show();
            } else {
                parentAkunContainer.hide();
            }

            $('#akun_keuangan').on('change', function() {
                if ($(this).val() == '201') {
                    parentAkunContainer.show();
                } else {
                    parentAkunContainer.hide();
                }
            });
        });

        // JSON saldo sudah OK
        const saldos = @json($saldos);

        // Tambah event listener
        document.getElementById('akun_keuangan_id').addEventListener('change', updateSaldoInfo);

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

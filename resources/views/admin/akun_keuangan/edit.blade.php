@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Edit Akun Keuangan</h2>

        <!-- Alert jika ada pesan sukses -->
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Form Edit -->
        <form action="{{ route('admin.akun_keuangan.update', $akunKeuangan->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="mb-2">ID Akun</label>
                <input type="number" id="id" name="id" class="form-control" value="{{ old('id',$akunKeuangan->id) }}" required>
                @error('id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="mb-2">Nama Akun</label>
                <input type="text" name="nama_akun" class="form-control"
                    value="{{ old('nama_akun', $akunKeuangan->nama_akun) }}" required>
                @error('nama_akun')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="mb-2">Tipe Akun</label>
                <select class="form-control" name="tipe_akun" required>
                    <option value="asset" {{ $akunKeuangan->tipe_akun == 'asset' ? 'selected' : '' }}>Asset</option>
                    <option value="liability" {{ $akunKeuangan->tipe_akun == 'liability' ? 'selected' : '' }}>Liability
                    </option>
                    <option value="revenue" {{ $akunKeuangan->tipe_akun == 'revenue' ? 'selected' : '' }}>Revenue</option>
                    <option value="expense" {{ $akunKeuangan->tipe_akun == 'expense' ? 'selected' : '' }}>Expense</option>
                    <option value="equity" {{ $akunKeuangan->tipe_akun == 'equity' ? 'selected' : '' }}>Equity</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="mb-2">Kode Akun</label>
                <input type="text" name="kode_akun" class="form-control"
                    value="{{ old('kode_akun', $akunKeuangan->kode_akun) }}" required>
                @error('kode_akun')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="mb-2">Saldo Normal</label>
                <select class="form-control" name="saldo_normal" required>
                    <option value="debit" {{ $akunKeuangan->saldo_normal == 'debit' ? 'selected' : '' }}>Debit</option>
                    <option value="kredit" {{ $akunKeuangan->saldo_normal == 'kredit' ? 'selected' : '' }}>Kredit</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="mb-2">Induk Akun (Opsional)</label>
                <select class="form-control" name="parent_id">
                    <option value="">- Tidak Ada Induk -</option>
                    @foreach ($akunKeuangantanpaparent as $akun)
                        <option value="{{ $akun->id }}" {{ $akunKeuangan->parent_id == $akun->id ? 'selected' : '' }}>
                            {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="mb-2">Icon (Bootstrap Icons class)</label>
                <input type="text" name="icon" class="form-control" value="{{ old('icon', $akunKeuangan->icon) }}"
                    placeholder="Contoh: bi-cash">
                @error('icon')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="mb-2">Kategori Arus Kas (Opsional)</label>
                <select class="form-control" name="cashflow_category">
                    <option value="">- Tidak Ada -</option>
                    <option value="operasional" {{ $akunKeuangan->cashflow_category == 'operasional' ? 'selected' : '' }}>
                        Operasional</option>
                    <option value="investasi" {{ $akunKeuangan->cashflow_category == 'investasi' ? 'selected' : '' }}>
                        Investasi</option>
                    <option value="pendanaan" {{ $akunKeuangan->cashflow_category == 'pendanaan' ? 'selected' : '' }}>
                        Pendanaan</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('admin.akun_keuangan.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
@endsection
@if (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '{{ session('error') }}'
        });
    </script>
@endif
@if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '{{ implode(', ', $errors->all()) }}'
        });
    </script>
@endif

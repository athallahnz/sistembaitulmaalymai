@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Edit Akun Keuangan</h2>

        {{-- Alert jika ada pesan sukses --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Form Edit --}}
        <form action="{{ route('admin.akun_keuangan.update', $akunKeuangan->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- ID Akun --}}
            <div class="mb-3">
                <label class="mb-2">ID Akun</label>
                <input type="number" id="id" name="id" class="form-control"
                    value="{{ old('id', $akunKeuangan->id) }}" required>
                @error('id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Nama Akun --}}
            <div class="mb-3">
                <label class="mb-2">Nama Akun</label>
                <input type="text" name="nama_akun" class="form-control"
                    value="{{ old('nama_akun', $akunKeuangan->nama_akun) }}" required>
                @error('nama_akun')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tipe Akun --}}
            <div class="mb-3">
                <label class="mb-2">Tipe Akun</label>
                <select class="form-control" name="tipe_akun" required>
                    <option value="asset" {{ old('tipe_akun', $akunKeuangan->tipe_akun) == 'asset' ? 'selected' : '' }}>
                        Asset</option>
                    <option value="liability"
                        {{ old('tipe_akun', $akunKeuangan->tipe_akun) == 'liability' ? 'selected' : '' }}>Liability</option>
                    <option value="revenue" {{ old('tipe_akun', $akunKeuangan->tipe_akun) == 'revenue' ? 'selected' : '' }}>
                        Revenue</option>
                    <option value="expense" {{ old('tipe_akun', $akunKeuangan->tipe_akun) == 'expense' ? 'selected' : '' }}>
                        Expense</option>
                    <option value="equity" {{ old('tipe_akun', $akunKeuangan->tipe_akun) == 'equity' ? 'selected' : '' }}>
                        Equity</option>
                </select>
                @error('tipe_akun')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Kategori PSAK --}}
            <div class="mb-3">
                <label class="mb-2">Kategori PSAK (Opsional)</label>
                @php
                    $kategoriPsak = old('kategori_psak', $akunKeuangan->kategori_psak);
                @endphp
                <select class="form-control" name="kategori_psak">
                    <option value="">- Tidak Ada -</option>
                    <option value="aset_lancar" {{ $kategoriPsak == 'aset_lancar' ? 'selected' : '' }}>Aset Lancar</option>
                    <option value="aset_tidak_lancar" {{ $kategoriPsak == 'aset_tidak_lancar' ? 'selected' : '' }}>Aset
                        Tidak Lancar</option>
                    <option value="liabilitas_jangka_pendek"
                        {{ $kategoriPsak == 'liabilitas_jangka_pendek' ? 'selected' : '' }}>
                        Liabilitas Jangka Pendek
                    </option>
                    <option value="liabilitas_jangka_panjang"
                        {{ $kategoriPsak == 'liabilitas_jangka_panjang' ? 'selected' : '' }}>
                        Liabilitas Jangka Panjang
                    </option>
                    <option value="aset_neto_tidak_terikat"
                        {{ $kategoriPsak == 'aset_neto_tidak_terikat' ? 'selected' : '' }}>
                        Aset Neto Tidak Terikat
                    </option>
                    <option value="aset_neto_terikat_temporer"
                        {{ $kategoriPsak == 'aset_neto_terikat_temporer' ? 'selected' : '' }}>
                        Aset Neto Terikat Temporer
                    </option>
                    <option value="aset_neto_terikat_permanen"
                        {{ $kategoriPsak == 'aset_neto_terikat_permanen' ? 'selected' : '' }}>
                        Aset Neto Terikat Permanen
                    </option>
                    <option value="pendapatan" {{ $kategoriPsak == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                    <option value="beban" {{ $kategoriPsak == 'beban' ? 'selected' : '' }}>Beban</option>
                </select>
                @error('kategori_psak')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Pembatasan --}}
            <div class="mb-3">
                <label class="mb-2">Pembatasan (Opsional)</label>
                @php
                    $pembatasan = old('pembatasan', $akunKeuangan->pembatasan);
                @endphp
                <select class="form-control" name="pembatasan">
                    <option value="">- Tidak Ada -</option>
                    <option value="tidak_terikat" {{ $pembatasan == 'tidak_terikat' ? 'selected' : '' }}>Tidak Terikat
                    </option>
                    <option value="terikat_temporer" {{ $pembatasan == 'terikat_temporer' ? 'selected' : '' }}>Terikat
                        Temporer</option>
                    <option value="terikat_permanen" {{ $pembatasan == 'terikat_permanen' ? 'selected' : '' }}>Terikat
                        Permanen</option>
                </select>
                @error('pembatasan')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Kode Akun --}}
            <div class="mb-3">
                <label class="mb-2">Kode Akun</label>
                <input type="text" name="kode_akun" class="form-control"
                    value="{{ old('kode_akun', $akunKeuangan->kode_akun) }}" required>
                @error('kode_akun')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Saldo Normal --}}
            <div class="mb-3">
                <label class="mb-2">Saldo Normal</label>
                <select class="form-control" name="saldo_normal" required>
                    <option value="debit"
                        {{ old('saldo_normal', $akunKeuangan->saldo_normal) == 'debit' ? 'selected' : '' }}>Debit</option>
                    <option value="kredit"
                        {{ old('saldo_normal', $akunKeuangan->saldo_normal) == 'kredit' ? 'selected' : '' }}>Kredit
                    </option>
                </select>
                @error('saldo_normal')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Akun Kas / Bank --}}
            <div class="mb-3">
                <label class="mb-2 d-block">Kas / Bank?</label>

                {{-- hidden supaya kalau unchecked tetap kirim 0 --}}
                <input type="hidden" name="is_kas_bank" value="0">

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_kas_bank" name="is_kas_bank"
                        value="1" {{ old('is_kas_bank', $akunKeuangan->is_kas_bank) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_kas_bank">
                        Centang jika akun ini termasuk Kas / Bank
                    </label>
                </div>
                @error('is_kas_bank')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Induk Akun --}}
            <div class="mb-3">
                <label class="mb-2">Induk Akun (Opsional)</label>
                <select class="form-control" name="parent_id">
                    <option value="">- Tidak Ada Induk -</option>
                    @foreach ($akunKeuangantanpaparent as $akun)
                        <option value="{{ $akun->id }}"
                            {{ old('parent_id', $akunKeuangan->parent_id) == $akun->id ? 'selected' : '' }}>
                            {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Icon --}}
            <div class="mb-3">
                <label class="mb-2">Icon (Bootstrap Icons class)</label>
                <input type="text" name="icon" class="form-control" value="{{ old('icon', $akunKeuangan->icon) }}"
                    placeholder="Contoh: bi-cash">
                @error('icon')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            {{-- Kategori Arus Kas --}}
            <div class="mb-3">
                <label class="mb-2">Kategori Arus Kas (Opsional)</label>
                @php
                    $cf = old('cashflow_category', $akunKeuangan->cashflow_category);
                @endphp
                <select class="form-control" name="cashflow_category">
                    <option value="">- Tidak Ada -</option>
                    <option value="operasional" {{ $cf == 'operasional' ? 'selected' : '' }}>Operasional</option>
                    <option value="investasi" {{ $cf == 'investasi' ? 'selected' : '' }}>Investasi</option>
                    <option value="pendanaan" {{ $cf == 'pendanaan' ? 'selected' : '' }}>Pendanaan</option>
                </select>
                @error('cashflow_category')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
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

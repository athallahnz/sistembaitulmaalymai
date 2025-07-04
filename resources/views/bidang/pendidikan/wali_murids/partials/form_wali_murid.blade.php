@php
    $jenisKelamin = $hubungan === 'Ayah' ? 'L' : 'P';
@endphp

<div class="mb-3">
    <label>Nama Wali <span class="text-danger">*</span></label>
    <input type="text" name="wali[nama][]" class="form-control" value="{{ old('wali.nama.' . $loopIndex) }}"
        placeholder="Masukkan Nama Wali Murid.." required>
    @error("wali.nama.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Jenis Kelamin</label>
    <select name="wali[jenis_kelamin][]" class="form-select" readonly>
        <option value="L" {{ $jenisKelamin === 'L' ? 'selected' : '' }}>Laki-laki</option>
        <option value="P" {{ $jenisKelamin === 'P' ? 'selected' : '' }}>Perempuan</option>
    </select>
</div>

<div class="mb-3">
    <label>Hubungan</label>
    <input type="text" name="wali[hubungan][]" class="form-control" value="{{ $hubungan }}" readonly>
</div>

<div class="mb-3">
    <label>NIK</label>
    <input type="text" name="wali[nik][]" class="form-control" value="{{ old("wali.nik.$loopIndex") }}"
        placeholder="Masukkan NIK Wali Murid..">
    @error("wali.nik.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>No. Handphone <span class="text-danger">*</span></label>
    <input type="text" name="wali[no_hp][]" class="form-control" value="{{ old("wali.no_hp.$loopIndex") }}"
        placeholder="Masukkan No. Handphone.." required>
    @error("wali.no_hp.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>E-Mail <span class="text-danger">*</span></label>
    <input type="text" name="wali[email][]" class="form-control" value="{{ old("wali.email.$loopIndex") }}"
        placeholder="Masukkan E-Mail.." required>
    @error("wali.email.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Pendidikan Terakhir</label>
    <input type="text" name="wali[pendidikan_terakhir][]" class="form-control"
        value="{{ old("wali.pendidikan_terakhir.$loopIndex") }}" placeholder="Masukkan Pendidikan Terakhir..">
    @error("wali.pendidikan_terakhir.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Pekerjaan</label>
    <input type="text" name="wali[pekerjaan][]" class="form-control" value="{{ old("wali.pekerjaan.$loopIndex") }}"
        placeholder="Masukkan Pekerjaan..">
    @error("wali.pekerjaan.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-check mb-3">
    <input type="checkbox" class="form-check-input" id="copyAlamatWali{{ $loopIndex }}">
    <label class="form-check-label" for="copyAlamatWali{{ $loopIndex }}">
        Gunakan Alamat Utama sbg Alamat Wali Murid
    </label>
</div>
<textarea name="wali[alamat][]" id="alamat_wali_{{ $loopIndex }}" class="form-control">{{ old("wali.alamat.$loopIndex") }}</textarea>

<div class="mb-3">
    <label>Foto KTP</label>
    <input type="file" name="wali[foto_ktp][]" class="form-control">
    @error("wali.foto_ktp.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

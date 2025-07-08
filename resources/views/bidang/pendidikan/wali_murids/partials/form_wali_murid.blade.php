@php
    $jenisKelamin = $hubungan === 'Ayah' ? 'L' : 'P';
@endphp

<div class="mb-3">
    <label>Nama Wali <span class="text-danger">*</span></label>
    <input type="text" name="wali[nama][]" class="form-control"
        value="{{ old("wali.nama.$loopIndex", $wali->nama ?? '') }}" placeholder="Masukkan Nama Wali.." required>
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
    <input type="text" name="wali[nik][]" class="form-control"
        value="{{ old("wali.nik.$loopIndex", $wali->nik ?? '') }}" placeholder="Masukkan NIK Wali Murid..">
    @error("wali.nik.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>No. Handphone <span class="text-danger">*</span></label>
    <input type="text" name="wali[no_hp][]" class="form-control"
        value="{{ old("wali.no_hp.$loopIndex", $wali->no_hp ?? '') }}" placeholder="Masukkan No. Handphone.." required>
    @error("wali.no_hp.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>E-Mail <span class="text-danger">*</span></label>
    <input type="text" name="wali[email][]" class="form-control"
        value="{{ old("wali.email.$loopIndex", $wali->email ?? '') }}" placeholder="Masukkan E-Mail.." required>
    @error("wali.email.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Pendidikan Terakhir</label>
    <input type="text" name="wali[pendidikan_terakhir][]" class="form-control"
        value="{{ old("wali.pendidikan_terakhir.$loopIndex", $wali->pendidikan_terakhir ?? '') }}"
        placeholder="Masukkan Pendidikan Terakhir..">
    @error("wali.pendidikan_terakhir.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Pekerjaan</label>
    <input type="text" name="wali[pekerjaan][]" class="form-control"
        value="{{ old("wali.pekerjaan.$loopIndex", $wali->pekerjaan ?? '') }}" placeholder="Masukkan Pekerjaan..">
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

<textarea name="wali[alamat][]" id="alamat_wali_{{ $loopIndex }}" class="form-control mb-3">{{ old("wali.alamat.$loopIndex", $wali->alamat ?? '') }}</textarea>

<div class="mb-3">
    <label>Foto KTP</label>
    <input type="file" name="wali[foto_ktp][]" class="form-control">
    @if (isset($wali->foto_ktp))
        <a href="{{ asset('storage/' . $wali->foto_ktp) }}" target="_blank">Lihat KTP</a><br>
    @endif

    @error("wali.foto_ktp.$loopIndex")
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

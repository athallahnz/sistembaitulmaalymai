@extends('layouts.app')

@section('content')
    <div class="container">
        <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a>Edit</a></li>
            </ol>
        <h1 class="mb-3">Form Edit Data Murid</h1>

        {{-- Flash Message --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Form Edit --}}
        <form method="POST" action="{{ route('students.update', $student->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Header --}}
            <div class="row mb-2">
                <div class="col-md-6 mb-2">
                    <label for="no_induk">No Induk - Nama</label>
                    <h2>{{ old('no_induk', $student->no_induk) }} - {{ old('name', $student->name) }}</h2>
                </div>

                <div class="col-md-6 mb-2">
                    <label for="edu_class_id">Kelas / Tahun Ajaran</label>
                    <h2>
                        {{ $class->name }} / {{ $class->tahun_ajaran }}
                    </h2>
                    @error('edu_class_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                {{-- Kolom 1: Data Diri Murid --}}
                <div class="col-md-4">
                    <h4 class="mb-3"><strong>Data Diri Murid</strong></h4>
                    <div class="mb-3">
                        <label for="rfid_uid">RFID UID</label>
                        <input type="text" name="rfid_uid" class="form-control @error('rfid_uid') is-invalid @enderror"
                            value="{{ old('rfid_uid', $student->rfid_uid) }}">
                        @error('rfid_uid')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror">
                            <option value="L"
                                {{ old('jenis_kelamin', $student->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki
                            </option>
                            <option value="P"
                                {{ old('jenis_kelamin', $student->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan
                            </option>
                        </select>
                        @error('jenis_kelamin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir"
                                class="form-control @error('tempat_lahir') is-invalid @enderror"
                                value="{{ old('tempat_lahir', $student->tempat_lahir) }}">
                            @error('tempat_lahir')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="ttl">Tanggal Lahir</label>
                            <input type="date" name="ttl" class="form-control @error('ttl') is-invalid @enderror"
                                value="{{ old('ttl', \Carbon\Carbon::parse($student->ttl)->format('Y-m-d')) }}">

                            @error('ttl')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="usia">Usia</label>
                        <input type="text" name="usia" class="form-control @error('usia') is-invalid @enderror"
                            value="{{ old('usia', $student->usia) }}">
                        @error('usia')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="nik">NIK</label>
                        <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror"
                            value="{{ old('nik', $student->nik) }}">
                        @error('nik')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="no_akte">No Akte</label>
                        <input type="text" name="no_akte" class="form-control @error('no_akte') is-invalid @enderror"
                            value="{{ old('no_akte', $student->no_akte) }}">
                        @error('no_akte')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="no_kk">No. Kartu Keluarga</label>
                        <input type="text" name="no_kk" class="form-control @error('no_kk') is-invalid @enderror"
                            value="{{ old('no_kk', $student->no_kk) }}">
                        @error('no_kk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Alamat KK (Utama) -->
                    <div class="mb-3">
                        <label for="alamat_kk">Alamat KK (Utama)</label>
                        <textarea name="alamat_kk" class="form-control @error('alamat_kk') is-invalid @enderror" id="alamat_kk">{{ old('alamat_kk', $student->alamat_kk) }}</textarea>
                        @error('alamat_kk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Checkbox: Gunakan Alamat Utama sbg Alamat Tinggal -->
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="copyAlamatTinggal">
                        <label class="form-check-label" for="copyAlamatTinggal">
                            Gunakan Alamat Utama sbg Alamat Tinggal
                        </label>
                    </div>

                    <!-- Alamat Tinggal -->
                    <div class="mb-3">
                        <label for="alamat_tinggal">Alamat Tinggal</label>
                        <textarea name="alamat_tinggal" id="alamat_tinggal" class="form-control">{{ old('alamat_tinggal', $student->alamat_tinggal) }}</textarea>
                        @error('alamat_tinggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="pas_photo">Pas Photo</label>
                        <input type="file" name="pas_photo"
                            class="form-control @error('pas_photo') is-invalid @enderror">
                        @if ($student->pas_photo)
                            <small class="form-text text-muted">
                                <strong>Pas Foto Saat Ini:</strong>
                                @if ($student->pas_photo)
                                    <a href="{{ asset('storage/' . $student->pas_photo) }}" target="_blank">Lihat Pas
                                        Photo</a>
                                @else
                                    <em>Tidak ada</em>
                                @endif
                            </small>
                        @endif
                        @error('pas_photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="akte">Dokumen Akte</label>
                        <input type="file" name="akte" class="form-control @error('akte') is-invalid @enderror">
                        @if ($student->akte)
                            <small class="form-text text-muted">
                                <strong>Dokumen Akte Saat Ini:</strong>
                                @if ($student->akte)
                                    <a href="{{ asset('storage/' . $student->akte) }}" target="_blank">Lihat Akta</a>
                                @else
                                    <em>Tidak ada</em>
                                @endif
                            </small>
                        @endif
                        @error('akte')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="kk">Dokumen KK</label>
                        <input type="file" name="kk" class="form-control @error('kk') is-invalid @enderror">
                        @if ($student->kk)
                            <small class="form-text text-muted">
                                <strong>Dokumen KK Saat Ini:</strong>
                                <a href="{{ asset('storage/' . $student->kk) }}" target="_blank">Lihat KK</a>
                            </small>
                        @else
                            <em>Tidak ada</em>
                        @endif
                        @error('kk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                {{-- Kolom 2: Data Wali Murid --}}
                <div class="col-md-4">
                    <h4 class="mb-3"><strong>Data Wali Murid</strong></h4>

                    {{-- Nama Wali --}}
                    <div class="mb-3">
                        <label for="wali_nama" class="form-label">Nama Wali</label>
                        <input type="text" name="wali_nama" id="wali_nama"
                            class="form-control @error('wali_nama') is-invalid @enderror"
                            value="{{ old('wali_nama', $student->waliMurid->nama ?? '-') }}">
                        @error('wali_nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Jenis Kelamin --}}
                    <div class="mb-3">
                        <label for="wali_jenis_kelamin" class="form-label">Jenis Kelamin</label>
                        <select name="wali_jenis_kelamin" id="wali_jenis_kelamin"
                            class="form-control @error('wali_jenis_kelamin') is-invalid @enderror">
                            <option value="">-- Pilih --</option>
                            <option value="L"
                                {{ old('wali_jenis_kelamin', $student->waliMurid->jenis_kelamin ?? '') == 'L' ? 'selected' : '' }}>
                                Laki-laki</option>
                            <option value="P"
                                {{ old('wali_jenis_kelamin', $student->waliMurid->jenis_kelamin ?? '') == 'P' ? 'selected' : '' }}>
                                Perempuan</option>
                        </select>
                        @error('wali_jenis_kelamin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Hubungan --}}
                    <div class="mb-3">
                        <label for="wali_hubungan" class="form-label">Hubungan</label>
                        <input type="text" name="wali_hubungan" id="wali_hubungan"
                            class="form-control @error('wali_hubungan') is-invalid @enderror"
                            value="{{ old('wali_hubungan', $student->waliMurid->hubungan ?? '-') }}">
                        @error('wali_hubungan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- NIK --}}
                    <div class="mb-3">
                        <label for="wali_nik" class="form-label">NIK</label>
                        <input type="text" name="wali_nik" id="wali_nik"
                            class="form-control @error('wali_nik') is-invalid @enderror"
                            value="{{ old('wali_nik', $student->waliMurid->nik ?? '-') }}">
                        @error('wali_nik')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- No. HP --}}
                    <div class="mb-3">
                        <label for="wali_no_hp" class="form-label">No. Handphone</label>
                        <input type="text" name="wali_no_hp" id="wali_no_hp"
                            class="form-control @error('wali_no_hp') is-invalid @enderror"
                            value="{{ old('wali_no_hp', $student->waliMurid->no_hp ?? '-') }}">
                        @error('wali_no_hp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="wali_email" class="form-label">Email</label>
                        <input type="email" name="wali_email" id="wali_email"
                            class="form-control @error('wali_email') is-invalid @enderror"
                            value="{{ old('wali_email', $student->waliMurid->email ?? '-') }}">
                        @error('wali_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Pendidikan Terakhir --}}
                    <div class="mb-3">
                        <label for="wali_pendidikan_terakhir" class="form-label">Pendidikan Terakhir</label>
                        <input type="text" name="wali_pendidikan_terakhir" id="wali_pendidikan_terakhir"
                            class="form-control @error('wali_pendidikan_terakhir') is-invalid @enderror"
                            value="{{ old('wali_pendidikan_terakhir', $student->waliMurid->pendidikan_terakhir ?? '-') }}">
                        @error('wali_pendidikan_terakhir')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Pekerjaan --}}
                    <div class="mb-3">
                        <label for="wali_pekerjaan" class="form-label">Pekerjaan</label>
                        <input type="text" name="wali_pekerjaan" id="wali_pekerjaan"
                            class="form-control @error('wali_pekerjaan') is-invalid @enderror"
                            value="{{ old('wali_pekerjaan', $student->waliMurid->pekerjaan ?? '-') }}">
                        @error('wali_pekerjaan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Checkbox: Gunakan Alamat Utama sbg Alamat Wali -->
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="copyAlamatWali">
                        <label class="form-check-label" for="copyAlamatWali">
                            Gunakan Alamat Utama sbg Alamat Wali Murid
                        </label>
                    </div>

                    <!-- Alamat Wali -->
                    <div class="mb-3">
                        <label for="wali_alamat">Alamat Wali Murid</label>
                        <textarea name="wali_alamat" id="wali_alamat" class="form-control">{{ old('wali_alamat', $student->waliMurid->alamat) }}</textarea>
                        @error('wali_alamat')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Foto KTP --}}
                    <div class="mb-3">
                        <label for="wali_foto_ktp" class="form-label">Foto KTP</label>
                        <input type="file" name="wali_foto_ktp" id="wali_foto_ktp"
                            class="form-control @error('wali_foto_ktp') is-invalid @enderror">
                        <small class="form-text text-muted">
                            <strong>Foto KTP Saat Ini:</strong>
                            @if ($student->waliMurid->foto_ktp)
                                <a href="{{ asset('storage/' . $student->waliMurid->foto_ktp) }}" target="_blank">Lihat
                                    Foto KTP</a>
                            @else
                                <em>Tidak ada</em>
                            @endif
                        </small>
                        @error('wali_foto_ktp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>


                {{-- Kolom 3: Rincian Biaya --}}
                <div class="col-md-4">
                    <h4 class="mb-3"><strong>Rincian Biaya</strong></h4>
                    @foreach ($studentCosts as $cost)
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <h4>{{ $cost->nama_akun }}</h4>
                            </div>
                            <div class="col-md-6">
                                <h4>{{ number_format($cost->jumlah, 0, ',', '.') }}</h4>
                            </div>
                        </div>
                    @endforeach
                    <hr>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <h4><strong>Total Biaya</strong></h4>
                        </div>
                        <div class="col-md-6">
                            <h4><strong>{{ number_format(old('total_biaya', $student->total_biaya), 0, ',', '.') }}</strong>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tombol --}}
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>


    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("input[name='ttl']", {
            dateFormat: "d/m/Y", // ini yang akan dikirim
            altInput: true,
            altFormat: "d/m/Y",
            altInputClass: "form-control", // agar tetap pakai style Bootstrap
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    const birthDate = selectedDates[0];
                    const today = new Date();

                    let years = today.getFullYear() - birthDate.getFullYear();
                    let months = today.getMonth() - birthDate.getMonth();
                    let days = today.getDate() - birthDate.getDate();

                    if (days < 0) {
                        // Ambil jumlah hari bulan sebelumnya
                        const prevMonth = new Date(today.getFullYear(), today.getMonth(), 0).getDate();
                        days += prevMonth;
                        months--;
                    }

                    if (months < 0) {
                        months += 12;
                        years--;
                    }

                    document.querySelector("input[name='usia']").value =
                        `${years} tahun ${months} bulan ${days} hari`;
                }
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alamatKK = document.getElementById('alamat_kk');
            const alamatTinggal = document.getElementById('alamat_tinggal');
            const alamatWali = document.getElementById('alamat_wali');

            const checkboxTinggal = document.getElementById('copyAlamatTinggal');
            const checkboxWali = document.getElementById('copyAlamatWali');

            checkboxTinggal.addEventListener('change', function() {
                if (this.checked) {
                    alamatTinggal.value = alamatKK.value;
                    alamatTinggal.setAttribute('readonly', true);
                } else {
                    alamatTinggal.removeAttribute('readonly');
                    alamatTinggal.value = '';
                }
            });

            checkboxWali.addEventListener('change', function() {
                if (this.checked) {
                    alamatWali.value = alamatKK.value;
                    alamatWali.setAttribute('readonly', true);
                } else {
                    alamatWali.removeAttribute('readonly');
                    alamatWali.value = '';
                }
            });
        });
    </script>
@endpush

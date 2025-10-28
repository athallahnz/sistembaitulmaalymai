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
            <div class="alert alert-success">{{ session(key: 'success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Terjadi kesalahan!</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Form Edit --}}
        <form method="POST"
            action="{{ isset($student) ? route('students.update', $student->id) : route('students.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if (isset($student))
                @method('PUT')
            @endif

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

            @include('bidang.pendidikan.partials.form', [
                'student' => $student,
                'akunKeuangans' => $akunKeuangans,
            ])

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
            const checkboxTinggal = document.getElementById('copyAlamatTinggal');

            if (alamatKK && alamatTinggal && checkboxTinggal) {
                checkboxTinggal.addEventListener('change', function() {
                    if (this.checked) {
                        alamatTinggal.value = alamatKK.value;
                        alamatTinggal.setAttribute('readonly', true);
                    } else {
                        alamatTinggal.removeAttribute('readonly');
                        alamatTinggal.value = '';
                    }
                });

                alamatKK.addEventListener('input', function() {
                    if (checkboxTinggal.checked) {
                        alamatTinggal.value = alamatKK.value;
                    }
                });
            }

            // Checkbox untuk Alamat Wali Murid (Ayah & Ibu)
            document.querySelectorAll('[id^="copyAlamatWali"]').forEach(function(checkbox) {
                const index = checkbox.id.replace('copyAlamatWali', '');
                const target = document.getElementById(`alamat_wali_${index}`);

                if (!target || !alamatKK) return;

                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        target.value = alamatKK.value;
                        target.setAttribute('readonly', true);
                    } else {
                        target.value = '';
                        target.removeAttribute('readonly');
                    }
                });

                alamatKK.addEventListener('input', function() {
                    if (checkbox.checked) {
                        target.value = alamatKK.value;
                    }
                });
            });
        });
    </script>
@endpush

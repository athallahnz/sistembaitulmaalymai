@extends('layouts.app')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('edu_classes.index') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a>Edit</a></li>
            </ol>
        </nav>
        <h1 class="mb-4">Edit Kelas</h1>
        <form action="{{ route('edu_classes.update', $eduClass->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Nama Kelas</label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $eduClass->name) }}"
                    required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="row">
                <!-- Tahun Awal Ajaran -->
                <div class="col-md-6 mb-3">
                    <label for="tahun_awal" class="form-label">Tahun Awal Ajaran:</label>
                    <select class="form-select" id="tahun_awal" name="tahun_awal" required>
                        <option value="" disabled selected>Pilih Tahun</option>
                        @php
                            $startYear = date('Y'); // tahun sekarang
                            $endYear = $startYear + 5; // 5 tahun ke depan
                        @endphp
                        @for ($year = $startYear; $year <= $endYear; $year++)
                            <option value="{{ $year }}" {{ old('tahun_awal') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Tahun Ajaran Preview -->
                <div class="col-md-6 mb-3">
                    <label for="tahun_ajaran" class="form-label">Tahun Ajaran (Preview):</label>
                    <input type="text" class="form-control" id="tahun_ajaran" value="{{ old('tahun_ajaran', $eduClass->tahun_ajaran) }}" readonly>
                </div>
            </div>

            <button type="submit" class="btn btn-warning">Update</button>
            <a href="{{ route('edu_classes.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
@endsection
@push('script')
    <script>
        const tahunAwalInput = document.getElementById('tahun_awal');
        const tahunAjaranInput = document.getElementById('tahun_ajaran');

        tahunAwalInput.addEventListener('input', () => {
            const tahunAwal = parseInt(tahunAwalInput.value);
            if (!isNaN(tahunAwal)) {
                tahunAjaranInput.value = `${tahunAwal}/${tahunAwal + 1}`;
            } else {
                tahunAjaranInput.value = '';
            }
        });
    </script>
@endpush

@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-3">Edit Data Siswa</h1>

        {{-- Flash Message --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Form Edit --}}
        <form method="POST" action="{{ route('students.update', $student->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Nama:</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                    value="{{ old('name', $student->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="edu_class_id" class="form-label">Kelas:</label>
                <select class="form-select @error('edu_class_id') is-invalid @enderror" id="edu_class_id"
                    name="edu_class_id" required>
                    <option value="" disabled>Pilih Kelas</option>
                    @foreach ($eduClasses as $class)
                        <option value="{{ $class->id }}"
                            {{ old('edu_class_id', $student->edu_class_id) == $class->id ? 'selected' : '' }}>
                            {{ $class->name }} - {{ $class->tahun_ajaran }}
                        </option>
                    @endforeach
                </select>
                @error('edu_class_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            <div class="mb-3">
                <label for="total_biaya" class="form-label">Total Biaya:</label>
                <input type="number" class="form-control @error('total_biaya') is-invalid @enderror" id="total_biaya"
                    name="total_biaya" value="{{ old('total_biaya', $student->total_biaya) }}" required>
                @error('total_biaya')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="rfid_uid" class="form-label">RFID UID:</label>
                <input type="text" class="form-control @error('rfid_uid') is-invalid @enderror" id="rfid_uid"
                    name="rfid_uid" value="{{ old('rfid_uid', $student->rfid_uid) }}" required>
                @error('rfid_uid')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('students.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
@endsection

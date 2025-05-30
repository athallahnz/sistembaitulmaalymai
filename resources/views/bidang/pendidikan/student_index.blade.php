@extends('layouts.app')

@section('content')
    <div class="container">

        <h1>Data Murid</h1>

        {{-- Flash Message --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Tombol Tambah --}}
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#studentModal">
            Tambah Siswa
        </button>

        {{-- Tabel Data Murid --}}
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Total Biaya</th>
                    <th>RFID UID</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>{{ $student->name }}</td>
                        <td>{{ $student->kelas }}</td>
                        <td>Rp {{ number_format($student->total_biaya, 0, ',', '.') }}</td>
                        <td>{{ $student->rfid_uid }}</td>
                        <td>
                            <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-warning">Edit</a>

                            <form action="{{ route('students.destroy', $student) }}" method="POST"
                                style="display:inline-block;" onsubmit="return confirm('Yakin hapus data ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data murid</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        {{ $students->links() }}

        {{-- Modal Form Tambah --}}
        <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('students.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="studentModalLabel">Daftar Murid Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama:</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="kelas" class="form-label">Kelas:</label>
                                <input type="text" class="form-control @error('kelas') is-invalid @enderror"
                                    id="kelas" name="kelas" value="{{ old('kelas') }}" required>
                                @error('kelas')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="total_biaya" class="form-label">Total Biaya:</label>
                                <input type="number" class="form-control @error('total_biaya') is-invalid @enderror"
                                    id="total_biaya" name="total_biaya" value="{{ old('total_biaya') }}" required>
                                @error('total_biaya')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="rfid_uid_input" class="form-label">Tempelkan Kartu RFID:</label>
                                <input type="text" class="form-control @error('rfid_uid') is-invalid @enderror"
                                    id="rfid_uid_input" name="rfid_uid" value="{{ old('rfid_uid') }}" required autofocus>
                                @error('rfid_uid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Daftarkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @if ($errors->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                var myModal = new bootstrap.Modal(document.getElementById('studentModal'));
                myModal.show();

                // Fokus ke input RFID saat modal ditampilkan
                document.getElementById('studentModal').addEventListener('shown.bs.modal', function() {
                    document.getElementById('rfid_uid_input').focus();
                });
            });
        </script>
    @endif
@endsection

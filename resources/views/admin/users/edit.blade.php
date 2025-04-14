@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h2>Edit Pengguna</h2>

        <form action="{{ route('users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Nama</label>
                <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nomor Telepon</label>
                <input type="text" class="form-control" name="nomor" value="{{ $user->nomor }}">
            </div>

            <div class="mb-3">
                <label class="form-label">PIN (Kosongkan jika tidak diubah)</label>
                <input type="password" class="form-control" name="pin">
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-control" name="role" id="role">
                    <option value="User" {{ $user->role == 'User' ? 'selected' : '' }}>User</option>
                    <option value="Admin" {{ $user->role == 'Admin' ? 'selected' : '' }}>Admin</option>
                    <option value="Ketua Yayasan" {{ $user->role == 'Ketua Yayasan' ? 'selected' : '' }}>Ketua Yayasan
                    </option>
                    <option value="Bendahara" {{ $user->role == 'Bendahara' ? 'selected' : '' }}>Bendahara</option>
                    <option value="Manajer Keuangan" {{ $user->role == 'Manajer Keuangan' ? 'selected' : '' }}>Manajer
                        Keuangan</option>
                    <option value="Bidang" {{ $user->role == 'Bidang' ? 'selected' : '' }}>Bidang</option>
                </select>
            </div>

            <div class="mb-3" id="bidang_name_div" style="display: {{ $user->role == 'Bidang' ? 'block' : 'none' }};">
                <label class="form-label">Bidang</label>
                <select class="form-control" name="bidang_name">
                    <option value="Kemasjidan" {{ $user->bidang_name == 'Kemasjidan' ? 'selected' : '' }}>Kemasjidan
                    </option>
                    <option value="Pendidikan" {{ $user->bidang_name == 'Pendidikan' ? 'selected' : '' }}>Pendidikan
                    </option>
                    <option value="Sosial" {{ $user->bidang_name == 'Sosial' ? 'selected' : '' }}>Sosial</option>
                    <option value="Usaha" {{ $user->bidang_name == 'Usaha' ? 'selected' : '' }}>Usaha</option>
                    <option value="Pembangunan" {{ $user->bidang_name == 'Pembangunan' ? 'selected' : '' }}>Pembangunan
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
    @push('scripts')
        <script>
            // Menambahkan event listener untuk mengubah tampilan bidang_name berdasarkan role
            document.getElementById('role').addEventListener('change', function() {
                var bidangDiv = document.getElementById('bidang_name_div');
                if (this.value === 'Bidang') {
                    bidangDiv.style.display = 'block';
                } else {
                    bidangDiv.style.display = 'none';
                }
            });
        </script>
    @endpush
@endsection

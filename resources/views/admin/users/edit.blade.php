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
                    @php $roleValue = old('role', $user->role); @endphp
                    <option value="User" {{ $roleValue == 'User' ? 'selected' : '' }}>User</option>
                    <option value="Admin" {{ $roleValue == 'Admin' ? 'selected' : '' }}>Admin</option>
                    <option value="Ketua Yayasan" {{ $roleValue == 'Ketua Yayasan' ? 'selected' : '' }}>Ketua Yayasan
                    </option>
                    <option value="Bendahara" {{ $roleValue == 'Bendahara' ? 'selected' : '' }}>Bendahara</option>
                    <option value="Manajer Keuangan" {{ $roleValue == 'Manajer Keuangan' ? 'selected' : '' }}>Manajer
                        Keuangan</option>
                    <option value="Bidang" {{ $roleValue == 'Bidang' ? 'selected' : '' }}>Bidang</option>
                </select>
            </div>

            <div class="mb-3" id="bidang_name_div"
                style="display: {{ old('role', $user->role) == 'Bidang' ? 'block' : 'none' }};">
                <label class="form-label">Bidang</label>
                <select class="form-control" name="bidang_name">
                    <option value="">-- Pilih Bidang --</option>
                    @foreach ($bidangs as $bidang)
                        <option value="{{ $bidang->id }}"
                            {{ (string) old('bidang_name', $user->bidang_name) === (string) $bidang->id ? 'selected' : '' }}>
                            {{ $bidang->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        function toggleBidangField() {
            var roleSelect = document.getElementById('role');
            var bidangDiv = document.getElementById('bidang_name_div');

            if (roleSelect.value === 'Bidang') {
                bidangDiv.style.display = 'block';
            } else {
                bidangDiv.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initial state
            toggleBidangField();

            // On change
            document.getElementById('role').addEventListener('change', toggleBidangField);
        });
    </script>
@endpush

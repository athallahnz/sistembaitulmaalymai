@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Tambah Pengguna</h2>

        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Nomor</label>
                <input type="text" name="nomor" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>PIN</label>
                <input type="password" name="pin" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Role</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="User">User</option>
                    <option value="Admin">Admin</option>
                    <option value="Ketua Yayasan">Ketua Yayasan</option>
                    <option value="Bendahara">Bendahara</option>
                    <option value="Manajer Keuangan">Manajer Keuangan</option>
                    <option value="Bidang">Bidang</option>
                </select>
            </div>
            <div class="form-group mb-3" id="bidang_name_group" style="display: none;">
                <label for="bidang_name">Bidang Name</label>
                <select class="form-control" id="bidang_name" name="bidang_name">
                    <option value="Kemasjidan">Kemasjidan</option>
                    <option value="Pendidikan">Pendidikan</option>
                    <option value="Sosial">Sosial</option>
                    <option value="Usaha">Usaha</option>
                    <option value="Pembangunan">Pembangunan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
        <script>
            // Mengubah tampilan field bidang_name berdasarkan pilihan role
            document.getElementById('role').addEventListener('change', function() {
                var role = this.value;
                var bidangField = document.getElementById('bidang_name_group');

                // Jika role adalah Bidang, tampilkan kolom bidang_name
                if (role === 'Bidang') {
                    bidangField.style.display = 'block';
                } else {
                    bidangField.style.display = 'none';
                }
            });
        </script>
    </div>
@endsection

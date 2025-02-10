@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-center align-items-center">
        <div class="card shadow-lg" style="width: 350px; border-radius: 15px;">
            <div class="card-body">
                <h4 class="card-title text-center mb-4">Edit Profil</h4>

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Input Bidang Name (Hanya untuk Role: Bidang) -->
                    @if (auth()->user()->role === 'Bidang')
                        <div class="mb-3">
                            <label for="bidang_name" class="form-label">Bidang Name</label>
                            <input type="text" class="form-control" id="bidang_name" name="bidang_name"
                                value="{{ old('bidang_name', auth()->user()->bidang_name) }}" readonly>
                        </div>
                    @endif

                    <!-- Input Nama -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ old('name', auth()->user()->name) }}" required>
                    </div>

                    <!-- Input Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ old('email', auth()->user()->email) }}" required>
                    </div>

                    <!-- Input Nomor -->
                    <div class="mb-3">
                        <label for="nomor" class="form-label">Nomor</label>
                        <input type="text" class="form-control" id="nomor" name="nomor"
                            value="{{ old('nomor', auth()->user()->nomor) }}" required>
                    </div>

                    <!-- Input PIN -->
                    <div class="mb-3">
                        <label for="pin" class="form-label">PIN</label>
                        <input type="password" class="form-control" id="pin" name="pin"
                            placeholder="Kosongkan jika tidak ingin mengubah">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
@endsection

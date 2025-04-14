@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-center align-items-center">
        <div class="card shadow-lg mx-auto" style="width: 350px; border-radius: 15px;">
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3">
                    <img id="profile-image"
                        src="{{ auth()->user()->foto ? asset('storage/' . auth()->user()->foto) : asset('default.jpg') }}"
                        class="rounded-circle" width="100" height="100"
                        style="object-fit: cover; border: 4px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

                    <label for="foto"
                        class="position-absolute bottom-0 end-0 translate-middle-x bg-white rounded-circle shadow d-flex justify-content-center align-items-center me-1 mb-1"
                        style="width: 25px; height: 25px; cursor: pointer; border: 10px solid #ffffff;">
                        <i class="bi bi-pencil-fill text-primary" style="font-size: 12px;"></i>
                    </label>
                </div>

                <h5 class="text-danger mb-0">{{ auth()->user()->name }}</h5>
                @if (auth()->user()->role === 'Admin' ||
                        auth()->user()->role === 'Bendahara' ||
                        auth()->user()->role === 'Ketua Yayasan' ||
                        auth()->user()->role === 'Manajer Keuangan')
                    <p class="text-muted mb-2">{{ auth()->user()->role }}</p>
                @else
                    <p class="text-muted mb-2">{{ auth()->user()->role }} {{ auth()->user()->bidang->name }}</p>
                @endif


                @if (session('success'))
                    <div class="alert alert-success mt-2">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="form-profile">
                    @csrf
                    @method('PUT')

                    @if (auth()->user()->role === 'Bidang')
                        <div class="mb-3 text-start">
                            <label for="bidang_name" class="form-label">Bidang</label>
                            <input type="text" class="form-control" id="bidang_name" name="bidang_name"
                                value="{{ old('bidang_name', auth()->user()->bidang->name) }}" readonly>
                        </div>
                    @endif

                    <div class="mb-3 text-start">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ old('name', auth()->user()->name) }}" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ old('email', auth()->user()->email) }}" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label for="nomor" class="form-label">Nomor</label>
                        <input type="text" class="form-control" id="nomor" name="nomor"
                            value="{{ old('nomor', auth()->user()->nomor) }}" required>
                    </div>

                    <div class="mb-3 text-start d-none">
                        <input type="file" id="foto" name="foto" accept="image/*">
                    </div>

                    <div class="mb-4 text-start">
                        <label for="pin" class="form-label">PIN</label>
                        <input type="password" class="form-control" id="pin" name="pin"
                            placeholder="Kosongkan jika tidak ingin mengubah">
                    </div>

                    <button type="submit" class="btn btn-danger w-100">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const input = document.getElementById('foto');
            const profileImage = document.getElementById('profile-image');

            input.addEventListener('change', function(event) {
                const file = event.target.files[0];

                if (file) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        profileImage.src = e.target.result; // Preview langsung
                    };

                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
@endpush

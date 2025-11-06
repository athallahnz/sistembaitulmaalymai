@extends('layouts.public-warga')

@section('title', 'Login Tracking Infaq')

@section('content')
    <div class="container py-5" style="max-width:520px;">
        <h3 class="mb-3 text-center">Tracking Infaq Warga</h3>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('warga.login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nomor HP</label>
                        <input type="text" name="hp" class="form-control" placeholder="Masukkan Nomor Handphone..."
                            value="{{ old('hp') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PIN</label>
                        <input type="password" name="pin" class="form-control" placeholder="Masukkan PIN..." required>
                        <div class="form-text">PIN diberikan oleh Bidang Sosial.</div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Masuk</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.public-warga')

@section('title', 'Login Tracking Infaq | Sistem Baitul Maal Yayasan Al Iman Sutorejo Indah')

@section('content')
    {{-- Utility lokal untuk page ini saja --}}
    <style>
        /* Estimasi tinggi nav+footer total ≈ 120px (sesuaikan jika perlu) */
        .fit-viewport {
            min-height: calc(75svh - 120px);
            display: grid;
            place-items: center;
            padding: 0;
            /* nolkan padding bawaan supaya nggak nambah tinggi */
        }

        .login-header {
            margin-bottom: .75rem;
        }

        /* < 12px */
        .login-card {
            max-width: 340px;
            width: 100%;
        }

        /* lebih ramping */
        .login-card .card-body {
            padding: 1.25rem;
        }

        /* 20px */
        .login-alert {
            max-width: 340px;
            margin-bottom: .5rem;
        }

        /* Kecilkan input biar nggak nambah tinggi total */
        .login-input {
            border-radius: var(--radius-sm);
        }

        .form-text {
            margin-top: .25rem;
        }
    </style>

    <div class="container-fluid fit-viewport">
        <div class="w-100 d-flex flex-column align-items-center">
            {{-- Header kecil --}}
            <header class="text-center login-header">
                <h3 class="section-heading justify-content-center">
                    <span class="text-brown">Tracking Infaq Warga</span>
                </h3>
                <div class="divider mx-auto" style="width:56px;height:3px;"></div>
            </header>

            {{-- Flash message (ramping & center) --}}
            @if (session('error'))
                <div class="alert alert-danger shadow-sm text-center login-alert">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success shadow-sm text-center login-alert">{{ session('success') }}</div>
            @endif

            {{-- Card login ramping & ringan --}}
            <div class="card glass shadow-sm border-0 login-card">
                <div class="card-body">
                    <form method="POST" action="{{ route('warga.login') }}">
                        @csrf

                        <div class="mb-2">
                            <label class="form-label fw-semibold mb-1">Nomor HP</label>
                            <input type="text" name="hp" class="form-control login-input"
                                placeholder="Masukkan Nomor Handphone..." value="{{ old('hp') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-1">PIN</label>
                            <input type="password" name="pin" class="form-control login-input"
                                placeholder="Masukkan PIN..." required>
                            <div class="form-text">PIN diberikan oleh Bidang Sosial.</div>
                        </div>

                        <button class="btn btn-brown w-100" type="submit" style="border-radius: var(--radius-pill);">
                            Masuk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

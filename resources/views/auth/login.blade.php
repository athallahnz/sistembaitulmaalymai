<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login Baitul Maal Yayasan Masjid Al Iman">
    <meta name="author" content="AnzArt Studio">

    <title>Login | Baitul Maal Yayasan Masjid Al Iman</title>

    <link rel="shortcut icon" href="{{ asset('img/icons/favicon.ico') }}" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @php
        $sidebarSetting = \App\Models\SidebarSetting::first();
    @endphp

    <style>
        :root {
            --primary-color: {{ $sidebarSetting->background_color ?? '#622200' }};
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .btn-primary:hover {
            background-color: darken(var(--primary-color), 5%);
            border-color: darken(var(--primary-color), 5%);
        }

        body {
            background: url("{{ $sidebarSetting?->background_login ? asset('storage/' . $sidebarSetting->background_login) : asset('img/photos/img-background-login.webp') }}") no-repeat center center fixed;
            background-size: cover;
        }

        .card {
            max-width: 300px;
            /* Sesuaikan lebar agar fit dengan keypad */
            margin: auto;
            background-color: rgba(255, 255, 255, 0.8);
            /* Transparan putih */
            border-radius: 15px;
        }

        .card-body {
            padding: 20px;
            text-align: center;
        }

        .btn-circle {
            width: 60px;
            height: 60px;
            font-size: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-outline-dark {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            background-color: transparent !important;
        }

        .btn-outline-dark:hover {
            color: white !important;
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .pin-dots {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .dot {
            width: 10px;
            height: 10px;
            background-color: rgb(181, 181, 181);
            border-radius: 50%;
        }

        .dot.active {
            background-color: var(--primary-color);
        }

        .fade-in {
            animation: fadeIn 0.9s ease-in-out;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-md-6 col-lg-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">
                        <div class="card mx-auto">
                            <div class="card-body fade-in">
                                <div class="m-sm">
                                    @if (session('step') === 'pin' && session('nomor'))
                                        {{-- FORM PIN --}}
                                        <form method="POST" action="{{ route('login') }}">
                                            @csrf
                                            <input type="hidden" name="nomor" value="{{ session('nomor') }}">

                                            <h6 class="mb-3">Masukkan PIN untuk:
                                                <strong>{{ session('nomor') }}</strong>
                                            </h6>

                                            <div class="pin-dots">
                                                @for ($i = 1; $i <= 6; $i++)
                                                    <div class="dot" id="dot{{ $i }}"></div>
                                                @endfor
                                            </div>

                                            <input type="password" name="pin" id="pin"
                                                class="form-control text-center" hidden readonly>

                                            <div class="text-center">
                                                <div class="d-flex flex-column align-items-center gap-2">
                                                    @foreach ([['1', '2', '3'], ['4', '5', '6'], ['7', '8', '9']] as $row)
                                                        <div class="d-flex gap-2">
                                                            @foreach ($row as $num)
                                                                <button type="button"
                                                                    class="btn btn-outline-dark btn-circle"
                                                                    onclick="appendPin('{{ $num }}')">{{ $num }}</button>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-danger btn-circle"
                                                            onclick="clearPin()">
                                                            <i class="bi bi-backspace"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-dark btn-circle"
                                                            onclick="appendPin('0')">0</button>
                                                        <button type="submit" class="btn btn-primary btn-circle"
                                                            id="submitBtn" disabled>
                                                            <i class="bi bi-arrow-right"></i>
                                                        </button>
                                                    </div>
                                                    <a href="#" onclick="resetStep()"
                                                        class="btn btn-primary w-100 py-2 fs-6 mt-3">
                                                        <i class="bi bi-arrow-left-circle ms-1"></i> Kembali</a>
                                                </div>
                                            </div>
                                        </form>
                                    @else
                                        {{-- FORM NOMOR --}}
                                        <form method="POST" action="{{ route('login.nomor') }}">
                                            @csrf
                                            <div class="text-center mb-4">
                                                @if ($sidebarSetting?->logo_path)
                                                    <img src="{{ asset('storage/' . $sidebarSetting->logo_path) }}"
                                                        alt="Logo" class="img-fluid mb-3" style="max-height: 90px;">
                                                @endif

                                                <h4 class="fw-bold mb-1">Selamat Datang di</h4>
                                                <h2 class="fw-bold text-primary mb-1">{{ $sidebarSetting->title }}</h2>
                                                <h6 class="fw-light">{{ $sidebarSetting->subtitle }}</h6>
                                            </div>

                                            <div class="mb-3">
                                                <input type="tel" class="form-control" name="nomor" id="nomor"
                                                    placeholder="Masukkan Nomor Handphone" value="{{ old('nomor') }}"
                                                    required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100 py-2 fs-6">
                                                Lanjut <i class="bi bi-arrow-right-circle ms-1"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- SweetAlert Notifications -->
                        @if (session('success'))
                            <script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: "{{ session('success') }}",
                                    confirmButtonColor: '#28a745',
                                });
                            </script>
                        @endif

                        @if (session('error'))
                            <script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Login Gagal!',
                                    text: "{{ session('error') }}",
                                    confirmButtonColor: '#d33',
                                });
                            </script>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        function resetStep() {
            Swal.fire({
                title: 'Yakin ganti nomor?',
                text: "PIN yang Anda ketik akan dihapus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, ganti',
                confirmButtonColor: '#28a745',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('login.reset') }}";
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Handle input nomor hanya jika inputnya ada di halaman
            const nomorInput = document.getElementById('nomor');
            if (nomorInput) {
                // Isi nomor dari localStorage
                let savedNumber = localStorage.getItem('savedNumber');
                if (savedNumber) {
                    nomorInput.value = savedNumber;
                }

                // Simpan ke localStorage setiap perubahan
                nomorInput.addEventListener('input', function() {
                    localStorage.setItem('savedNumber', this.value);
                });
            }

            // Logika PIN
            let pin = '';

            window.appendPin = function(num) {
                if (pin.length < 6) {
                    pin += num;
                    updatePin();
                }
            }

            window.clearPin = function() {
                pin = pin.slice(0, -1);
                updatePin();
            }

            function updatePin() {
                const pinInput = document.getElementById('pin');
                const submitBtn = document.getElementById('submitBtn');

                if (!pinInput || !submitBtn) return; // jika tidak ada elemen, skip

                pinInput.value = pin;

                for (let i = 1; i <= 6; i++) {
                    const dot = document.getElementById('dot' + i);
                    if (dot) dot.classList.remove('active');
                }

                for (let i = 1; i <= pin.length; i++) {
                    const dot = document.getElementById('dot' + i);
                    if (dot) dot.classList.add('active');
                }

                submitBtn.disabled = pin.length !== 6;
            }
        });
    </script>

</body>

</html>

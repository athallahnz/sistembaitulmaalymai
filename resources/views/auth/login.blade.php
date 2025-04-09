<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login Baitul Maal Yayasan Masjid Al Iman">
    <meta name="author" content="AdminKit">

    <title>Login | Baitul Maal Yayasan Masjid Al Iman</title>

    <!-- Stylesheets -->
    <link rel="shortcut icon" href="{{ asset('img/icons/favicon.ico') }}" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: url("{{ asset('img/photos/img-background-login.webp') }}") no-repeat center center fixed;
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
            color: #622200;
        }

        .btn-outline-dark:hover {
            background-color: #622200;
            /* Warna coklat saat hover */
            color: white;
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
            background-color: #622200;
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
                            <div class="card-body">
                                <div class="m-sm-3">
                                    <form method="POST" action="{{ route('login') }}">
                                        @csrf

                                        <!-- Nomor -->
                                        <div class="mb-3">
                                            <input type="tel" class="form-control" name="nomor" id="nomor"
                                                placeholder="Masukkan Nomor Handphone" value="{{ old('nomor') }}"
                                                required>
                                        </div>

                                        <h6 class="mb-3">Masukkan PIN Anda</h6>

                                        <!-- PIN Dots -->
                                        <div class="pin-dots">
                                            <div class="dot" id="dot1"></div>
                                            <div class="dot" id="dot2"></div>
                                            <div class="dot" id="dot3"></div>
                                            <div class="dot" id="dot4"></div>
                                            <div class="dot" id="dot5"></div>
                                            <div class="dot" id="dot6"></div>
                                        </div>

                                        <!-- Input PIN (Hidden) -->
                                        <input type="password" class="form-control text-center" name="pin"
                                            id="pin" readonly hidden>

                                        <!-- Tombol Angka -->
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
                                                    <button type="submit" class="btn btn-primary btn-circle">
                                                        <i class="bi bi-arrow-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
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
    <script>
        // Simpan nomor ke localStorage setiap kali diubah
        document.getElementById('nomor').addEventListener('input', function() {
            localStorage.setItem('savedNumber', this.value);
        });

        // Ambil nomor dari localStorage saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            let savedNumber = localStorage.getItem('savedNumber');
            if (savedNumber) {
                document.getElementById('nomor').value = savedNumber;
            }
        });

        let pin = '';

        function appendPin(num) {
            if (pin.length < 6) {
                pin += num;
                document.getElementById('pin').value = pin;
                updateDots();
                checkSubmit();
            }
        }

        function clearPin() {
            pin = pin.slice(0, -1);
            document.getElementById('pin').value = pin;
            updateDots();
            checkSubmit();
        }

        function updateDots() {
            for (let i = 1; i <= 6; i++) {
                document.getElementById('dot' + i).classList.remove('active');
            }
            for (let i = 1; i <= pin.length; i++) {
                document.getElementById('dot' + i).classList.add('active');
            }
        }

        function checkSubmit() {
            document.getElementById('submitBtn').disabled = pin.length !== 6;
        }
    </script>

    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>

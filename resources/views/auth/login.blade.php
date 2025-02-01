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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
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

        .btn-outline-secondary:hover {
            background-color: #622200;
            /* Warna coklat saat hover */
            color: white;
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
                                                placeholder="Masukkan Nomor" required>
                                        </div>

                                        <!-- PIN -->
                                        <div class="mb-4">
                                            <input type="hidden" class="form-control" name="pin" id="pin"
                                                placeholder="Masukkan PIN" required readonly>
                                        </div>

                                        <!-- Tombol Angka -->
                                        <div class="text-center">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                @foreach ([['1', '2', '3'], ['4', '5', '6'], ['7', '8', '9']] as $row)
                                                    <div class="d-flex gap-2">
                                                        @foreach ($row as $num)
                                                            <button type="button"
                                                                class="btn btn-outline-secondary btn-circle"
                                                                onclick="appendPin('{{ $num }}')">{{ $num }}</button>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-danger btn-circle"
                                                        onclick="clearPin()">
                                                        <i class="bi bi-backspace"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-circle"
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
        function appendPin(digit) {
            const pinField = document.getElementById('pin');
            pinField.value += digit;
        }

        function clearPin() {
            document.getElementById('pin').value = '';
        }
    </script>

    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>

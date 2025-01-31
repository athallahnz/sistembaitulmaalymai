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
    <link rel="shortcut icon" href="{{ asset('img/icons/icon-48x48.png') }}" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .btn-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <main class="d-flex w-100">
        <div class="container d-flex flex-column">
            <div class="row vh-100">
                <div class="col-md-6 col-lg-5 mx-auto d-table h-100">
                    <div class="d-table-cell align-middle">

                        <div class="text-center mt-4">
                            <h1 class="h2">Selamat Datang!</h1>
                            <p class="lead">Silakan login untuk melanjutkan</p>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="m-sm-3">
                                    <form method="POST" action="{{ route('login') }}">
                                        @csrf

                                        <!-- Nomor -->
                                        <div class="mb-3">
                                            <label for="nomor" class="form-label">Nomor</label>
                                            <input type="tel" class="form-control" name="nomor" id="nomor"
                                                placeholder="Masukkan Nomor" required>
                                        </div>

                                        <!-- PIN -->
                                        <div class="mb-4">
                                            <label for="pin" class="form-label">PIN</label>
                                            <input type="password" class="form-control" name="pin" id="pin"
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
                                                    <button type="button" class="btn btn-outline-danger btn-circle"
                                                        onclick="clearPin()">⌫</button>
                                                    <button type="button" class="btn btn-outline-secondary btn-circle"
                                                        onclick="appendPin('0')">0</button>
                                                    <button type="submit" class="btn btn-primary btn-circle">➜</button>
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

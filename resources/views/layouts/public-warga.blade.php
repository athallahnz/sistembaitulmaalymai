<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Infaq Warga')</title>
    <link rel="shortcut icon" href="{{ asset('img/icons/favicon.ico') }}">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Brand stylesheet (punyamu) --}}
    <link rel="stylesheet" href="{{ asset('css/app-public.css') }}">

    <style>
        /* Sentuhan kecil agar default langsung selaras brand */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(1200px 600px at -10% -10%, #f4eae2 0%, #fff 40%, #fff 100%),
                radial-gradient(900px 420px at 110% 0%, #f7efe8 0%, #fff 45%, #fff 100%);
        }

        .navbar-brand {
            font-weight: 700;
        }

        .content-shell {
            padding-top: var(--section-y);
            padding-bottom: var(--section-y);
        }

        footer {
            margin-top: auto;
        }
    </style>
</head>

<body>

    {{-- Navbar (bungkus dengan kelas brand) --}}
    <div class="elegant-navbar shadow-sm">
        @include('layouts.public-warga-nav')
    </div>

    <main class="flex-grow-1 content-shell">
        @yield('content')
    </main>

    <footer class="footer bg-brown text-white py-3">
        <div class="container text-center small">
            © {{ date('Y') }} Bidang Sosial Yayasan Al Iman Sutorejo Indah
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

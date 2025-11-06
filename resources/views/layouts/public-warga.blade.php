<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Infaq Warga')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        footer {
            margin-top: auto;
            background: #fff;
            padding: 1rem 0;
            text-align: center;
            color: #666;
            border-top: 1px solid #eee;
        }

        .navbar-brand {
            font-weight: 600;
            color: #622200 !important;
        }
    </style>
</head>

<body>

    {{-- Navbar --}}
    @include('layouts.public-warga-nav')

    <main class="flex-grow-1 py-4">
        @yield('content')
    </main>

    <footer>
        <small>© {{ date('Y') }} Bidang Sosial Yayasan Al Iman Sutorejo Indah</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

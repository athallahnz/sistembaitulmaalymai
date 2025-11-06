<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Beranda') | Sistem Baitul Maal Yayasan Masjid Al Iman Surabaya</title>

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('img/icons/favicon.ico') }}">

    {{-- Google Font (Inter) --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 + Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- AOS (scroll animation) --}}
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

    {{-- DataTables (opsional) --}}
    <link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    {{-- Custom CSS publik --}}
    <link href="{{ asset('css/app-public.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body style="font-family: 'Inter', sans-serif;">

    {{-- Navbar publik --}}
    @include('layouts.public-nav')

    <main class="content">
        @yield('content')
    </main>

    {{-- Script --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js" defer></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            AOS.init();
        });
    </script>

    @stack('scripts')
</body>

</html>

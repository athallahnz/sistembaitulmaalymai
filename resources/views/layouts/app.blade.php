<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin & Dashboard Template based on Bootstrap 5">

    <title>@yield('title') | Sistem Baitul Maal Yayasan Masjid Al Iman Surabaya</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('img/icons/favicon.ico') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.1/lib/anime.min.js"></script>

    <!-- Custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinycolor/1.4.2/tinycolor.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

@php
    $sidebarSetting = \App\Models\SidebarSetting::first();
@endphp

<body>
    <div class="wrapper">
        <x-sidebar />
        <div class="main">
            <x-navbar-top />

            <main class="content">
                <div class="container-fluid p-4">
                    @yield('content')
                </div>
            </main>
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row text-muted">
                        <div class="col-12 col-md-6 text-start">
                            <p class="mb-0">
                                @if ($sidebarSetting?->footer_text)
                                    {!! $sidebarSetting->footer_text !!}
                                @else
                                    <strong>{{ $sidebarSetting->title ?? 'Sistem Baitul Maal' }}</strong> -
                                    <a class="text-muted"
                                        href="{{ $sidebarSetting->footer_link ?? 'https://alimansurabaya.com/' }}"
                                        target="_blank">
                                        <strong>{{ $sidebarSetting->subtitle ?? 'Yayasan Masjid Al Iman Surabaya' }}</strong>
                                    </a>
                                    &copy; {{ now()->year }}
                                @endif
                            </p>
                        </div>
                        <div class="col-12 col-md-6 text-end">
                            <ul class="list-inline mb-0">
                                @if (!empty($sidebarSetting?->footer_links))
                                    @foreach ($sidebarSetting->footer_links as $label => $url)
                                        <li class="list-inline-item">
                                            <a href="{{ $url }}" target="_blank">{{ $label }}</a>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="list-inline-item"><a href="#">Support</a></li>
                                    <li class="list-inline-item"><a href="#">Help Center</a></li>
                                    <li class="list-inline-item"><a href="#">Privacy</a></li>
                                    <li class="list-inline-item"><a href="#">Terms</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>

        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('script')
    @stack('scripts')
    @stack('styles')

    <!-- SweetAlert2 Notifications -->
    @if (session('login success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Login Berhasil!',
                text: "{{ session('login success') }}",
                confirmButtonColor: '#28a745',
            });
        </script>
    @endif

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
                title: 'Gagal!',
                text: "{{ session('error') }}",
                confirmButtonColor: '#dc3545',
            });
        </script>
    @endif
</body>

</html>

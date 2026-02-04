<nav class="navbar navbar-expand-lg bg-white elegant-navbar shadow-sm">
    <div class="container">
        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center gap-2 text-brown" href="{{ route('warga.dashboard') }}">
            <span class="d-flex flex-column lh-1">
                <strong class="mb-0" style="letter-spacing:.3px;">SINFABUL</strong>
                <small class="text-muted">Sistem Informasi Infaq Bulanan Al Iman</small>
            </span>
        </a>

        {{-- Toggler --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarWarga"
            aria-controls="navbarWarga" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Nav Right --}}
        <div class="collapse navbar-collapse" id="navbarWarga">
            <ul class="navbar-nav ms-auto align-items-lg-center nav-mini">
                @if (session()->has('warga_id'))
                    <li class="nav-item me-2">
                        <a href="{{ route('warga.dashboard') }}"
                            class="nav-link {{ request()->routeIs('warga.dashboard') ? 'active' : '' }}">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item me-2">
                        <a href="{{ route('warga.infaq') }}"
                            class="nav-link {{ request()->routeIs('warga.infaq') ? 'active' : '' }}">
                            Infaq
                        </a>
                    </li>

                    <li class="nav-item me-2">
                        <a href="{{ route('warga.iuran') }}"
                            class="nav-link {{ request()->routeIs('warga.iuran') ? 'active' : '' }}">
                            Iuran Sosial
                        </a>
                    </li>

                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <form action="{{ route('warga.logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-brown-pill">
                                Keluar
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a href="{{ route('warga.login.form') }}" class="btn btn-ms btn-brown">
                            Login
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

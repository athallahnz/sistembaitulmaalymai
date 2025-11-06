<nav
    class="navbar navbar-expand-md navbar-light bg-white shadow-sm elegant-navbar {{ request()->is('/') ? 'nav-on-hero' : 'sticky-top' }}">
    <div class="container">
        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            @if (!empty($sidebarSetting?->logo_path))
                <span class="brand-badge {{ request()->is('/') ? 'brand-hero' : '' }}">
                    <img src="{{ asset('storage/' . $sidebarSetting->logo_path) }}" alt="Logo" class="img-fluid">
                </span>
            @else
                <span class="fw-semibold text-brown">Masjid Al Iman</span>
            @endif
        </a>

        {{-- Toggler --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Menu --}}
        <div class="collapse navbar-collapse" id="navbarMain">
            {{-- Left --}}
            <ul class="navbar-nav me-auto">
                <li class="nav-item mx-3">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Home</a>
                </li>

                <li class="nav-item mx-3">
                    <a class="nav-link {{ request()->is('infaqku*') ? 'active' : '' }}"
                        href="{{ url('/infaqku') }}">Infaqku</a>
                </li>

                <li class="nav-item mx-3">
                    <a class="nav-link {{ request()->is('kajians*') ? 'active' : '' }}"
                        href="{{ url('/kajians') }}">Kajian</a>
                </li>

                <li class="nav-item mx-3">
                    <a class="nav-link {{ request()->is('kegiatan*') ? 'active' : '' }}"
                        href="{{ url('/kegiatan') }}">Kegiatan</a>
                </li>

                <li class="nav-item mx-3">
                    <a class="nav-link {{ request()->is('konsultasi') ? 'active' : '' }}"
                        href="{{ url('/konsultasi') }}">Konsultasi</a>
                </li>

                {{-- Dropdown Profil --}}
                <li class="nav-item mx-3 dropdown hover-dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->is('profil*') ? 'active' : '' }}" href="#"
                        id="dropdownProfil" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Profil
                    </a>
                    <ul class="dropdown-menu border-0 shadow" aria-labelledby="dropdownProfil">
                        <li>
                            <a class="dropdown-item" href="{{ url('/profil/sejarah') }}">
                                <i class="bi bi-clock-history me-2"></i> Sejarah
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                                href="https://drive.google.com/file/d/1mlKAQmGScW1LiuNoznt4NKvKXQBRfe-a/view?usp=sharing"
                                target="_blank" rel="noopener">
                                <i class="bi bi-diagram-3 me-2"></i> Struktur Organisasi
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            {{-- Right --}}
            <ul class="navbar-nav ms-auto">
                @guest
                    @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="btn btn-sm text-white px-4 me-2 btn-brown" href="{{ route('login') }}">Login</a>
                        </li>
                    @endif
                    @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="btn btn-sm text-white px-4 btn-brown" href="{{ route('register') }}">Register</a>
                        </li>
                    @endif
                @else
                    <li class="nav-item dropdown hover-dropdown">
                        <a id="dropdownUser" class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {{ Str::limit(auth()->user()->name, 22) }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow" aria-labelledby="dropdownUser">
                            <li><a class="dropdown-item" href="{{ url('/dashboard') }}"><i
                                        class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

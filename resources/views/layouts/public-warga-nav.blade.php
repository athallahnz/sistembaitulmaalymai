<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex flex-column" href="{{ route('warga.dashboard') }}">
            <div class="d-flex align-items-center">
                <i class="bi bi-heart-fill text-danger"></i>
                <strong class="mb-0">SINFABUL</strong>
            </div>
            <small class="text-muted">Sistem Informasi Infaq Bulanan Warga</small>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarWarga"
            aria-controls="navbarWarga" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarWarga">
            <ul class="navbar-nav ms-auto">
                @if(session()->has('warga_id'))
                    <li class="nav-item">
                        <form action="{{ route('warga.logout') }}" method="POST">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm">Keluar</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ route('warga.login.form') }}" class="btn btn-outline-primary btn-sm">Login</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

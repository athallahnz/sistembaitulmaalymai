<nav id="sidebar" class="sidebar js-sidebar">
<div class="sidebar-content js-simplebar">
    <a class="sidebar-brand" href="index.html">
        <span class="align-middle">Sistem Baitul Maal</span>
        <p> Yayasan Masjid Al Iman Surabaya</p>
    </a>

    <ul class="sidebar-nav">
        <li class="sidebar-header">Manajemen</li>

        @role('Admin')
        <li class="sidebar-item {{ request()->routeIs('admin.index') ? 'active' : '' }}">
            <a class="sidebar-link" href="{{ route('admin.index') }}">
                <i class="align-middle" data-feather="sliders"></i>
                <span class="align-middle">Dashboard Admin</span>
            </a>
        </li>

        <li class="sidebar-item {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
            <a class="sidebar-link" href="{{ route('admin.users.index') }}">
                <i class="align-middle" data-feather="user"></i>
                <span class="align-middle">Users</span>
            </a>
        </li>
        @endrole

        @role('Ketua Yayasan')
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('ketua.index') }}">
                <i class="align-middle" data-feather="sliders"></i>
                <span class="align-middle">Dashboard Ketua</span>
            </a>
        </li>
        @endrole

        @role('Manajer Keuangan')
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('manajer.index') }}">
                <i class="align-middle" data-feather="sliders"></i>
                <span class="align-middle">Dashboard Manajer</span>
            </a>
        </li>
        @endrole

        @role('Bendahara')
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('bendahara.index') }}">
                <i class="align-middle" data-feather="sliders"></i>
                <span class="align-middle">Dashboard Bendahara</span>
            </a>
        </li>
        @endrole

        @role('Bidang')
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('bidang.index') }}">
                <i class="align-middle" data-feather="sliders"></i>
                <span class="align-middle">Dashboard Bidang</span>
            </a>
        </li>
        @endrole
    </ul>


    <div class="sidebar-cta">
        {{-- <div class="sidebar-cta-content">
            <strong class="d-inline-block mb-2">Upgrade to Pro</strong>
            <div class="mb-3 text-sm">
                Are you looking for more components? Check out our premium
                version.
            </div>
            <div class="d-grid">
                <a href="upgrade-to-pro.html" class="btn btn-primary">Upgrade to Pro</a>
            </div>
        </div> --}}
    </div>
</div>
</nav>

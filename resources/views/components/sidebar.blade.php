<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <div class="sidebar-brand">
            <span class="align-middle">Sistem Baitul Maal</span>
            <p> Yayasan Masjid Al Iman Surabaya</p>
        </div>

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
                <li class="sidebar-item {{ request()->routeIs('ketua.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('ketua.index') }}">
                        <i class="align-middle" data-feather="sliders"></i>
                        <span class="align-middle">Dashboard Ketua</span>
                    </a>
                </li>
            @endrole

            @role('Manajer Keuangan')
                <li class="sidebar-item {{ request()->routeIs('manajer.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('manajer.index') }}">
                        <i class="align-middle" data-feather="sliders"></i>
                        <span class="align-middle">Dashboard Manajer</span>
                    </a>
                </li>
            @endrole

            @role('Bendahara')
                <li class="sidebar-item {{ request()->routeIs('bendahara.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('bendahara.index') }}">
                        <i class="align-middle" data-feather="sliders"></i>
                        <span class="align-middle">Dashboard Bendahara</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('transaksi.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('transaksi.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Buku Harian</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('ledger.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('ledger.index') }}">
                        <i class="align-middle" data-feather="book"></i>
                        <span class="align-middle">Buku Besar Kas</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('laporan.bank') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('laporan.bank') }}">
                        <i class="align-middle" data-feather="book"></i>
                        <span class="align-middle">Buku Besar Bank</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('piutangs.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('piutangs.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Piutang</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('hutangs.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('hutangs.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Hutang</span>
                    </a>
                </li>
                <li class="sidebar-header">Pelaporan</li>
                <li class="sidebar-item">
                    <a href="#laporanKeuangan" data-bs-toggle="collapse" class="sidebar-link">
                        <i class="align-middle" data-feather="clipboard"></i>
                        <span class="align-middle">Keuangan Yayasan</span>
                    </a>
                    <ul id="laporanKeuangan"
                        class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('laporan.arus-kas', 'laporan.posisi-keuangan', 'laporan.laba-rugi', 'laporan.neraca-saldo') ? 'show' : '' }}">
                        <li class="sidebar-item {{ request()->routeIs('laporan.arus-kas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.arus-kas') }}">
                                <i class="align-middle ms-3" data-feather="trending-up"></i>
                                <span class="align-middle">Arus kas</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.posisi-keuangan') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.posisi-keuangan') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart"></i>
                                <span class="align-middle">Posisi Keuangan</span>
                            </a>
                        </li>
                        <li
                            class="sidebar-item {{ request()->routeIs('laporan.neraca-saldo-bendahara') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.neraca-saldo-bendahara') }}">
                                <i class="align-middle ms-3" data-feather="file-text"></i>
                                <span class="align-middle">Neraca Saldo</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endrole

            @role('Bidang')
                <li class="sidebar-item {{ request()->routeIs('bidang.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('bidang.index') }}">
                        <i class="align-middle" data-feather="sliders"></i>
                        <span class="align-middle">Dashboard Bidang</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('transaksi.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('transaksi.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Buku Harian</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('ledger.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('ledger.index') }}">
                        <i class="align-middle" data-feather="book"></i>
                        <span class="align-middle">Buku Besar Kas</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('laporan.bank') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('laporan.bank') }}">
                        <i class="align-middle" data-feather="book"></i>
                        <span class="align-middle">Buku Besar Bank</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('piutangs.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('piutangs.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Piutang</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('hutangs.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('hutangs.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Hutang</span>
                    </a>
                </li>
                @if (auth()->user()->bidang_name === 'Pendidikan')
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="#">
                            <i class="align-middle" data-feather="dollar-sign"></i>
                            <span class="align-middle">Pembayaran SPP</span>
                        </a>
                    </li>
                @endif
                <li class="sidebar-header">Pelaporan</li>
                <li class="sidebar-item">
                    <a href="#laporanKeuangan" data-bs-toggle="collapse" class="sidebar-link">
                        <i class="align-middle" data-feather="clipboard"></i>
                        <span class="align-middle">Keuangan Bidang</span>
                    </a>
                    <ul id="laporanKeuangan"
                        class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('laporan.arus-kas', 'laporan.posisi-keuangan', 'laporan.laba-rugi', 'laporan.neraca-saldo') ? 'show' : '' }}">
                        <li class="sidebar-item {{ request()->routeIs('laporan.arus-kas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.arus-kas') }}">
                                <i class="align-middle ms-3" data-feather="trending-up"></i>
                                <span class="align-middle">Arus kas</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.posisi-keuangan') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.posisi-keuangan') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart"></i>
                                <span class="align-middle">Posisi Keuangan</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.neraca-saldo') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.neraca-saldo') }}">
                                <i class="align-middle ms-3" data-feather="file-text"></i>
                                <span class="align-middle">Neraca Saldo</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endrole
        </ul>


        <div class="sidebar-cta">
            <div class="sidebar-cta-content">
                <strong class="d-inline-block mb-2">Sudah bersyukur hari ini?</strong>
                <div class="mb-3 text-sm">
                    Are you looking for more happiness? Check out your heart now!
                </div>
                <div class="d-grid">
                    <a class="btn"
                        style="background-color: #81431E; color: #fff5e1; padding: 0.5rem 1rem; border-radius: 0.25rem; text-decoration: none; display: inline-block;"
                        onmouseover="this.style.backgroundColor='#984F23';"
                        onmouseout="this.style.backgroundColor='#81431E';">
                        Bersyukurlah!
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

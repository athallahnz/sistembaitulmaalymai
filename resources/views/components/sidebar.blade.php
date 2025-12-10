@php
    $setting = \App\Models\SidebarSetting::first();
@endphp

@push('styles')
    @if ($setting)
        <style>
            .sidebar,
            .sidebar-content {
                background-color: {{ $setting->background_color ?? '#7A3E16' }};
            }

            .sidebar {
                height: 100vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .sidebar-cta {
                flex-shrink: 0;
            }

            .sidebar-content {
                flex: 1 1 auto;
                overflow-y: auto;
                padding-bottom: 6rem;
            }

            .sidebar-link,
            .sidebar-link i,
            .sidebar-link svg {
                color: {{ $setting->link_color ?? 'rgba(233, 236, 239, 0.5)' }};
            }

            .sidebar-link:hover,
            .sidebar-link:hover i,
            .sidebar-link:hover svg {
                color: {{ $setting->link_hover_color ?? 'rgba(233, 236, 239, 0.75)' }};
            }

            .sidebar-item.active>.sidebar-link {
                background-image: linear-gradient(to right, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0)) !important;
                color: {{ $setting->link_active_color ?? '#e9ecef' }};
                border-left-color: {{ $setting->link_active_border_color ?? '#f2c89d' }};
            }

            .sidebar-item.active>.sidebar-link i,
            .sidebar-item.active>.sidebar-link svg {
                color: {{ $setting->link_active_color ?? '#e9ecef' }};
            }

            .sidebar-cta-content-box {
                background-color: {{ $setting->cta_background_color ?? '#8D4720' }};
            }

            .sidebar-cta-content-box .btn {
                background-color: {{ $setting->cta_button_color ?? '#81431E' }};
                color: {{ $setting->cta_button_text_color ?? '#fff5e1' }};
            }

            .sidebar-cta-content-box .btn:hover {
                background-color: {{ $setting->cta_button_hover_color ?? '#984F23' }};
            }

            .floating-heart {
                position: absolute;
                font-size: 1.5rem;
                pointer-events: none;
            }

            .sidebar-cta {
                position: relative;
                overflow: hidden;
            }
        </style>
    @endif
@endpush

<nav id="sidebar" class="sidebar js-sidebar d-flex flex-column" style="height: 100vh;">
    <div class="sidebar-content js-sidebar flex-grow-1 overflow-auto d-flex flex-column" data-simplebar>
        @php
            $sidebarSetting = \App\Models\SidebarSetting::first();
        @endphp

        <div class="sidebar-brand text-center py-3">
            @if ($sidebarSetting?->logo_path)
                <img src="{{ url('storage/' . $sidebarSetting->logo_path) }}" alt="Logo" class="img-fluid mb-2"
                    style="height: 75px;">
            @endif
            <div>
                <span>{{ $sidebarSetting->title }}</span>
                <p>{{ $sidebarSetting->subtitle }}</p>
            </div>
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
                        <i class="align-middle" data-feather="users"></i>
                        <span class="align-middle">Users</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('admin.akun_keuangan.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('admin.akun_keuangan.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Akun Keuangan</span>
                    </a>
                </li>
                <li class="sidebar-item {{ request()->routeIs('admin.add_bidangs.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('admin.add_bidangs.index') }}">
                        <i class="align-middle" data-feather="briefcase"></i>
                        <span class="align-middle">Tambah Bidang</span>
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
                {{-- Dashboard Bendahara --}}
                {{-- <li class="sidebar-item {{ request()->routeIs('bendahara.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('bendahara.index') }}">
                        <i class="align-middle" data-feather="sliders"></i>
                        <span class="align-middle">Dashboard Bendahara</span>
                    </a>
                </li> --}}
                {{-- MENU BARU: APPROVAL DANA --}}
                {{-- <li class="sidebar-header">Manajemen Anggaran & Approval Dana</li>
                <li class="sidebar-item {{ request()->routeIs('pengajuan.*') ? 'active' : '' }}" id="menu-approval-dana">
                    <a class="sidebar-link" href="{{ route('pengajuan.index') }}">
                        <i class="align-middle" data-feather="check-square"></i>
                        <span class="align-middle">Approval Dana</span>
                        <span class="sidebar-badge badge bg-danger" id="approval-badge" style="display:none;">0</span>
                    </a>
                </li> --}}
                <li class="sidebar-header">Pelaporan</li>
                <li class="sidebar-item">
                    <a href="#laporanKeuangan" data-bs-toggle="collapse" class="sidebar-link">
                        <i class="align-middle" data-feather="clipboard"></i>
                        <span class="align-middle">Keuangan Yayasan</span>
                    </a>
                    <ul id="laporanKeuangan"
                        class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('laporan.arus-kas', 'laporan.posisi-keuangan', 'laporan.laba-rugi', 'laporan.neraca-saldo-bendahara', 'laporan.aktivitas') ? 'show' : '' }}">
                        <li class="sidebar-item {{ request()->routeIs('laporan.posisi-keuangan') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.posisi-keuangan') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart"></i>
                                <span class="align-middle">Posisi Keuangan</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.arus-kas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.arus-kas') }}">
                                <i class="align-middle ms-3" data-feather="trending-up"></i>
                                <span class="align-middle">Arus kas</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.aktivitas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.aktivitas') }}">
                                <i class="align-middle ms-3" data-feather="file-text"></i>
                                <span class="align-middle">Aktivitas</span>
                            </a>
                        </li>
                        {{-- <li
                            class="sidebar-item {{ request()->routeIs('laporan.neraca-saldo-bendahara') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.neraca-saldo-bendahara') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart-2"></i>
                                <span class="align-middle">Neraca Saldo</span>
                            </a>
                        </li> --}}
                    </ul>
                </li>
            @endrole

            @role('Manajer Keuangan')
                <li class="sidebar-item {{ request()->routeIs('manajer.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('manajer.index') }}">
                        <i class="align-middle" data-feather="sliders"></i>
                        <span class="align-middle">Dashboard Manajer</span>
                    </a>
                </li>
                {{-- MENU BARU: APPROVAL DANA --}}
                <li class="sidebar-header">Manajemen Anggaran & Approval Dana</li>
                <li class="sidebar-item {{ request()->routeIs('pengajuan.*') ? 'active' : '' }}" id="menu-approval-dana">
                    <a class="sidebar-link" href="{{ route('pengajuan.index') }}">
                        <i class="align-middle" data-feather="check-square"></i>
                        <span class="align-middle">Approval Dana</span>
                        {{-- Badge Notifikasi --}}
                        <span class="sidebar-badge badge bg-danger" id="approval-badge" style="display:none;">0</span>
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
                        <i class="align-middle" data-feather="book"></i>
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
                <li class="sidebar-item">
                    <a href="#hutangpiutang" data-bs-toggle="collapse" class="sidebar-link">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Hutang Piutang</span>
                    </a>
                    <ul id="hutangpiutang"
                        class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('piutangs.index', 'piutangs.penerima') ? 'show' : '' }}">
                        <li class="sidebar-item {{ request()->routeIs('piutangs.index') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('piutangs.index') }}">
                                <i class="align-middle ms-3" data-feather="dollar-sign"></i>
                                <span class="align-middle">Piutang</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('piutangs.penerima') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('piutangs.penerima') }}">
                                <i class="align-middle ms-3" data-feather="dollar-sign"></i>
                                <span class="align-middle">Hutang</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-item {{ request()->routeIs('hutangs.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('hutangs.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Hutang Perantara</span>
                    </a>
                </li>
                <li class="sidebar-header">Manajemen Anggaran & Pencairan Dana</li>
                <li class="sidebar-item {{ request()->routeIs('pengajuan.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('pengajuan.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle"> Pencairan Dana</span>
                        {{-- Badge Notifikasi --}}
                        <span class="sidebar-badge badge bg-danger" id="approval-badge" style="display:none;">0</span>
                    </a>
                </li>
                <li class="sidebar-header">Pelaporan</li>
                <li class="sidebar-item">
                    <a href="#laporanKeuangan" data-bs-toggle="collapse" class="sidebar-link">
                        <i class="align-middle" data-feather="clipboard"></i>
                        <span class="align-middle">Keuangan Yayasan</span>
                    </a>
                    <ul id="laporanKeuangan"
                        class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('laporan.arus-kas', 'laporan.posisi-keuangan', 'laporan.laba-rugi', 'laporan.neraca-saldo-bendahara', 'laporan.aktivitas') ? 'show' : '' }}">
                        <li class="sidebar-item {{ request()->routeIs('laporan.posisi-keuangan') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.posisi-keuangan') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart"></i>
                                <span class="align-middle">Posisi Keuangan</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.arus-kas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.arus-kas') }}">
                                <i class="align-middle ms-3" data-feather="trending-up"></i>
                                <span class="align-middle">Arus kas</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.aktivitas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.aktivitas') }}">
                                <i class="align-middle ms-3" data-feather="file-text"></i>
                                <span class="align-middle">Aktivitas</span>
                            </a>
                        </li>
                        {{-- <li
                            class="sidebar-item {{ request()->routeIs('laporan.neraca-saldo-bendahara') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.neraca-saldo-bendahara') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart-2"></i>
                                <span class="align-middle">Neraca Saldo</span>
                            </a>
                        </li> --}}
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
                <li class="sidebar-item {{ request()->routeIs('transaksi.index.bidang') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('transaksi.index.bidang') }}">
                        <i class="align-middle" data-feather="book"></i>
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
                <li class="sidebar-item">
                    <a href="#hutangpiutang" data-bs-toggle="collapse" class="sidebar-link">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Hutang Piutang</span>
                    </a>
                    <ul id="hutangpiutang"
                        class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('piutangs.index', 'piutangs.penerima') ? 'show' : '' }}">
                        <li class="sidebar-item {{ request()->routeIs('piutangs.index') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('piutangs.index') }}">
                                <i class="align-middle ms-3" data-feather="dollar-sign"></i>
                                <span class="align-middle">Piutang</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('piutangs.penerima') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('piutangs.penerima') }}">
                                <i class="align-middle ms-3" data-feather="dollar-sign"></i>
                                <span class="align-middle">Hutang</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-item {{ request()->routeIs('hutangs.index') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('hutangs.index') }}">
                        <i class="align-middle" data-feather="dollar-sign"></i>
                        <span class="align-middle">Hutang Perantara</span>
                    </a>
                </li>
                <li class="sidebar-header">Manajemen Anggaran & Pencairan Dana</li>
                <li class="sidebar-item {{ request()->routeIs('pengajuan.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('pengajuan.index') }}">
                        <i class="align-middle" data-feather="file-plus"></i>
                        <span class="align-middle"> Pengajuan Dana</span>
                        {{-- Opsional: Badge notifikasi jika ada yang perlu diapprove --}}
                        {{-- <span class="sidebar-badge badge bg-danger">!</span> --}}
                    </a>
                </li>
                <li class="sidebar-header">Pelaporan</li>
                <li class="sidebar-item">
                    <a href="#laporanKeuangan" data-bs-toggle="collapse" class="sidebar-link">
                        <i class="align-middle" data-feather="clipboard"></i>
                        <span class="align-middle">Keuangan Bidang</span>
                    </a>
                    <ul id="laporanKeuangan"
                        class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('laporan.arus-kas', 'laporan.posisi-keuangan', 'laporan.laba-rugi', 'laporan.neraca-saldo', 'laporan.aktivitas') ? 'show' : '' }}">
                        <li class="sidebar-item {{ request()->routeIs('laporan.posisi-keuangan') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.posisi-keuangan') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart"></i>
                                <span class="align-middle">Posisi Keuangan</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.arus-kas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.arus-kas') }}">
                                <i class="align-middle ms-3" data-feather="trending-up"></i>
                                <span class="align-middle">Arus kas</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('laporan.aktivitas') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.aktivitas') }}">
                                <i class="align-middle ms-3" data-feather="file-text"></i>
                                <span class="align-middle">Aktivitas</span>
                            </a>
                        </li>
                        {{-- <li class="sidebar-item {{ request()->routeIs('laporan.neraca-saldo') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('laporan.neraca-saldo') }}">
                                <i class="align-middle ms-3" data-feather="bar-chart-2"></i>
                                <span class="align-middle">Neraca Saldo</span>
                            </a>
                        </li> --}}
                    </ul>
                </li>
                @if (auth()->user()->bidang && auth()->user()->bidang->name === 'Pendidikan')
                    <li class="sidebar-header">Manajemen Murid</li>
                    <li class="sidebar-item {{ request()->routeIs('students.index') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('students.index') }}">
                            <i class="align-middle" data-feather="users"></i>
                            <span class="align-middle">Data Murid</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('edu_classes.index') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('edu_classes.index') }}">
                            <i class="align-middle" data-feather="star"></i>
                            <span class="align-middle">Data Kelas</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="#dataPembayaran" data-bs-toggle="collapse" class="sidebar-link">
                            <i class="align-middle" data-feather="clipboard"></i>
                            <span class="align-middle">Data Pembayaran Murid</span>
                        </a>
                        <ul id="dataPembayaran"
                            class="sidebar-dropdown list-unstyled collapse {{ request()->routeIs('payment.dashboard', 'tagihan-spp.dashboard') ? 'show' : '' }}">
                            <li class="sidebar-item {{ request()->routeIs('payment.dashboard') ? 'active' : '' }}">
                                <a class="sidebar-link" href="{{ route('payment.dashboard') }}">
                                    <i class="align-middle ms-3" data-feather="trending-up"></i>
                                    <span class="align-middle">Pembayaran PMB</span>
                                </a>
                            </li>
                            <li class="sidebar-item {{ request()->routeIs('tagihan-spp.dashboard') ? 'active' : '' }}">
                                <a class="sidebar-link" href="{{ route('tagihan-spp.dashboard') }}">
                                    <i class="align-middle ms-3" data-feather="bar-chart-2"></i>
                                    <span class="align-middle">Pembayaran SPP</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('tagihan-spp.create') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('tagihan-spp.create') }}">
                            <i class="align-middle" data-feather="file-plus"></i>
                            <span class="align-middle">Buat Tagihan Murid</span>
                        </a>
                    </li>
                @elseif (auth()->user()->bidang && auth()->user()->bidang->name === 'Kemasjidan')
                    <li class="sidebar-header">Database Warga & Jamaah</li>
                    <li class="sidebar-item {{ request()->routeIs('kemasjidan.warga.index') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('kemasjidan.warga.index') }}">
                            <i class="align-middle" data-feather="users"></i>
                            <span class="align-middle">Data Warga & Jamaah</span>
                        </a>
                    </li>
                    <li class="sidebar-header">Manajemen Infaq Jamaah</li>
                    <li class="sidebar-item {{ request()->routeIs('kemasjidan.infaq.index') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('kemasjidan.infaq.index') }}">
                            <i class="align-middle" data-feather="archive"></i>
                            <span class="align-middle">Data Infaq Bulanan</span>
                        </a>
                    </li>
                @elseif (auth()->user()->bidang && auth()->user()->bidang->name === 'Sosial')
                    <li class="sidebar-header">Manajemen Infaq Bulanan</li>
                    <li class="sidebar-item {{ request()->routeIs('sosial.iuran.index') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('sosial.iuran.index') }}">
                            <i class="align-middle" data-feather="archive"></i>
                            <span class="align-middle">Data Infaq Sinoman</span>
                        </a>
                    </li>
                @endif
            @endrole
        </ul>


        <div class="sidebar-cta mt-3">
            <div class="sidebar-cta-content-box">
                <strong class="d-inline-block mb-2">Sudah bersyukur hari ini?</strong>
                <div class="mb-3 text-sm">
                    Are you looking for more happiness? Check out your heart now!
                </div>
                <div class="d-grid">
                    <a class="btn"
                        style="padding: 0.5rem 1rem; border-radius: 0.25rem; text-decoration: none; display: inline-block;">
                        Bersyukurlah!
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/animejs@3.2.1/lib/anime.min.js"></script>

    <script>
        const btn = document.querySelector('.sidebar-cta .btn');
        const box = document.querySelector('.sidebar-cta-content-box');

        btn.addEventListener('click', () => {
            for (let i = 0; i < 6; i++) {
                spawnHeart();
            }
        });

        function spawnHeart() {
            const heart = document.createElement('div');
            heart.classList.add('floating-heart');
            heart.innerHTML = '🤲🏻';

            // Posisi acak horizontal dalam box
            const x = Math.random() * box.clientWidth;
            const y = box.clientHeight - 20;

            heart.style.left = `${x}px`;
            heart.style.top = `${y}px`;

            box.appendChild(heart);

            // Animasi dengan anime.js
            anime({
                targets: heart,
                translateY: -80 - Math.random() * 40, // naik ke atas
                scale: [1, 1.8],
                opacity: [1, 0],
                duration: 1200 + Math.random() * 300,
                easing: 'easeOutQuad',
                complete: () => heart.remove()
            });
        }
    </script>
@endpush

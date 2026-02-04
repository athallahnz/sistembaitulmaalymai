<nav class="navbar navbar-expand navbar-light navbar-bg">
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>
    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align">
            <li class="nav-item dropdown">
                <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
                    <div class="position-relative">
                        <i class="align-middle" data-feather="bell"></i>

                        @php $unreadCount = auth()->user()?->unreadNotifications->count() ?? 0; @endphp
                        @if ($unreadCount > 0)
                            <span class="indicator bg-danger text-white rounded-pill">
                                {{ $unreadCount }}
                            </span>
                        @endif
                    </div>
                </a>

                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
                    <div class="dropdown-menu-header bg-primary text-white">
                        {{ $unreadCount }} Notifikasi Baru
                    </div>

                    <div class="list-group">
                        @forelse (auth()->user()->unreadNotifications as $notification)
                            {{-- Form POST untuk mark 1 notifikasi --}}
                            <form id="notif-read-{{ $notification->id }}"
                                action="{{ route('notifications.readOne', $notification->id) }}" method="POST"
                                class="d-none">
                                @csrf
                            </form>

                            <a href="#" class="list-group-item list-group-item-action"
                                onclick="event.preventDefault(); document.getElementById('notif-read-{{ $notification->id }}').submit();">
                                <div class="row g-0 align-items-center">
                                    <div class="col-2">
                                        <i class="text-warning" data-feather="alert-circle"></i>
                                    </div>
                                    <div class="col-10">
                                        <div class="text-dark fw-bold">
                                            {{ $notification->data['message'] ?? 'Pesan tidak tersedia' }}
                                        </div>
                                        <div class="text-muted small mt-1">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-3 text-center text-muted small">
                                Tidak ada notifikasi baru.
                            </div>
                        @endforelse
                    </div>

                    <div class="dropdown-menu-footer text-center">
                        @if ($unreadCount > 0)
                            <form action="{{ route('notifications.readAll') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-link text-muted p-0">
                                    Tandai semua sebagai dibaca
                                </button>
                            </form>
                        @else
                            <span class="text-muted small">Semua notifikasi sudah dibaca</span>
                        @endif
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-icon dropdown-toggle" href="#" id="messagesDropdown" data-bs-toggle="dropdown">
                    <div class="position-relative">
                        <i class="align-middle" data-feather="message-square"></i>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="messagesDropdown">
                    <div class="dropdown-menu-header">4 New Messages</div>
                    <div class="list-group">
                        <a href="#" class="list-group-item">
                            <div class="row g-0 align-items-center">
                                <div class="col-2">
                                    <img src="{{ asset('img/avatars/avatar-5.jpg') }}"
                                        class="avatar img-fluid rounded-circle" alt="Vanessa Tucker" />
                                </div>
                                <div class="col-10 ps-2">
                                    <div class="text-dark">Vanessa Tucker</div>
                                    <div class="text-muted small mt-1">Nam pretium turpis et arcu. Duis arcu tortor.
                                    </div>
                                    <div class="text-muted small mt-1">15m ago</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="dropdown-menu-footer">
                        <a href="#" class="text-muted">Show all messages</a>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
                    <i class="align-middle" data-feather="settings"></i>
                </a>
                <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
                    <img src="{{ auth()->user()->foto ? url('storage/' . auth()->user()->foto) : asset('img/avatars/avatar.jpg') }}"
                        class="avatar rounded me-1" style="object-fit: cover;" alt="User Avatar" />
                    <span class="text-dark">{{ Auth::user()->name }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="align-middle me-1"
                            data-feather="user"></i>
                        Profile</a>
                    @role('Admin')
                        <a class="dropdown-item" href="{{ route('admin.index') }}"><i class="align-middle me-1"
                                data-feather="pie-chart"></i>
                            Analytics</a>
                    @endrole
                    @role('Ketua Yayasan')
                        <a class="dropdown-item" href="{{ route('ketua.index') }}"><i class="align-middle me-1"
                                data-feather="pie-chart"></i>
                            Analytics</a>
                    @endrole
                    @role('Manajer Keuangan')
                        <a class="dropdown-item" href="{{ route('manajer.index') }}"><i class="align-middle me-1"
                                data-feather="pie-chart"></i>
                            Analytics</a>
                    @endrole
                    @role('Bendahara')
                        <a class="dropdown-item" href="{{ route('bendahara.index') }}"><i class="align-middle me-1"
                                data-feather="pie-chart"></i>
                            Analytics</a>
                    @endrole
                    @role('Bidang')
                        <a class="dropdown-item" href="{{ route('bidang.index') }}"><i class="align-middle me-1"
                                data-feather="pie-chart"></i>
                            Analytics</a>
                    @endrole
                    <div class="dropdown-divider"></div>
                    @role('Admin')
                        <a class="dropdown-item" href="{{ route('admin.sidebar_setting.edit') }}">
                            <i class="align-middle me-1" data-feather="settings"></i> Settings
                        </a>
                    @endrole
                    <a class="dropdown-item" href="#"><i class="align-middle me-1"
                            data-feather="help-circle"></i>
                        Help Center</a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" onclick="logoutClear()" method="POST" id="logout-form">
                        @csrf
                        <button type="submit" class="dropdown-item" style="border: none; background: none;">
                            <i class="align-middle me-1" data-feather="log-out"></i> Log Out
                        </button>
                    </form>
                    <script>
                        function logoutClear() {
                            localStorage.removeItem('savedNumber'); // Hapus nomor sebelum logout
                        }
                    </script>
                </div>
            </li>
        </ul>
    </div>
</nav>

@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="container">
        <h1 class="mb-4">Selamat Datang, <strong>{{ auth()->user()->role }} Yayasan!</strong></h1>

        {{-- OPTIONAL: salam welcome --}}
        <div class="row mb-4" id="welcomeAlert">
            <div class="col-12">
                <div class="alert alert-success shadow-sm border-1">
                    <h5 class="alert-heading"><strong>Welcome Back, {{ Auth::user()->name }}!</strong></h5>
                    <p class="mb-0">
                        Ini adalah dashboard Admin, Anda dapat memonitor User, Akun Keuangan, dan Bidang dari sini.
                    </p>
                </div>
            </div>
        </div>

        <script>
            // Hide welcome alert after 5 seconds
            setTimeout(function() {
                document.getElementById('welcomeAlert').style.display = 'none';
            }, 5000);
        </script>

        {{-- ROW CARDS SUMMARY --}}
        <div class="row mb-3">
            {{-- Jumlah transaksi bulan ini --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="icon bi bi-credit-card"></div>
                    <h5>Total User</h5>
                    <div class="value">
                        {{ $totalUser }}
                    </div>
                    <div class="description">Seluruh user terdaftar di sistem</div>
                </div>
            </div>

            {{-- Jumlah akun keuangan --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="icon bi bi-wallet2"></div>
                    <h5>Total Akun Keuangan</h5>
                    <div class="value">
                        {{ $totalAkun }}
                    </div>
                    <div class="description">Seluruh akun keuangan yang ada</div>
                </div>
            </div>

            {{-- Jumlah bidang --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="icon bi bi-building"></div>
                    <h5>Total Bidang</h5>
                    <div class="value">
                        {{ $totalBidang }}
                    </div>
                    <div class="description">Seluruh bidang yang terdaftar</div>
                </div>
            </div>
        </div>

        {{-- ROW: CHARTS --}}
        <div class="row">
            {{-- Chart Akun Keuangan per Tipe --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="mb-4 text-center">Distribusi Akun Keuangan per Tipe</h5>
                        <div style="height: 280px;">
                            <canvas id="akunChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chart User per Role --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="mb-4 text-center">Distribusi User per Role</h5>
                        <div style="height: 280px;">
                            <canvas id="userChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW: ACTIVITY LOG --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">Aktivitas Terbaru User</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>URL</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($latestActivities as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $log->user->name ?? '-' }}</td>
                                            <td>{{ $log->action }}</td>
                                            <td class="text-truncate" style="max-width: 250px;">
                                                <small>{{ $log->url }}</small>
                                            </td>
                                            <td><small>{{ $log->ip_address }}</small></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada aktivitas tercatat.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Data untuk chart Akun Keuangan per tipe
        const akunLabels = @json($akunByTipe->pluck('tipe_akun'));
        const akunData = @json($akunByTipe->pluck('total'));

        const ctxAkun = document.getElementById('akunChart').getContext('2d');
        new Chart(ctxAkun, {
            type: 'bar',
            data: {
                labels: akunLabels,
                datasets: [{
                    label: 'Jumlah Akun',
                    data: akunData,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Data untuk chart User per role
        const userLabels = @json($userByRole->pluck('role'));
        const userData = @json($userByRole->pluck('total'));

        const ctxUser = document.getElementById('userChart').getContext('2d');
        new Chart(ctxUser, {
            type: 'doughnut',
            data: {
                labels: userLabels,
                datasets: [{
                    label: 'Jumlah User',
                    data: userData,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
@endpush

@extends('layouts.public')

@php
    $sidebarSetting = \App\Models\SidebarSetting::first();

    $jadwal = $jadwalDefault['data']['jadwal'][0] ?? null;
    $timesLeft = ['Imsak' => 'imsak', 'Shubuh' => 'subuh', 'Terbit' => 'terbit', 'Dhuha' => 'dhuha'];
    $timesRight = ['Dzuhur' => 'dzuhur', 'Ashar' => 'ashar', 'Maghrib' => 'maghrib', "Isya'" => 'isya'];
@endphp

@section('content')
    {{-- ================= CAROUSEL ================= --}}
    <div id="carouselExampleSlidesOnly" class="carousel slide" data-bs-ride="carousel" data-bs-pause="hover">
        <div class="carousel-inner">
            @foreach ($slideshows as $slide)
                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                    <img src="{{ asset('storage/' . $slide->image) }}" alt="{{ $slide->title }}" loading="lazy"
                        class="d-block w-100 rounded-3">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>{{ $slide->title }}</h5>
                        <p>{{ $slide->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= KAJIAN TERDEKAT ================= --}}
    @if ($nextKajian)
        <div class="container my-5">
            <div class="row justify-content-between align-items-center bg-brown-gradient rounded-3 shadow p-4 glass lift">
                <div class="col-md-5 text-md-start text-center">
                    <h2 class="text-white mb-3">Kajian Terdekat:</h2>
                    <h1 class="fw-bold text-white mb-3">{{ $nextKajian->jeniskajian->name }} {{ $nextKajian->title }}</h1>
                    <h3 class="text-white mb-3">Oleh {{ $nextKajian->ustadz->name }}</h3>
                </div>
                <div class="col-md-5 text-md-end text-center">
                    <h3 class="text-white mb-4">InsyaAllah akan dimulai dalam:</h3>
                    <div id="timer" class="fw-bold text-white fs-1"></div>
                </div>
            </div>
        </div>

        <script>
            const countDownDate = new Date("{{ $nextKajian->start_time }}").getTime();
            const timer = document.getElementById("timer");
            const interval = setInterval(() => {
                const now = new Date().getTime();
                const distance = countDownDate - now;
                if (distance < 0) {
                    clearInterval(interval);
                    timer.textContent = "Kajian telah dimulai";
                    return;
                }
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                timer.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            }, 1000);
        </script>
    @else
        <div class="text-center py-4 text-white shadow-sm" style="background-color:#854321;">
            <h2 class="section-heading mb-2 animate-fadein text-white">Tidak ada Kajian untuk saat ini.</h2>
            <div class="divider mx-auto"></div>
        </div>
    @endif

    {{-- ======= Wave separator sebelum Jadwal Sholat ======= --}}
    <div class="wave">
        <svg viewBox="0 0 1440 120" width="100%" height="120" preserveAspectRatio="none">
            <path d="M0,64 C240,128 480,0 720,48 C960,96 1200,96 1440,48 L1440,120 L0,120 Z" fill="#622200"></path>
            {{-- gelap --}}
        </svg>
    </div>

    <section id="jadwalsholat" class="bg-brown text-white" role="region" aria-labelledby="jadwal-heading">
        <div class="container py-5">
            <div class="row align-items-start justify-content-between gy-4">

                {{-- Kiri: Heading + Lokasi + Pencarian --}}
                <div class="col-12 col-md-4">
                    <header class="mb-4 text-center text-md-start">
                        <h3 id="jadwal-heading" class="section-heading fw-bold mb-2 text-white d-inline-block">
                            Jadwal Shalat
                        </h3>
                        <div class="divider mx-auto mx-md-0"></div>

                        <p class="fw-bold mt-2 mb-1">
                            <i class="bi bi-geo-fill"></i>
                            {{ ucfirst($selectedCity ?? 'Surabaya') }},
                            {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}
                        </p>

                        <small class="text-white-50 d-block">
                            Untuk memperbarui jadwal setempat, silakan cari nama kota Anda.
                        </small>

                        <form action="{{ route('landing') }}" method="GET" aria-label="Pencarian kota"
                            class="mt-4 text-center text-md-start">
                            <div class="input-group glass-input mx-auto mx-md-0">
                                <input type="text" name="city" class="form-control text-white"
                                    placeholder="Cari kota..." value="{{ request('city', $selectedCity ?? 'Surabaya') }}"
                                    list="city-list" aria-label="Ketik nama kota">
                                <button type="submit" class="btn btn-light fw-semibold" style="color: white"
                                    aria-label="Cari">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>

                            <datalist id="city-list">
                                <option value="Surabaya">
                                <option value="Jakarta">
                                <option value="Bandung">
                                <option value="Semarang">
                                <option value="Yogyakarta">
                                <option value="Malang">
                                <option value="Denpasar">
                                <option value="Makassar">
                                <option value="Palembang">
                                <option value="Medan">
                            </datalist>
                        </form>
                    </header>
                </div>

                {{-- Tengah: Imsak - Dhuha --}}
                <div class="col-12 col-md-4">
                    <article class="p-3 rounded-3 glass lift" aria-labelledby="col1-heading">
                        <h4 id="col1-heading" class="visually-hidden">Imsak hingga Dhuha</h4>
                        @if ($jadwal)
                            <dl class="pray-times m-0">
                                @foreach ($timesLeft as $label => $key)
                                    <div class="pray-item">
                                        <dt>{{ $label }}</dt>
                                        <dd><span class="badge">{{ $jadwal[$key] ?? '-' }}</span></dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </article>
                </div>

                {{-- Kanan: Dzuhur - Isya --}}
                <div class="col-12 col-md-4">
                    <article class="p-3 rounded-3 glass lift" aria-labelledby="col2-heading">
                        <h4 id="col2-heading" class="visually-hidden">Dzuhur hingga Isya</h4>
                        @if ($jadwal)
                            <dl class="pray-times m-0">
                                @foreach ($timesRight as $label => $key)
                                    <div class="pray-item">
                                        <dt>{{ $label }}</dt>
                                        <dd><span class="badge">{{ $jadwal[$key] ?? '-' }}</span></dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </article>
                </div>

            </div>
        </div>
    </section>

    {{-- ======= Wave separator sesudah Jadwal Sholat ======= --}}
    <div class="wave-reverse">
        <svg viewBox="0 0 1440 120" width="100%" height="120" preserveAspectRatio="none">
            <path d="M0,64 C240,128 480,0 720,48 C960,96 1200,96 1440,48 L1440,120 L0,120 Z" fill="#ffffff"></path>
        </svg>
    </div>

    {{-- ======= Heading: Bidang & Layanan ======= --}}
    <section class="section section-bidang text-center" data-aos="fade-up">
        <div class="container">
            <h3 class="section-heading fw-bold mb-2 animate-fadein">Bidang & Layanan</h3>
            <div class="divider mx-auto"></div>
        </div>
    </section>

    {{-- ================= DIVISION CARDS ================= --}}
    <div id="divisioncard" class="container my-5" data-aos="fade-up">
        <div class="row text-center g-4">
            @php
                $divisions = [
                    ['name' => 'Kemasjidan', 'icon' => 'bi-moon-stars', 'url' => url('/')],
                    ['name' => 'Pendidikan', 'icon' => 'bi-mortarboard', 'url' => '#'],
                    ['name' => 'Sosial', 'icon' => 'bi-people', 'url' => '#'],
                    ['name' => 'Usaha', 'icon' => 'bi-bank', 'url' => '#'],
                ];
            @endphp
            @foreach ($divisions as $division)
                <div class="col-6 col-md-3">
                    <a href="{{ $division['url'] }}" class="division-item text-decoration-none"
                        aria-label="{{ $division['name'] }}">
                        <div class="division-icon">
                            <i class="bi {{ $division['icon'] }}"></i>
                        </div>
                        <h5 class="division-label mt-2">{{ $division['name'] }}</h5>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ======= Heading: Saldo Keuangan Terkini ======= --}}
    <section class="section section-bidang text-center" data-aos="fade-up">
        <div class="container">
            <h3 class="section-heading fw-bold mb-2 animate-fadein">Saldo Keuangan Terkini</h3>
            <div class="divider mx-auto"></div>
        </div>
    </section>

    {{-- ================= TOTAL INFAQ / TRANSAKSI ================= --}}
    <div id="totalinfaq" class="container-sm mt-3 bg-brown-light">
        <div class="row align-items-stretch gy-4">
            <!-- Kiri -->
            <div class="col-md-6 h-100 d-flex" data-aos="fade-right" data-aos-duration="1000">
                <div class="p-5 bg-light rounded-3 border w-100 h-100">
                    <h4 class="fs-4 pb-4 fw-bold">10 Transaksi Terakhir (Bid. Kemasjidan)</h4>

                    {{-- ✅ Responsive wrapper --}}
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestTransaksi as $trx)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d/m/Y') }}</td>
                                        <td>{{ $trx->deskripsi }}</td>
                                        <td>
                                            @if ($trx->type === 'penerimaan')
                                                <span class="badge bg-success">Penerimaan</span>
                                            @elseif($trx->type === 'pengeluaran')
                                                <span class="badge bg-danger">Pengeluaran</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($trx->type) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">Rp{{ number_format($trx->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada transaksi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div> {{-- end table-responsive --}}
                </div>
            </div>

            <!-- Kanan -->
            <div class="col-md-6 h-100 d-flex" data-aos="fade-left" data-aos-duration="1000">
                <div class="p-5 bg-light rounded-3 border w-100 h-100">
                    <h4 class="fs-4 fw-bold">Total Saldo Bidang Kemasjidan</h4>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h2 class="m-0"><strong>Rp<span id="saldoValue"
                                        data-target="{{ (int) $totalSaldo }}">0</span></strong></h2>
                        </div>
                    </div>
                    <p class="pt-1">
                        Last Update:
                        {{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('d-m-Y H:i:s') : 'Belum ada data' }}
                    </p>

                    <div class="mt-4 row align-items-center">
                        <div class="col-md-4 text-center mb-4">
                            <img src="/img/logobsi.png" alt="Logo Bank" class="img-fluid gy-4" style="max-width:150px;">
                        </div>
                        <div class="col-md-8">
                            <h5 class="fs-5 fw-bold">Informasi No. Rekening</h5>
                            <h3 class="mb-0">Bank Syariah Indonesia</h3>
                            <h3 class="fw-bold">1040820333</h3>
                            <p class="mb-0">A/N YYS Masjid Al Iman Sutorejo Indah</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= Heading: Infaq ======= --}}
    <section class="section section-bidang text-center" data-aos="fade-up">
        <div class="container">
            <h3 class="section-heading fw-bold mb-2 animate-fadein">Salurkan Infaq terbaik Anda!</h3>
            <div class="divider mx-auto"></div>
        </div>
    </section>

    {{-- ================= FORM INFAQ ================= --}}
    <div id="infaq" class="container-sm mt-2" data-aos="fade-in" data-aos-duration="1000" data-aos-delay="100">
        <form action="#" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row justify-content-center">
                <div class="p-5 bg-light rounded-3 border col-xl-6">
                    <div class="mb-4 text-center">
                        <h4 class="fs-2 fw-bold">Form Pencatatan Infaq</h4>
                        <small class="text-muted">Data Pribadi Jamaah Kami Pastikan tidak terpublikasikan!</small>
                    </div>
                    <div class="row">
                        <!-- Nama Input -->
                        <div class="col-mb-3">
                            <label for="nama" class="form-label">Nama</label>
                            <input class="form-control @error('nama') is-invalid @enderror" type="text" name="nama"
                                id="nama" value="{{ Auth::check() ? Auth::user()->name : old('nama') }}"
                                placeholder="Masukkan Nama">
                            <input type="hidden" name="user_id" value="{{ Auth::check() ? Auth::id() : '' }}">
                            @error('nama')
                                <div class="text-danger"><small>{{ $message }}</small></div>
                            @enderror
                        </div>

                        <!-- Nomor HP Input -->
                        <div class="mt-3">
                            <label for="nomor" class="form-label">No. Handphone</label>
                            <input class="form-control @error('nomor') is-invalid @enderror" type="text"
                                name="nomor" id="nomor"
                                value="{{ Auth::check() ? Auth::user()->nomor : old('nomor') }}"
                                placeholder="Masukkan No. HP">
                            @error('nomor')
                                <div class="text-danger"><small>{{ $message }}</small></div>
                            @enderror
                        </div>

                        <!-- Alamat Input -->
                        <div class="mt-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <input class="form-control @error('alamat') is-invalid @enderror" type="text"
                                name="alamat" id="alamat" value="{{ old('alamat') }}"
                                placeholder="Masukkan Alamat">
                            @error('alamat')
                                <div class="text-danger"><small>{{ $message }}</small></div>
                            @enderror
                        </div>

                        <!-- Nominal Infaq Input -->
                        <div class="mt-3">
                            <label for="nominal" class="form-label">Nominal Infaq</label>
                            <input class="form-control @error('nominal') is-invalid @enderror" type="number"
                                name="nominal" id="nominal" value="{{ old('nominal') }}"
                                placeholder="Masukkan Nominal Infaq">
                            @error('nominal')
                                <div class="text-danger"><small>{{ $message }}</small></div>
                            @enderror
                        </div>

                        <!-- Tujuan Infaq (Dropdown) -->
                        <div class="mt-3">
                            <label for="infaq" class="form-label">Tentukan tujuan Infaqmu</label>
                            <select name="infaq" id="infaq"
                                class="form-select @error('infaq') is-invalid @enderror">
                                {{-- @foreach ($infaqs as $Infaq)
                                    <option value="{{ $Infaq->id }}"
                                        {{ old('infaq') == $Infaq->id ? 'selected' : '' }}>
                                        {{ $Infaq->code . ' - ' . $Infaq->name }}</option>
                                @endforeach --}}
                            </select>
                            @error('infaq')
                                <div class="text-danger"><small>{{ $message }}</small></div>
                            @enderror
                        </div>

                        <!-- File Upload -->
                        <div class="mt-3">
                            <label for="file" class="form-label">Upload Bukti Transfer</label>
                            <input class="form-control @error('file') is-invalid @enderror" type="file" id="formFile"
                                name="file">
                            @error('file')
                                <div class="text-danger"><small>{{ $message }}</small></div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn bg-brown text-white">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ======= Heading: Layanan Kritik & Saran ======= --}}
    <section class="section section-bidang text-center" data-aos="fade-up">
        <div class="container">
            <h3 class="section-heading fw-bold mb-2 animate-fadein">Layanan Kritik & Saran</h3>
            <div class="divider mx-auto"></div>
        </div>
    </section>

    {{-- ================= FORM FEEDBACK ================= --}}
    <div id="feedback" class="container-sm mt-2">
        <div class="mb-4">
            <h4 class="fw-bold">Silahkan isi Masukan/Saran</h4>
            <small class="text-muted">Masukan/Saran Jamaah sangat kami harapkan guna untuk Kemakmuran Masjid kita!</small>
        </div>
        <form action="#" method="POST">
            @csrf
            <div class="mb-3">
                {{-- Input for Name --}}
                <input type="text" name="name" class="form-control"
                    value="{{ Auth::check() ? Auth::user()->name : '' }}" placeholder="Nama" required>
                @error('name')
                    <div class="text-danger"><small>{{ $message }}</small></div>
                @enderror
            </div>

            <div class="mb-3">
                {{-- Input for Phone Number --}}
                <input type="text" name="nomor" class="form-control"
                    value="{{ Auth::check() ? Auth::user()->nomor : '' }}" placeholder="Masukkan Nomor HP" required>
                @error('nomor')
                    <div class="text-danger"><small>{{ $message }}</small></div>
                @enderror
            </div>

            <div class="mb-3">
                {{-- Textarea for Message --}}
                <textarea name="message" class="form-control" rows="5" placeholder="Sampaikan saran & pesan untuk Kami."
                    required></textarea>
                @error('message')
                    <div class="text-danger"><small>{{ $message }}</small></div>
                @enderror
            </div>

            <button type="submit" class="btn btn-success">
                <i class="bi bi-whatsapp"></i> Kirim Saran & Masukan
            </button>
        </form>
    </div>

    {{-- ======= Wave separator sebelum Footer ======= --}}
    <div class="wave-reverse" style="background-color: white">
        <svg viewBox="0 0 1440 120" width="100%" height="120" preserveAspectRatio="none">
            <path d="M0,64 C240,128 480,0 720,48 C960,96 1200,96 1440,48 L1440,120 L0,120 Z" fill="#854321"></path>
        </svg>
    </div>

    {{-- ================= FOOTER ================= --}}
    <div id="footer" class="footer text-white" style="background-color: #854321">
        <div class="container py-5">
            <div class="row py-2 px-4">
                <div class="container">
                    <div class="row align-items-start">
                        <div class="col">
                            <a href="#" class="logo text-decoration-none">
                                <div class="d-flex">
                                    @if ($sidebarSetting?->logo_path)
                                        <img src="{{ asset('storage/' . $sidebarSetting->logo_path) }}" alt="Logo"
                                            class="img-fluid mb-3" style="max-height: 90px;">
                                    @endif
                                </div>
                            </a>
                            <ul class="list-unstyled footer-list">
                                <li class="mb-2">
                                    <div class="row">
                                        <div class="col-sm-1">
                                            <i class="bi bi-map"></i>
                                        </div>
                                        <div class="col">
                                            JL. Sutorejo Tengah X/2-4 Dukuh Sutorejo - Mulyorejo, Surabaya, Jawa Timur 60113
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <div class="row">
                                        <div class="col-sm-1">
                                            <i class="bi bi-telephone"></i>
                                        </div>
                                        <div class="col">
                                            0853 6936 9517
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <div class="row">
                                        <div class="col-sm-1">
                                            <i class="bi bi-envelope-paper-heart"></i>
                                        </div>
                                        <div class="col">
                                            masjidalimansurabaya@gmail.com
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="col">
                            <h5 class="mb-4">Kajian</h5>
                            <ul class="list-unstyled footer-list">
                                <li class="mb-2">Kajian Hari Besar Islam</li>
                                <li class="mb-2">Kajian Rutin Ahad Pagi</li>
                                <li class="mb-2">Kajian Tafsir Qur'an</li>
                            </ul>
                            <h5 class="my-4">Kegiatan</h5>
                            <ul class="list-unstyled footer-list">
                                <li class="mb-2">Pesantren Mahasiswa</li>
                                <li class="mb-2">Tadarus Al Qur'an</li>
                                <li class="mb-2">Syabab Rimayah Community Al Iman</li>
                                <li class="mb-2">Panitia Ramadhan 1446 H</li>
                                <li class="mb-2">Panitia Idul Adha 1446 H <strong>Coming Soon</strong></li>
                            </ul>
                        </div>

                        <div class="col">
                            <h5 class="mb-4">Profil</h5>
                            <ul class="list-unstyled footer-list">
                                <li class="mb-2">Sejarah</li>
                                <li class="mb-2">Struktur Organisasi</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= Wave separator sesudah Footer ======= --}}
    <div class="wave-reverse" style="transform: rotate(180deg);">
        <svg viewBox="0 0 1440 120" width="100%" height="120" preserveAspectRatio="none">
            <path d="M0,64 C240,128 480,0 720,48 C960,96 1200,96 1440,48 L1440,120 L0,120 Z" fill="#854321"></path>
        </svg>
    </div>

    {{-- COPYRIGHT --}}
    <div id="copyright" class="container-fluid py-3" style="background-color: #622200">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 col-xs-12 text-center" style="color: white;">© 2025 Yayasan Masjid Al Iman Sutorejo
                    Indah Surabaya. All rights reserved</div>
            </div>
        </div>
    </div>

    {{-- Floating WhatsApp + Back to Top --}}
    <a href="https://wa.me/6285369369517" target="_blank"
        class="btn btn-success fab-top lift d-flex align-items-center gap-2">
        <i class="bi bi-whatsapp fs-5"></i><span class="d-none d-md-inline">Hubungi Kami</span>
    </a>
    <button id="toTop" type="button" class="btn btn-success fab-top lift"
        style="right:18px; bottom:84px; display:none;">
        <i class="bi bi-arrow-up"></i>
    </button>

    @if (request()->has('verified') && request('verified') == 1)
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Verifikasi Berhasil!',
                text: 'Email Anda berhasil diverifikasi.',
                confirmButtonColor: '#622200'
            });
        </script>
    @endif

    @push('scripts')
        <script>
            // Smooth scroll untuk anchor internal
            document.querySelectorAll('a[href^="#"]').forEach(a => {
                a.addEventListener('click', e => {
                    const id = a.getAttribute('href');
                    if (id.length > 1 && document.querySelector(id)) {
                        e.preventDefault();
                        document.querySelector(id).scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Count-up saldo (format Rupiah)
            (function() {
                const el = document.getElementById('saldoValue');
                if (!el) return;
                const target = parseInt(el.dataset.target || '0', 10);
                const duration = 1200; // ms
                const start = performance.now();
                const fmt = new Intl.NumberFormat('id-ID');

                function tick(now) {
                    const p = Math.min(1, (now - start) / duration);
                    const val = Math.floor(p * target);
                    el.textContent = fmt.format(val);
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            })();

            // Back to top visibility
            (function() {
                const toTop = document.getElementById('toTop');
                if (!toTop) return;
                window.addEventListener('scroll', () => {
                    toTop.style.display = (window.scrollY > 320) ? 'inline-block' : 'none';
                });
                toTop?.addEventListener('click', () => window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                }));
            })();

            // Carousel pause/cycle fallback via JS
            (function() {
                const el = document.getElementById('carouselExampleSlidesOnly');
                if (!el) return;
                el.addEventListener('mouseenter', () => {
                    const carousel = bootstrap.Carousel.getInstance(el) || new bootstrap.Carousel(el);
                    carousel.pause();
                });
                el.addEventListener('mouseleave', () => {
                    const carousel = bootstrap.Carousel.getInstance(el) || new bootstrap.Carousel(el);
                    carousel.cycle();
                });
            })();

            // AOS refresh (jika dipakai)
            if (window.AOS && typeof AOS.refreshHard === 'function') {
                setTimeout(() => AOS.refreshHard(), 400);
            }
        </script>
    @endpush
@endsection

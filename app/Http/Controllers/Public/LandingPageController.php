<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Slideshow;
use App\Models\Kajian;
use App\Models\SidebarSetting;
use App\Models\Transaksi;

class LandingPageController extends Controller
{
    /**
     * Landing page
     */
    protected function getLastSaldoBySaldoColumn(
        int $akunId,
        string $userRole,
        $bidangValue,
        ?string $tanggalCutoff = null
    ): float {
        if (!$akunId)
            return 0.0;

        $q = Transaksi::where('akun_keuangan_id', $akunId);

        if ($tanggalCutoff) {
            $cutoff = Carbon::parse($tanggalCutoff)->toDateString();
            $q->whereDate('tanggal_transaksi', '<=', $cutoff);
        }

        if ($userRole !== 'Bendahara') {
            $q->where(function ($w) use ($bidangValue) {
                $w->where('bidang_name', $bidangValue)
                    ->orWhereNull('bidang_name');
            });
        }

        return (float) ($q->orderBy('tanggal_transaksi', 'desc')
            ->orderBy('id', 'desc')
            ->value('saldo') ?? 0.0);
    }

    public function index(Request $request)
    {
        // 1️⃣ Ambil nama kota dari query string, default: Surabaya
        $kota = $request->input('city', 'Surabaya');

        // 2️⃣ Ambil jadwal sholat sesuai kota
        $jadwalDefault = $this->getJadwalSholatLikeBlade($kota);

        // 3️⃣ Slideshow (terbaru dulu)
        $slideshows = Slideshow::query()
            ->when(method_exists(Slideshow::class, 'active'), fn($q) => $q->active())
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get(['id', 'image', 'title', 'description', 'created_at']);

        // 4️⃣ Kajian terdekat
        $nextKajian = Kajian::with(['jeniskajian:id,name', 'ustadz:id,name'])
            ->whereNotNull('start_time')
            ->where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->first([
                'id',
                'title',
                'description',
                'youtube_link',
                'image',
                'start_time',
                'jeniskajian_id',
                'ustadz_id'
            ]);

        $prefix = 'SJD-';
        $bidang = 1; // Kemasjidan

        // Akun internal Bendahara & Kemasjidan
        $rekeningInternal = [
            1011, // Kas Bendahara
            1021, // Bank Bendahara
            1012, // Kas Kemasjidan
            1022, // Bank Kemasjidan
        ];

        // Akun Kemasjidan (untuk hitung saldo)
        $akunKas = 1012;
        $akunBank = 1022;

        /* ======================================================================
        BASE FILTER: TAMPILKAN HANYA TRANSAKSI REAL (bukan transfer antar kantong)
        ====================================================================== */
        $baseFilter = Transaksi::where('bidang_name', $bidang)
            ->where('kode_transaksi', 'like', $prefix . '%')
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereIn('type', ['penerimaan', 'pengeluaran'])
            ->where(function ($q) use ($rekeningInternal) {
                $q->whereNull('parent_akun_id')
                    ->orWhereNot(function ($qq) use ($rekeningInternal) {
                        $qq->whereIn('akun_keuangan_id', $rekeningInternal)
                            ->whereIn('parent_akun_id', $rekeningInternal);
                    });
            });

        /* ======================================================================
        10 TRANSAKSI TERBARU (bukan transfer)
        ====================================================================== */
        $latestTransaksi = (clone $baseFilter)
            ->with(['parentAkunKeuangan:id,nama_akun']) // <= load name nya
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->limit(10)
            ->get([
                'tanggal_transaksi',
                'parent_akun_id',
                'deskripsi',
                'type',
                'amount',
                'created_at',
                'updated_at',
            ]);

        /* ======================================================================
        SALDO MENGGUNAKAN FUNGSIMU
        ====================================================================== */
        $saldoKasKemasjidan = $this->getLastSaldoBySaldoColumn($akunKas, 'Bidang', $bidang, null);
        $saldoBankKemasjidan = $this->getLastSaldoBySaldoColumn($akunBank, 'Bidang', $bidang, null);

        $totalSaldo = $saldoKasKemasjidan + $saldoBankKemasjidan;


        // Last update: MAX(COALESCE(updated_at, created_at))
        $lastUpdate = (clone $baseFilter)
            ->selectRaw('MAX(COALESCE(updated_at, created_at)) AS ts')
            ->value('ts');

        // 5️⃣ Kirim ke view
        return view('public.landing-page', [
            'slideshows' => $slideshows,
            'nextKajian' => $nextKajian,
            'jadwalDefault' => $jadwalDefault,
            'selectedCity' => $kota,
            'latestTransaksi' => $latestTransaksi,
            'totalSaldo' => $totalSaldo,
            'lastUpdate' => $lastUpdate,
        ]);
    }

    /**
     * Bentuk data jadwal seperti yang dipakai Blade:
     * $jadwalDefault['data']['jadwal'][0]['imsak'|'subuh'|'terbit'|'dhuha'|'dzuhur'|'ashar'|'maghrib'|'isya']
     */
    /**
     * Ambil jadwal sholat dari API MyQuran dan kembalikan
     * dalam bentuk yang dipakai Blade: ['data']['jadwal'][0] = [...jam...]
     *
     * API Referensi (MyQuran):
     * - Cari kota:   GET https://api.myquran.com/v2/sholat/kota/cari/{nama}
     * - Jadwal hari: GET https://api.myquran.com/v2/sholat/jadwal/{idKota}/{Y}/{m}/{d}
     */
    private function getJadwalSholatLikeBlade(string $kota = 'Surabaya'): array
    {
        // Selalu pakai zona waktu Indonesia
        $now = Carbon::now('Asia/Jakarta');

        try {
            // 1) Dapatkan id kota (cache 1 hari biar irit request)
            $cacheKey = 'myquran:kotaid:' . mb_strtolower($kota);
            $kotaId = Cache::remember($cacheKey, now()->addDay(), function () use ($kota) {
                $resp = Http::retry(2, 300)
                    ->timeout(8)
                    ->get('https://api.myquran.com/v2/sholat/kota/cari/' . urlencode($kota));

                if (!$resp->ok())
                    return null;

                $json = $resp->json(); // bentuk: ['data' => [ { id, lokas, ... }, ... ]]
                $first = $json['data'][0] ?? null;

                // MyQuran biasanya pakai kolom 'id' sebagai string/angka
                return $first['id'] ?? null;
            });

            // Jika gagal dapat id kota, fallback ke dummy
            if (empty($kotaId)) {
                return $this->fallbackJadwal($kota, $now);
            }

            // 2) Ambil jadwal sholat HARI INI untuk kota tsb
            $url = sprintf(
                'https://api.myquran.com/v2/sholat/jadwal/%s/%s/%s/%s',
                $kotaId,
                $now->format('Y'),
                $now->format('m'),
                $now->format('d')
            );

            $resp = Http::retry(2, 300)->timeout(8)->get($url);
            if (!$resp->ok()) {
                return $this->fallbackJadwal($kota, $now);
            }

            $json = $resp->json();
            // Struktur MyQuran: ['data' => ['jadwal' => ['imsak'=>'04:xx', 'subuh'=>... ]]]
            $j = $json['data']['jadwal'] ?? null;
            if (!$j) {
                return $this->fallbackJadwal($kota, $now);
            }

            // Normalisasi key agar match Blade kamu
            $mapped = [
                'imsak' => $j['imsak'] ?? null,
                'subuh' => $j['subuh'] ?? null,
                'terbit' => $j['terbit'] ?? null,
                'dhuha' => $j['dhuha'] ?? null,
                'dzuhur' => $j['dzuhur'] ?? null,
                'ashar' => $j['ashar'] ?? null,
                'maghrib' => $j['maghrib'] ?? null,
                'isya' => $j['isya'] ?? null,
                'date' => $now->format('Y-m-d'),
                'kota' => $kota,
            ];

            return ['data' => ['jadwal' => [$mapped]]];
        } catch (\Throwable $e) {
            // Fallback aman kalau API/Internet error
            return $this->fallbackJadwal($kota, $now);
        }
    }

    /**
     * Fallback dummy jika API gagal
     */
    private function fallbackJadwal(string $kota, Carbon $now): array
    {
        $dummy = [
            'imsak' => '04:10',
            'subuh' => '04:20',
            'terbit' => '05:30',
            'dhuha' => '05:55',
            'dzuhur' => '11:25',
            'ashar' => '14:45',
            'maghrib' => '17:35',
            'isya' => '18:45',
            'date' => $now->format('Y-m-d'),
            'kota' => $kota,
        ];
        return ['data' => ['jadwal' => [$dummy]]];
    }


    // Resource methods (optional)
    public function create()
    {
    }
    public function store(Request $request)
    {
    }
    public function show(string $id)
    {
    }
    public function edit(string $id)
    {
    }
    public function update(Request $request, string $id)
    {
    }
    public function destroy(string $id)
    {
    }
}

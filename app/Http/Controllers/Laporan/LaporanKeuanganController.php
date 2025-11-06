<?php

namespace App\Http\Controllers\Laporan;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use App\Exports\LaporanExport;
use App\Models\PendapatanBelumDiterima;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Ledger;
use App\Models\Piutang;
use App\Models\Hutang;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;




class LaporanKeuanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function arusKas(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        $user = auth()->user();

        // Helper untuk filter transaksi non-internal antar akun kas
        $filterTransaksiKasEksternal = function ($transaksi) {
            return !(
                isKasAkun($transaksi->akunAsal) &&
                isKasAkun($transaksi->akunTujuan)
            );
        };

        // Ambil data transaksi sesuai kategori cashflow, lalu filter agar bukan antar akun kas
        $transaksiOperasional = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'operasional'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiInvestasi = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'investasi'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiPendanaan = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'pendanaan'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        // Hitung total kas masuk dan keluar
        $kasOperasionalMasuk = $transaksiOperasional->where('type', 'penerimaan')->sum('amount');
        $kasOperasionalKeluar = $transaksiOperasional->where('type', 'pengeluaran')->sum('amount');

        $kasInvestasiMasuk = $transaksiInvestasi->where('type', 'penerimaan')->sum('amount');
        $kasInvestasiKeluar = $transaksiInvestasi->where('type', 'pengeluaran')->sum('amount');

        $kasPendanaanMasuk = $transaksiPendanaan->where('type', 'penerimaan')->sum('amount');
        $kasPendanaanKeluar = $transaksiPendanaan->where('type', 'pengeluaran')->sum('amount');

        // Total arus kas
        $totalKasMasuk = $kasOperasionalMasuk + $kasInvestasiMasuk + $kasPendanaanMasuk;
        $totalKasKeluar = $kasOperasionalKeluar + $kasInvestasiKeluar + $kasPendanaanKeluar;

        return view('laporan.arus_kas', compact(
            'startDate',
            'endDate',
            'kasOperasionalMasuk',
            'kasOperasionalKeluar',
            'kasInvestasiMasuk',
            'kasInvestasiKeluar',
            'kasPendanaanMasuk',
            'kasPendanaanKeluar',
            'totalKasMasuk',
            'totalKasKeluar'
        ));
    }

    // 📥 Export Arus Kas ke PDF
    public function exportArusKasPDF(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        $user = auth()->user();

        // Helper untuk filter transaksi non-internal antar akun kas
        $filterTransaksiKasEksternal = function ($transaksi) {
            return !(
                isKasAkun($transaksi->akunAsal) &&
                isKasAkun($transaksi->akunTujuan)
            );
        };

        // Ambil data transaksi sesuai kategori cashflow, lalu filter agar bukan antar akun kas
        $transaksiOperasional = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'operasional'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiInvestasi = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'investasi'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        $transaksiPendanaan = Transaksi::where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereHas('akunTujuan', fn($q) => $q->where('cashflow_category', 'pendanaan'))
            ->with(['akunAsal', 'akunTujuan'])
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->when($user->role === 'Bidang', fn($q) => $q->where('bidang_name', $user->bidang_name))
            ->get()
            ->filter($filterTransaksiKasEksternal);

        // Hitung total kas masuk dan keluar
        $kasOperasionalMasuk = $transaksiOperasional->where('type', 'penerimaan')->sum('amount');
        $kasOperasionalKeluar = $transaksiOperasional->where('type', 'pengeluaran')->sum('amount');

        $kasInvestasiMasuk = $transaksiInvestasi->where('type', 'penerimaan')->sum('amount');
        $kasInvestasiKeluar = $transaksiInvestasi->where('type', 'pengeluaran')->sum('amount');

        $kasPendanaanMasuk = $transaksiPendanaan->where('type', 'penerimaan')->sum('amount');
        $kasPendanaanKeluar = $transaksiPendanaan->where('type', 'pengeluaran')->sum('amount');

        // Total arus kas
        $totalKasMasuk = $kasOperasionalMasuk + $kasInvestasiMasuk + $kasPendanaanMasuk;
        $totalKasKeluar = $kasOperasionalKeluar + $kasInvestasiKeluar + $kasPendanaanKeluar;

        $pdf = Pdf::loadView('laporan.export.arus_kas_pdf', compact(
            'startDate',
            'endDate',
            'kasOperasionalMasuk',
            'kasOperasionalKeluar',
            'kasInvestasiMasuk',
            'kasInvestasiKeluar',
            'kasPendanaanMasuk',
            'kasPendanaanKeluar',
            'totalKasMasuk',
            'totalKasKeluar'
        ));
        return $pdf->download('laporan_arus_kas.pdf');
    }

    // 📊 Laporan Posisi Keuangan
    public function posisiKeuangan(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', Carbon::now()->endOfMonth()));


        $kategoriAkun = ['asset', 'liability', 'equity'];
        $data = [];

        foreach ($kategoriAkun as $kategori) {
            $akuns = AkunKeuangan::with([
                'transaksis' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
                }
            ])->where('tipe_akun', $kategori)->get();

            $total = $akuns->sum('saldo'); // saldo tetap dari accessor

            $data[$kategori] = [
                'akuns' => $akuns,
                'total' => $total,
            ];
        }

        return view('laporan.posisi_keuangan', compact('data', 'startDate', 'endDate'));
    }

    // 📈 Laporan Aktivitas / Laba Rugi
    public function labaRugi()
    {
        $pendapatan = AkunKeuangan::where('tipe_akun', 'revenue')->with('transaksis')->get();
        $beban = AkunKeuangan::where('tipe_akun', 'expense')->with('transaksis')->get();

        return view('laporan.laba_rugi', compact('pendapatan', 'beban'));
    }

    /***********************
     * Helpers
     ***********************/
    /**
     * Ambil saldo terakhir dari kolom `saldo` untuk suatu akun keuangan,
     * mempertimbangkan role & bidang, dan batas tanggal (cutoff) bila ada.
     * Selalu return float (>= 0).
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

        // Untuk non-bendahara: filter per bidang + fallback histori lama (NULL)
        if ($userRole !== 'Bendahara') {
            $q->where(function ($w) use ($bidangValue) {
                $w->where('bidang_name', $bidangValue)
                    ->orWhereNull('bidang_name');
            });
        }

        // Ambil nilai saldo dari baris transaksi terbaru
        return (float) ($q->orderBy('tanggal_transaksi', 'desc')
            ->orderBy('id', 'desc')
            ->value('saldo') ?? 0.0);
    }

    /**
     * Menjumlahkan transaksi berdasarkan parent akun (dinamis via parent_akun_id).
     * Jika $bidangName null → tidak filter bidang (Bendahara total);
     * jika non-null → filter per bidang.
     *
     * NOTE:
     * - Dipakai untuk PENDAPATAN (mis. parent 202*) dan BEBAN (parent 302..310).
     * - Di sistemmu, akun pendapatan/beban di-post sebagai baris primer ke akun Kas/Bank
     *   sementara parent_akun_id mengacu ke kategori pendapatan/beban. Karena itu SUM(amount)
     *   di sini sudah cukup sebagai nilai brutonya.
     */
    private function sumTransaksiByParent(int $parentId, $bidangName = null): float
    {
        $subAkunIds = AkunKeuangan::where('parent_id', $parentId)->pluck('id')->toArray();
        if (empty($subAkunIds))
            return 0.0;

        $q = Transaksi::whereIn('parent_akun_id', $subAkunIds);
        if (!is_null($bidangName)) {
            $q->where('bidang_name', $bidangName);
        }
        return (float) $q->sum('amount');
    }

    /**
     * Dapatkan mapping akun Kas/Bank untuk user saat ini.
     */
    private function resolveKasBankForUser(): array
    {
        $user = auth()->user();
        $role = $user->role ?? 'Guest';
        $bidangId = $user->bidang_name ?? null;

        $kasMap = [1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015];
        $bankMap = [1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025];

        if ($role === 'Bendahara') {
            return [
                'role' => 'Bendahara',
                'bidangId' => null,
                'kasId' => 1011,
                'bankId' => 1021,
            ];
        }

        return [
            'role' => 'Bidang',
            'bidangId' => $bidangId,
            'kasId' => $kasMap[$bidangId] ?? null,
            'bankId' => $bankMap[$bidangId] ?? null,
        ];
    }

    /**
     * Hitung total Kas/Bank seluruh entitas:
     * - Bendahara-global (NULL)
     * - Bidang 1..4
     */
    private function getTotalKasBankSemua(): array
    {
        $maps = [
            ['bidang' => null, 'kas' => 1011, 'bank' => 1021], // Bendahara global
            ['bidang' => 1, 'kas' => 1012, 'bank' => 1022],
            ['bidang' => 2, 'kas' => 1013, 'bank' => 1023],
            ['bidang' => 3, 'kas' => 1014, 'bank' => 1024],
            ['bidang' => 4, 'kas' => 1015, 'bank' => 1025],
        ];

        $saldoKasTotal = 0.0;
        $saldoBankTotal = 0.0;

        foreach ($maps as $m) {
            // Saat agregasi total, kita treat “role” sebagai Bendahara agar fungsi tidak
            // mem-filter per-bidang (karena kita kirim bidangnya manual di param ketiga).
            $kas = $this->getLastSaldoBySaldoColumn($m['kas'], 'Bendahara', $m['bidang'], null);
            $bank = $this->getLastSaldoBySaldoColumn($m['bank'], 'Bendahara', $m['bidang'], null);
            $saldoKasTotal += $kas;
            $saldoBankTotal += $bank;
        }

        return [
            'saldoKasTotal' => $saldoKasTotal,
            'saldoBankTotal' => $saldoBankTotal,
            'totalKeuangan' => $saldoKasTotal + $saldoBankTotal,
        ];
    }

    /***********************
     * Laporan: NERACA
     ***********************/
    public function neracaSaldo(Request $request)
    {
        $user = auth()->user();
        $role = $user->role ?? 'Guest';
        $map = $this->resolveKasBankForUser();

        // Filter tanggal opsional (untuk posisi per tanggal tertentu)
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $startDate = $request->input('start_date'); // tidak dipakai untuk posisi, tapi tetap disediakan di form

        // ====== ASET LANCAR: Kas & Bank ======
        $saldoKas = $this->getLastSaldoBySaldoColumn($map['kasId'], $map['role'], $map['bidangId'], $endDate);
        $saldoBank = $this->getLastSaldoBySaldoColumn($map['bankId'], $map['role'], $map['bidangId'], $endDate);

        // ====== PIUTANG (asset) ======
        // PSAK: posisi neraca idealnya nilai piutang outstanding (belum lunas).
        $piutang = Piutang::when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        // ====== ASET TETAP / LAINNYA ======
        // (Tetap mengikuti kebiasaanmu: sum amount; kalau ingin akumulasi saldo, bisa dibuat pakai kolom `saldo` untuk akun TS tersebut)
        $tanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->sum('amount');

        $inventaris = Transaksi::where('akun_keuangan_id', 105)
            ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->sum('amount');

        $totalAset = $saldoKas + $saldoBank + $piutang + $tanahBangunan + $inventaris;

        // ====== LIABILITAS ======
        $hutang = Hutang::when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $pendapatanBelumDiterima = PendapatanBelumDiterima::when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->sum('jumlah');

        $totalLiabilitas = $hutang + $pendapatanBelumDiterima;

        // ====== ASET BERSIH (Net Assets) ======
        $asetBersih = $totalAset - $totalLiabilitas;

        // ====== Jika Bendahara: total agregat semua bidang untuk Kas/Bank (opsional tampilkan) ======
        $totalKasBankAll = ($map['role'] === 'Bendahara') ? $this->getTotalKasBankSemua() : null;

        return view('laporan.neraca_saldo', [
            'role' => $map['role'],
            'bidangId' => $map['bidangId'],

            'startDate' => $startDate,
            'endDate' => $endDate,

            // Aset
            'saldoKas' => $saldoKas,
            'saldoBank' => $saldoBank,
            'piutang' => $piutang,
            'tanahBangunan' => $tanahBangunan,
            'inventaris' => $inventaris,
            'totalAset' => $totalAset,

            // Liabilitas
            'hutang' => $hutang,
            'pendapatanBelumDiterima' => $pendapatanBelumDiterima,
            'totalLiabilitas' => $totalLiabilitas,

            // Aset Bersih
            'asetBersih' => $asetBersih,

            // Agregat Bendahara
            'saldoKasTotal' => $totalKasBankAll['saldoKasTotal'] ?? null,
            'saldoBankTotal' => $totalKasBankAll['saldoBankTotal'] ?? null,
            'totalKeuanganSemuaBidang' => $totalKasBankAll['totalKeuangan'] ?? null,
        ]);
    }

    public function neracaSaldoBendahara(Request $request)
    {
        // Paksa mode Bendahara
        $user = auth()->user();
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $startDate = $request->input('start_date');

        // Ambil total seluruh kas/bank semua bidang + bendahara
        $totalKasBankAll = $this->getTotalKasBankSemua();

        // ====== PIUTANG (semua bidang) ======
        $piutang = Piutang::where('status', 'belum_lunas')->sum('jumlah');

        // ====== ASET TETAP ======
        $tanahBangunan = Transaksi::where('akun_keuangan_id', 104)->sum('amount');
        $inventaris = Transaksi::where('akun_keuangan_id', 105)->sum('amount');

        $totalAset = $totalKasBankAll['totalKeuangan'] + $piutang + $tanahBangunan + $inventaris;

        // ====== LIABILITAS ======
        $hutang = Hutang::where('status', 'belum_lunas')->sum('jumlah');
        $pendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');
        $totalLiabilitas = $hutang + $pendapatanBelumDiterima;

        // ====== ASET BERSIH ======
        $asetBersih = $totalAset - $totalLiabilitas;

        return view('laporan.neraca_saldo', [
            'role' => 'Bendahara',
            'bidangId' => null,
            'startDate' => $startDate,
            'endDate' => $endDate,

            // Aset
            'saldoKas' => $totalKasBankAll['saldoKasTotal'],
            'saldoBank' => $totalKasBankAll['saldoBankTotal'],
            'piutang' => $piutang,
            'tanahBangunan' => $tanahBangunan,
            'inventaris' => $inventaris,
            'totalAset' => $totalAset,

            // Liabilitas
            'hutang' => $hutang,
            'pendapatanBelumDiterima' => $pendapatanBelumDiterima,
            'totalLiabilitas' => $totalLiabilitas,

            // Aset Bersih
            'asetBersih' => $asetBersih,

            // Total agregat (Kas/Bank semua bidang)
            'saldoKasTotal' => $totalKasBankAll['saldoKasTotal'],
            'saldoBankTotal' => $totalKasBankAll['saldoBankTotal'],
            'totalKeuanganSemuaBidang' => $totalKasBankAll['totalKeuangan'],
        ]);
    }

    /***********************
     * Laporan: AKTIVITAS (PSAK 45)
     ***********************/
    public function aktivitas(Request $request)
    {
        $user = auth()->user();
        $role = $user->role ?? 'Guest';
        $map = $this->resolveKasBankForUser();

        // Periode laporan (arus periodik)
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Filter builder by period
        $periodFilter = function ($q) use ($startDate, $endDate) {
            $q->whereDate('tanggal_transaksi', '>=', $startDate)
                ->whereDate('tanggal_transaksi', '<=', $endDate);
        };

        // ====== Pendapatan (contoh: parent 202 = Donasi) ======
        // NOTE: sumTransaksiByParent() tidak punya filter tanggal. Kita bungkus via ID transaksi.
        // Implementasi sederhana: batasi lewat whereBetween tanggal di query tambahan.
        // -> Cara cepat: ulang logic sum di sini dengan filter tanggal.
        $donasiParent = 202;
        $donasiSubIds = AkunKeuangan::where('parent_id', $donasiParent)->pluck('id')->toArray();

        $qDonasi = Transaksi::whereIn('parent_akun_id', $donasiSubIds)
            ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']));
        $periodFilter($qDonasi);
        $pendapatanDonasi = (float) $qDonasi->sum('amount');

        // ====== Beban (contoh: parents 302..310) ======
        $bebanParents = AkunKeuangan::where('tipe_akun', 'expense')
            ->whereNull('parent_id') // hanya ambil parent kategori utama
            ->pluck('id')
            ->toArray();
        $totalBeban = 0.0;
        $rincianBeban = [];

        foreach ($bebanParents as $pid) {
            $subIds = AkunKeuangan::where('parent_id', $pid)->pluck('id')->toArray();
            if (empty($subIds)) {
                $rincianBeban[$pid] = 0.0;
                continue;
            }
            $q = Transaksi::whereIn('parent_akun_id', $subIds)
                ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']));
            $periodFilter($q);
            $nilai = (float) $q->sum('amount');
            $rincianBeban[$pid] = $nilai;
            $totalBeban += $nilai;
        }

        // ====== Surplus (Defisit) Periode ======
        $totalPendapatan = $pendapatanDonasi; // tambah pendapatan lain jika ada
        $surplus = $totalPendapatan - $totalBeban;

        return view('laporan.aktivitas', [
            'role' => $map['role'],
            'bidangId' => $map['bidangId'],
            'startDate' => $startDate,
            'endDate' => $endDate,

            'pendapatanDonasi' => $pendapatanDonasi,
            'totalPendapatan' => $totalPendapatan,

            'rincianBeban' => $rincianBeban,
            'totalBeban' => $totalBeban,

            'surplus' => $surplus,
        ]);
    }

    // ===== helper kecil untuk reuse data Neraca (pakai fungsi yang sudah kamu punya) =====
    private function buildNeracaData(string $endDate, ?string $startDate = null): array
    {
        // Ambil mapping akun user (kasId, bankId, role, bidangId)
        $map = $this->resolveKasBankForUser();

        // Saldo posisi per tanggal (pakai getLastSaldoBySaldoColumn)
        $saldoKas = $this->getLastSaldoBySaldoColumn($map['kasId'], $map['role'], $map['bidangId'], $endDate);
        $saldoBank = $this->getLastSaldoBySaldoColumn($map['bankId'], $map['role'], $map['bidangId'], $endDate);

        // Piutang outstanding
        $piutang = Piutang::when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        // Aset tetap (sesuai kebiasaanmu saat ini – sum amount)
        $tanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->sum('amount');

        $inventaris = Transaksi::where('akun_keuangan_id', 105)
            ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->sum('amount');

        $totalAset = $saldoKas + $saldoBank + $piutang + $tanahBangunan + $inventaris;

        // Liabilitas
        $hutang = Hutang::when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $pendapatanBelumDiterima = PendapatanBelumDiterima::when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->sum('jumlah');

        $totalLiabilitas = $hutang + $pendapatanBelumDiterima;
        $asetBersih = $totalAset - $totalLiabilitas;

        // Agregat Bendahara (opsional tampilkan kalau role bendahara)
        $totalKasBankAll = ($map['role'] === 'Bendahara') ? $this->getTotalKasBankSemua() : null;

        return [
            'role' => $map['role'],
            'bidangId' => $map['bidangId'],
            'startDate' => $startDate,
            'endDate' => $endDate,

            // Aset
            'saldoKas' => $saldoKas,
            'saldoBank' => $saldoBank,
            'piutang' => $piutang,
            'tanahBangunan' => $tanahBangunan,
            'inventaris' => $inventaris,
            'totalAset' => $totalAset,

            // Liabilitas
            'hutang' => $hutang,
            'pendapatanBelumDiterima' => $pendapatanBelumDiterima,
            'totalLiabilitas' => $totalLiabilitas,

            // Aset Bersih
            'asetBersih' => $asetBersih,

            // Agregat bendahara
            'saldoKasTotal' => $totalKasBankAll['saldoKasTotal'] ?? null,
            'saldoBankTotal' => $totalKasBankAll['saldoBankTotal'] ?? null,
            'totalKeuanganSemuaBidang' => $totalKasBankAll['totalKeuangan'] ?? null,
        ];
    }

    // ===== helper data Aktivitas (periode berjalan) =====
    private function buildAktivitasData(string $startDate, string $endDate): array
    {
        $map = $this->resolveKasBankForUser();

        // Helper period filter (reusable)
        $periodFilter = function ($q) use ($startDate, $endDate) {
            $q->whereDate('tanggal_transaksi', '>=', Carbon::parse($startDate)->toDateString())
                ->whereDate('tanggal_transaksi', '<=', Carbon::parse($endDate)->toDateString());
        };

        // ====== List transaksi detail (untuk tabel) — eksklusif baris -LAWAN ======
        $aktivitas = Transaksi::when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->tap($periodFilter)
            ->orderBy('tanggal_transaksi')
            ->get();

        // ====== PENDAPATAN (contoh: Donasi parent 202) ======
        $donasiParent = 202;
        $donasiSubIds = AkunKeuangan::where('parent_id', $donasiParent)->pluck('id')->toArray();

        $qDonasi = Transaksi::whereIn('parent_akun_id', $donasiSubIds)
            ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
            ->where('kode_transaksi', 'not like', '%-LAWAN');
        $periodFilter($qDonasi);

        $pendapatanDonasi = (float) $qDonasi->sum('amount');

        // (opsional) jika ada pendapatan lain, tambahkan cara yang sama di sini, lalu jumlahkan
        $totalPendapatan = $pendapatanDonasi;

        // ====== BEBAN (dinamis dari parent tipe_akun='expense') ======
        $bebanParents = AkunKeuangan::where('tipe_akun', 'expense')
            ->whereNull('parent_id')   // ambil kategori beban level-atas (302..310)
            ->pluck('id')
            ->toArray();

        $rincianBeban = [];
        $totalBeban = 0.0;

        foreach ($bebanParents as $pid) {
            $subIds = AkunKeuangan::where('parent_id', $pid)->pluck('id')->toArray();
            if (empty($subIds)) { // aman kalau belum ada anak
                $rincianBeban[$pid] = 0.0;
                continue;
            }

            $qb = Transaksi::whereIn('parent_akun_id', $subIds)
                ->when($map['role'] === 'Bidang', fn($q) => $q->where('bidang_name', $map['bidangId']))
                ->where('kode_transaksi', 'not like', '%-LAWAN');
            $periodFilter($qb);

            $nilai = (float) $qb->sum('amount');
            $rincianBeban[$pid] = $nilai;
            $totalBeban += $nilai;
        }

        // ====== Surplus/(Defisit) periode ======
        $surplusDefisit = $totalPendapatan - $totalBeban;

        // ====== Ringkasan total kolom (jika tetap ingin cek cepat dari list transaksi) ======
        $totalPenerimaan = (float) $aktivitas->where('type', 'penerimaan')->sum('amount');
        $totalPengeluaran = (float) $aktivitas->where('type', 'pengeluaran')->sum('amount');

        return [
            // untuk tabel detail
            'aktivitas' => $aktivitas,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalPenerimaan' => $totalPenerimaan,
            'totalPengeluaran' => $totalPengeluaran,

            // untuk perhitungan laporan aktivitas (agregat)
            'pendapatanDonasi' => $pendapatanDonasi,
            'totalPendapatan' => $totalPendapatan,
            'rincianBeban' => $rincianBeban,   // key = parent_id beban, value = total
            'totalBeban' => $totalBeban,
            'surplusDefisit' => $surplusDefisit,
        ];
    }

    // =========================
    // EXPORT NERACA - PDF
    // =========================
    public function exportNeracaPdf(Request $request)
    {
        $end = $request->input('end_date', Carbon::now()->toDateString());
        $start = $request->input('start_date');

        $data = $this->buildNeracaData($end, $start);

        // gunakan view: resources/views/laporan/export/neraca_saldo_pdf.blade.php
        $pdf = Pdf::loadView('laporan.export.neraca_saldo_pdf', $data)->setPaper('a4', 'portrait');

        $name = 'Neraca_' . ($data['role'] === 'Bendahara' ? 'Yayasan' : 'Bidang_' . $data['bidangId']) . "_{$end}.pdf";
        return $pdf->download($name);
    }

    // =========================
    // EXPORT NERACA - Excel
    // =========================
    public function exportNeracaExcel(Request $request)
    {
        $end = $request->input('end_date', Carbon::now()->toDateString());
        $start = $request->input('start_date');

        $data = $this->buildNeracaData($end, $start);

        // gunakan view: resources/views/laporan/export/neraca_saldo_export_excel.blade.php
        $name = 'Neraca_' . ($data['role'] === 'Bendahara' ? 'Yayasan' : 'Bidang_' . $data['bidangId']) . "_{$end}.xlsx";
        return Excel::download(new LaporanExport('laporan.export.neraca_saldo_export_excel', $data), $name);
    }

    // =========================
    /* EXPORT AKTIVITAS - PDF */
    // =========================
    public function exportAktivitasPdf(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $data = $this->buildAktivitasData($start, $end);

        // gunakan view: resources/views/laporan/export/aktivitas_pdf.blade.php
        $pdf = Pdf::loadView('laporan.export.aktivitas_pdf', $data)->setPaper('a4', 'portrait');

        $name = "Laporan_Aktivitas_{$start}_{$end}.pdf";
        return $pdf->download($name);
    }

    // =========================
    /* EXPORT AKTIVITAS - Excel */
    // =========================
    public function exportAktivitasExcel(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $data = $this->buildAktivitasData($start, $end);

        // gunakan view: resources/views/laporan/export/aktivitas_export_excel.blade.php
        $name = "Laporan_Aktivitas_{$start}_{$end}.xlsx";
        return Excel::download(new LaporanExport('laporan.export.aktivitas_export_excel', $data), $name);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaksi $transaksi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        //
    }
}

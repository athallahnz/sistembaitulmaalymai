<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\AkunKeuangan;
use App\Models\Piutang;
use App\Models\Hutang;
use App\Models\PendapatanBelumDiterima;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Services\LaporanService;
use App\Services\LaporanKeuanganService;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class BendaharaController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('role:Bendahara|Ketua Yayasan');
    // }

    // ===============================================
    // HELPER FUNCTIONS
    // ===============================================

    protected function getSubAkunIds($parentId)
    {
        $result = [];
        $stack = [(int) $parentId];

        while (!empty($stack)) {
            $pid = array_pop($stack);
            $children = AkunKeuangan::where('parent_id', $pid)->pluck('id')->all();

            foreach ($children as $cid) {
                if (!in_array($cid, $result, true)) {
                    $result[] = $cid;
                    $stack[] = $cid;
                }
            }
        }

        return $result;
    }

    /**
     * Menjumlahkan transaksi berdasarkan parent akun (dinamis lewat parent_akun_id)
     * dan dibatasi per bidang (nullable → whereNull).
     */
    private function sumTransaksiByParent(int $parentId, $bidangName = null): float
    {
        $subAkunIds = AkunKeuangan::where('parent_id', $parentId)->pluck('id')->toArray();

        if (empty($subAkunIds)) {
            return 0.0;
        }

        $query = Transaksi::whereIn('parent_akun_id', $subAkunIds);

        if (!is_null($bidangName)) {
            $query->where('bidang_name', $bidangName);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Hitung total kas & bank untuk semua bidang + bendahara
     * dengan basis LaporanKeuanganService (ledger, PSAK 45).
     */
    protected function getTotalKasDanBankSemuaBidang(): array
    {
        $lapService = new LaporanKeuanganService();

        // Mapping akun kas/bank per "entitas"
        $maps = [
            ['bidang' => null, 'kas' => 1011, 'bank' => 1021], // Bendahara (global)
            ['bidang' => 1, 'kas' => 1012, 'bank' => 1022], // Kemasjidan
            ['bidang' => 2, 'kas' => 1013, 'bank' => 1023], // Pendidikan
            ['bidang' => 3, 'kas' => 1014, 'bank' => 1024], // Sosial
            ['bidang' => 4, 'kas' => 1015, 'bank' => 1025], // Usaha
        ];

        $saldoKasTotal = 0.0;
        $saldoBankTotal = 0.0;

        foreach ($maps as $m) {
            $kasAkun = AkunKeuangan::find($m['kas']);
            $bankAkun = AkunKeuangan::find($m['bank']);

            $saldoKas = $kasAkun
                ? $lapService->getSaldoAkunSampai($kasAkun, Carbon::now())
                : 0.0;

            $saldoBank = $bankAkun
                ? $lapService->getSaldoAkunSampai($bankAkun, Carbon::now())
                : 0.0;

            $saldoKasTotal += $saldoKas;
            $saldoBankTotal += $saldoBank;
        }

        return [
            'saldoKasTotal' => $saldoKasTotal,
            'saldoBankTotal' => $saldoBankTotal,
            'totalKeuanganSemuaBidang' => $saldoKasTotal + $saldoBankTotal,
        ];
    }

    // ===============================================
    // INDEX FUNCTION (DASHBOARD BENDAHARA)
    // ===============================================

    public function index()
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null; // integer id bidang
        $role = $user->role;

        $lapService = new LaporanKeuanganService();

        // ==================================
        // 🔹 Kas & Bank AKTIF untuk Bendahara
        // ==================================
        $akunKasId = ($role === 'Bendahara')
            ? 1011
            : ([1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015][$bidangId] ?? null);

        $akunBankId = ($role === 'Bendahara')
            ? 1021
            : ([1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025][$bidangId] ?? null);

        if (!$akunKasId || !$akunBankId) {
            return back()->withErrors(['error' => 'Akun kas atau bank tidak valid.']);
        }

        $akunKasModel = AkunKeuangan::find($akunKasId);
        $akunBankModel = AkunKeuangan::find($akunBankId);

        $saldoKas = $akunKasModel
            ? $lapService->getSaldoAkunSampai($akunKasModel, Carbon::now())
            : 0.0;

        $saldoBank = $akunBankModel
            ? $lapService->getSaldoAkunSampai($akunBankModel, Carbon::now())
            : 0.0;

        $totalKeuanganBidang = $saldoKas + $saldoBank;

        // ==================================
        // 🔹 Total semua bidang (kas+bank)
        // ==================================
        $totalAll = $this->getTotalKasDanBankSemuaBidang();
        $saldoKasTotal = $totalAll['saldoKasTotal'];
        $saldoBankTotal = $totalAll['saldoBankTotal'];
        $totalKeuanganSemuaBidang = $totalAll['totalKeuanganSemuaBidang'];

        // ==================================
        // 🔹 Statistik TRANSAKSI per-bidang / global
        // ==================================
        $jumlahTransaksi = Transaksi::when(
            is_null($bidangId),
            fn($q) => $q->whereNull('bidang_name'),
            fn($q) => $q->where('bidang_name', $bidangId)
        )
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->count();

        $jumlahPiutang = Piutang::when(
            is_null($bidangId),
            fn($q) => $q->whereNull('bidang_name'),
            fn($q) => $q->where('bidang_name', $bidangId)
        )
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        // Di controller khusus Bendahara, bidangName bisa null (konsolidasi)
        $saldoHutangPerantara = LaporanKeuanganService::getSaldoPerAkun(50016, null);

        $jumlahHutangPerantara = Transaksi::where('kode_transaksi', 'like', 'TRF-%')
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->whereIn('parent_akun_id', [1011, 1021])
            ->sum('amount');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::when(
            is_null($bidangId),
            fn($q) => $q->whereNull('bidang_name'),
            fn($q) => $q->where('bidang_name', $bidangId)
        )
            ->sum('jumlah');

        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->when(
                is_null($bidangId),
                fn($q) => $q->whereNull('bidang_name'),
                fn($q) => $q->where('bidang_name', $bidangId)
            )
            ->sum('amount');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->when(
                is_null($bidangId),
                fn($q) => $q->whereNull('bidang_name'),
                fn($q) => $q->where('bidang_name', $bidangId)
            )
            ->sum('amount');

        $jumlahHutang = Hutang::when(
            is_null($bidangId),
            fn($q) => $q->whereNull('bidang_name'),
            fn($q) => $q->where('bidang_name', $bidangId)
        )
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $hutangJatuhTempo = Hutang::where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(7))
            ->count();

        // === Biaya & pendapatan dinamis (per "bidang aktif") ===
        $bidangName = $bidangId;

        // 🔹 Pendapatan per-kategori (COA baru 201–207)
        $jumlahPendapatanPMB = $this->sumTransaksiByParent(201, $bidangName);
        $jumlahPendapatanSPP = $this->sumTransaksiByParent(202, $bidangName);
        $jumlahPendapatanLainPendidikan = $this->sumTransaksiByParent(203, $bidangName);
        $jumlahPendapatanInfaqTidakTerikat = $this->sumTransaksiByParent(204, $bidangName);
        $jumlahPendapatanInfaqTerikat = $this->sumTransaksiByParent(205, $bidangName);
        $jumlahPendapatanUsaha = $this->sumTransaksiByParent(206, $bidangName);
        $jumlahPendapatanBendaharaUmum = $this->sumTransaksiByParent(207, $bidangName);

        // 🔹 Donasi (dipakai di view lama) = Infaq Tidak Terikat + Infaq Terikat
        $jumlahDonasi = $jumlahPendapatanInfaqTidakTerikat + $jumlahPendapatanInfaqTerikat;

        $jumlahPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)
            ->when(
                is_null($bidangName),
                fn($q) => $q->whereNull('bidang_name'),
                fn($q) => $q->where('bidang_name', $bidangName)
            )
            ->sum('amount');

        $jumlahBebanGaji = $this->sumTransaksiByParent(302, $bidangName);
        $jumlahBiayaOperasional = $this->sumTransaksiByParent(303, $bidangName);
        $jumlahBiayaKegiatanSiswa = $this->sumTransaksiByParent(304, $bidangName);
        $jumlahBiayaPemeliharaan = $this->sumTransaksiByParent(305, $bidangName);
        $jumlahBiayaSosial = $this->sumTransaksiByParent(306, $bidangName);
        $jumlahBiayaPerlengkapanExtra = $this->sumTransaksiByParent(307, $bidangName);
        $jumlahBiayaSeragam = $this->sumTransaksiByParent(308, $bidangName);
        $jumlahBiayaPeningkatanSDM = $this->sumTransaksiByParent(309, $bidangName);
        $jumlahBiayadibayardimuka = $this->sumTransaksiByParent(310, $bidangName);

        // === TOTAL (AKUMULASI YAYASAN: seluruh bidang + bendahara) ===
        // Piutang konsolidasi (semua bidang) dari Ledger PSAK
        $piutangLedger = LaporanKeuanganService::getSaldoByGroup(
            config('akun.group_piutang', 103), // root Piutang
            null                               // null = semua bidang (konsolidasi)
        );
        $totalPendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');
        $totalTanahBangunan = Transaksi::where('akun_keuangan_id', 104)->sum('amount');
        $totalInventaris = Transaksi::where('akun_keuangan_id', 105)->sum('amount');
        $totalHutang = Hutang::where('status', 'belum_lunas')->sum('jumlah');

        // 🔹 Total pendapatan per-kategori tanpa filter bidang
        $totalPendapatanPMB = $this->sumTransaksiByParent(201, null);
        $totalPendapatanSPP = $this->sumTransaksiByParent(202, null);
        $totalPendapatanLainPendidikan = $this->sumTransaksiByParent(203, null);
        $totalPendapatanInfaqTidakTerikat = $this->sumTransaksiByParent(204, null);
        $totalPendapatanInfaqTerikat = $this->sumTransaksiByParent(205, null);
        $totalPendapatanUsaha = $this->sumTransaksiByParent(206, null);
        $totalPendapatanBendaharaUmum = $this->sumTransaksiByParent(207, null);

        // 🔹 TotalDonasi (variabel lama yang dipakai di Blade) = Infaq Tidak Terikat + Terikat
        $totalDonasi = $totalPendapatanInfaqTidakTerikat + $totalPendapatanInfaqTerikat;

        $totalPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)->sum('amount');
        $totalBebanGaji = $this->sumTransaksiByParent(302, null);
        $totalBiayaOperasional = $this->sumTransaksiByParent(303, null);
        $totalBiayaKegiatan = $this->sumTransaksiByParent(304, null);
        $totalBiayaKonsumsi = $this->sumTransaksiByParent(305, null);
        $totalBiayaPemeliharaan = $this->sumTransaksiByParent(306, null);
        $totalPengeluaranTerikat = $this->sumTransaksiByParent(307, null);
        $totalBiayaLainLain = $this->sumTransaksiByParent(308, null);
        $totalPengeluaranBendahara = $this->sumTransaksiByParent(309, null);

        return view('bendahara.index', compact(
            'saldoKas',
            'saldoBank',
            'totalKeuanganBidang',
            'saldoKasTotal',
            'saldoBankTotal',
            'totalKeuanganSemuaBidang',
            'jumlahTransaksi',
            'jumlahPiutang',
            'jumlahHutangPerantara',
            'saldoHutangPerantara',
            'jumlahPendapatanBelumDiterima',
            'jumlahTanahBangunan',
            'jumlahInventaris',
            'jumlahHutang',
            'hutangJatuhTempo',
            'jumlahDonasi',
            'jumlahPenyusutanAsset',
            'jumlahBebanGaji',
            'jumlahBiayaOperasional',
            'jumlahBiayaKegiatanSiswa',
            'jumlahBiayaPemeliharaan',
            'jumlahBiayaSosial',
            'jumlahBiayaPerlengkapanExtra',
            'jumlahBiayaSeragam',
            'jumlahBiayaPeningkatanSDM',
            'jumlahBiayadibayardimuka',
            'piutangLedger',
            'totalPendapatanBelumDiterima',
            'totalTanahBangunan',
            'totalInventaris',
            'totalHutang',
            'totalDonasi',
            'totalPenyusutanAsset',
            'totalBebanGaji',
            'totalBiayaOperasional',
            'totalBiayaKegiatan',
            'totalBiayaKonsumsi',
            'totalBiayaPemeliharaan',
            'totalPengeluaranTerikat',
            'totalBiayaLainLain',
            'totalPengeluaranBendahara',
            'totalPendapatanPMB',
            'totalPendapatanSPP',
            'totalPendapatanLainPendidikan',
            'totalPendapatanInfaqTidakTerikat',
            'totalPendapatanInfaqTerikat',
            'totalPendapatanUsaha',
            'totalPendapatanBendaharaUmum'
        ));
    }

    // ========================
    // FUNGSI LAIN (tidak diubah)
    // ========================

    private function calculateKasForBidang($bidang_id)
    {
        $totalDebit = Ledger::where('akun_keuangan_id', 101)
            ->whereHas('transaksi', function ($query) use ($bidang_id) {
                $query->where('bidang_name', $bidang_id);
            })
            ->sum('debit');

        $totalCredit = Ledger::where('akun_keuangan_id', 101)
            ->whereHas('transaksi', function ($query) use ($bidang_id) {
                $query->where('bidang_name', $bidang_id);
            })
            ->sum('credit');

        return $totalDebit - $totalCredit;
    }

    private function calculateTotalKas()
    {
        $bidangNames = User::whereNotNull('bidang_name')->pluck('bidang_name');

        $totalKas = $bidangNames->sum(function ($bidangName) {
            return Transaksi::where('akun_keuangan_id', 101)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last()?->saldo ?? 0;
        });

        return $totalKas;
    }

    private function calculateTotalKeuanganBidang()
    {
        $bidangNames = User::whereNotNull('bidang_name')->pluck('bidang_name');
        $totalKeuangan = 0;

        foreach ($bidangNames as $bidangName) {
            $lastSaldo101 = Transaksi::where('akun_keuangan_id', 101)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last()?->saldo ?? 0;

            $lastSaldo102 = Transaksi::where('akun_keuangan_id', 102)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last()?->saldo ?? 0;

            $totalKeuangan += $lastSaldo101 + $lastSaldo102;
        }

        return $totalKeuangan;
    }

    public function detailData(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id'); // bisa 'hutang-perantara' atau int
        $type = $request->input('type');

        // ==============================
        // 🔹 MODE KHUSUS: Hutang Perantara
        // ==============================
        if ($parentAkunId === 'hutang-perantara') {

            $akunHutangPerantaraId = config('akun.hutang_perantara_bidang', 50016);

            $query = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])
                ->where('parent_akun_id', $akunHutangPerantaraId)
                ->when($type, fn($q) => $q->where('type', $type))
                ->orderBy('tanggal_transaksi', 'asc');

        } else {
            // ==============================
            // 🔹 MODE NORMAL: kelompok akun
            // ==============================
            $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)
                ->pluck('id')
                ->toArray();

            $query = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])
                ->whereIn('parent_akun_id', $subAkunIds)
                ->when($type, fn($q) => $q->where('type', $type))
                ->orderBy('tanggal_transaksi', 'asc');
        }

        return DataTables::of($query)
            ->editColumn('tanggal_transaksi', function ($row) {
                return Carbon::parse($row->tanggal_transaksi)->format('d-m-Y');
            })
            ->addColumn('akun_keuangan', function ($row) {
                return optional($row->akunKeuangan)->nama_akun ?? '-';
            })
            ->addColumn('parent_akun_keuangan', function ($row) {
                return optional($row->parentAkunKeuangan)->nama_akun ?? '-';
            })
            // biarkan amount tetap numerik, biar bisa di-format di JS
            ->make(true);
    }

    public function showDetailBendahara(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id'); // bisa 'hutang-perantara' atau ID int
        $type = $request->input('type');

        $parentAkun = null;

        if ($parentAkunId === 'hutang-perantara') {
            // Biar di judul bisa tetap pakai $parentAkun->nama_akun
            $parentAkun = (object) ['nama_akun' => 'Hutang Perantara – Bidang'];
        } elseif (is_numeric($parentAkunId)) {
            $parentAkun = AkunKeuangan::find($parentAkunId);
        }

        return view('bendahara.detail', [
            'parentAkunId' => $parentAkunId,
            'parentAkun' => $parentAkun,
            'type' => $type,
        ]);
    }

}

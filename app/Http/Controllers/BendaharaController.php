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
use App\Services\DashboardService;
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
    public function index(DashboardService $dashboardService, LaporanKeuanganService $lapService)
    {
        // ==========================
        // Periode (YTD default)
        // ==========================
        $startDate = request()->filled('start_date')
            ? Carbon::parse(request('start_date'))->startOfDay()
            : now()->startOfYear()->startOfDay();

        $endDate = request()->filled('end_date')
            ? Carbon::parse(request('end_date'))->endOfDay()
            : now()->endOfDay();

        $period = ['start' => $startDate, 'end' => $endDate];

        // ==========================
        // Cards dinamis (DB-driven)
        // ==========================
        $cards = $dashboardService->getCards('BENDAHARA', null, $period);

        // Optional: kalau Yayasan juga mau pakai cards dinamis
        $cardsYayasan = $dashboardService->getCards('YAYASAN', null, $period);

        $jumlahTransaksiBendahara = Transaksi::query()
            ->whereNull('bidang_name')
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->count();

        // ==========================
        // Perantara (Bendahara: KONSOLIDASI, tanpa filter bidang)
        // ==========================
        $akunPiutangPerantaraId = config('akun.piutang_perantara', 1033);          // debit-normal
        $akunHutangPerantaraId  = config('akun.hutang_perantara_bidang', 50016);  // kredit-normal

        $trfScopeBendahara = function ($q) use ($startDate, $endDate) {
            $q->where('kode_transaksi', 'like', 'TRF-%')
                ->where('kode_transaksi', 'not like', '%-LAWAN')
                ->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
            // ⛔ tidak ada filter bidang_name (konsolidasi)
        };

        // 1) Piutang Perantara (saldo_normal = debit) => D - C
        $rowPiutang = Ledger::query()
            ->where('akun_keuangan_id', $akunPiutangPerantaraId)
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate) {
                $q->whereNull('bidang_name') // ✅ BENDAHARA ONLY
                    ->where('kode_transaksi', 'like', 'TRF-%')
                    ->where('kode_transaksi', 'not like', '%-LAWAN')
                    ->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
            })
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        $saldoPiutangPerantara = (float) $rowPiutang->d - (float) $rowPiutang->c;

        $rowHutang = Ledger::query()
            ->where('akun_keuangan_id', $akunHutangPerantaraId)
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate) {
                $q->whereNull('bidang_name') // ✅ BENDAHARA ONLY
                    ->where('kode_transaksi', 'like', 'TRF-%')
                    ->where('kode_transaksi', 'not like', '%-LAWAN')
                    ->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
            })
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        // saldo_normal = kredit → C - D
        $saldoHutangPerantara = (float) $rowHutang->c - (float) $rowHutang->d;


        $akunKasBendahara  = AkunKeuangan::find(1011);
        $akunBankBendahara = AkunKeuangan::find(1021);

        $saldoKas  = $akunKasBendahara  ? (float) $lapService->getSaldoAkunSampai($akunKasBendahara, $endDate) : 0.0;
        $saldoBank = $akunBankBendahara ? (float) $lapService->getSaldoAkunSampai($akunBankBendahara, $endDate) : 0.0;


        $totalKeuanganBendahara = $saldoKas + $saldoBank + $saldoPiutangPerantara;

        // ==========================
        // ✅ KONSOLIDASI YAYASAN (Kas/Bank seluruh bidang + bendahara)
        // ==========================
        $kasIdsAll  = [1011, 1012, 1013, 1014, 1015];
        $bankIdsAll = [1021, 1022, 1023, 1024, 1025];

        $saldoKasTotal = 0.0;
        foreach ($kasIdsAll as $id) {
            $akun = AkunKeuangan::find($id);
            if (!$akun) continue;
            $saldoKasTotal += (float) $lapService->getSaldoAkunSampai($akun, $endDate);
        }

        $saldoBankTotal = 0.0;
        foreach ($bankIdsAll as $id) {
            $akun = AkunKeuangan::find($id);
            if (!$akun) continue;
            $saldoBankTotal += (float) $lapService->getSaldoAkunSampai($akun, $endDate);
        }

        // Definisi “Nilai Kekayaan Yayasan” versi minimal (kas+bank)
        $totalKeuanganSemuaBidang = $saldoKasTotal + $saldoBankTotal;

        return view('bendahara.index', compact(
            'period',
            'cards',
            'cardsYayasan',
            'jumlahTransaksiBendahara',

            // bendahara aktif
            'saldoKas',
            'saldoBank',
            'saldoPiutangPerantara',
            'saldoHutangPerantara',
            'totalKeuanganBendahara',

            // yayasan konsolidasi
            'saldoKasTotal',
            'saldoBankTotal',
            'totalKeuanganSemuaBidang'
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
        $parentAkunId = $request->input('parent_akun_id');
        $type = $request->input('type');

        // periode (opsional)
        $start = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : null;

        $end = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : null;

        // ==========================
        // MODE KHUSUS: PERANTARA (BENDAHARA ONLY)
        // ==========================
        if (in_array($parentAkunId, ['piutang-perantara', 'hutang-perantara'], true)) {

            $map = [
                'piutang-perantara' => config('akun.piutang_perantara', 1033),
                'hutang-perantara'  => config('akun.hutang_perantara_bidang', 50016),
            ];

            $akunPerantaraId = $map[$parentAkunId];

            $query = Ledger::with(['akunKeuangan', 'transaksi', 'transaksi.parentAkunKeuangan'])
                ->where('akun_keuangan_id', $akunPerantaraId)
                ->whereHas('transaksi', function ($q) use ($type, $start, $end) {

                    $q->where('kode_transaksi', 'like', 'TRF-%')
                        ->where('kode_transaksi', 'not like', '%-LAWAN')

                        // ✅ Bendahara-only: jangan ambil transaksi bidang
                        ->whereNull('bidang_name');

                    if (!empty($type)) {
                        $q->where('type', $type);
                    }

                    if ($start && $end) {
                        $q->whereBetween('tanggal_transaksi', [$start, $end]);
                    }
                })
                ->orderByDesc('transaksi_id');

            return DataTables::of($query)
                ->addColumn('tanggal_transaksi', function ($row) {
                    $tgl = optional($row->transaksi)->tanggal_transaksi;
                    return $tgl ? Carbon::parse($tgl)->format('d-m-Y') : '-';
                })
                ->addColumn('kode_transaksi', fn($row) => optional($row->transaksi)->kode_transaksi ?? '-')
                ->addColumn('type', fn($row) => optional($row->transaksi)->type ?? '-')
                ->addColumn('akun_keuangan', fn($row) => $row->akunKeuangan->nama_akun ?? 'N/A')

                // ✅ INI WAJIB ADA (agar JS DataTables tidak error)
                ->addColumn('parent_akun_keuangan', function ($row) {
                    return optional(optional($row->transaksi)->parentAkunKeuangan)->nama_akun ?? '-';
                })

                ->addColumn('deskripsi', fn($row) => optional($row->transaksi)->deskripsi ?? '-')
                ->addColumn('debit', fn($row) => (float) ($row->debit ?? 0))
                ->addColumn('credit', fn($row) => (float) ($row->credit ?? 0))
                ->make(true);
        }

        // ==========================
        // MODE NORMAL (LEDGER BENDAHARA)
        // ==========================
        if (!is_numeric($parentAkunId)) {
            return DataTables::of(collect([]))->make(true);
        }

        $parentAkunId = (int) $parentAkunId;

        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();
        if (empty($subAkunIds)) {
            $akun = AkunKeuangan::find($parentAkunId);
            if (!$akun) return DataTables::of(collect([]))->make(true);
            $subAkunIds = [$akun->id];
        }

        $query = Ledger::with(['akunKeuangan', 'transaksi', 'transaksi.parentAkunKeuangan'])
            ->whereIn('akun_keuangan_id', $subAkunIds);
        $query->whereHas('transaksi', function ($q) use ($start, $end) {
            $q->whereNull('bidang_name'); // ✅ Bendahara-only

            if ($start && $end) {
                $q->whereBetween('tanggal_transaksi', [$start, $end]);
            }
        });

        if (!empty($type)) {
            $query->whereHas('transaksi', fn($q) => $q->where('type', $type));
        }

        if ($start && $end) {
            $query->whereHas('transaksi', fn($q) => $q->whereBetween('tanggal_transaksi', [$start, $end]));
        }

        return DataTables::of($query)
            ->addColumn('tanggal_transaksi', function ($row) {
                $tgl = optional($row->transaksi)->tanggal_transaksi;
                return $tgl ? Carbon::parse($tgl)->format('d-m-Y') : '-';
            })
            ->addColumn('kode_transaksi', fn($row) => optional($row->transaksi)->kode_transaksi ?? '-')
            ->addColumn('type', fn($row) => optional($row->transaksi)->type ?? '-')
            ->addColumn('akun_keuangan', fn($row) => $row->akunKeuangan->nama_akun ?? 'N/A')
            ->addColumn('parent_akun_keuangan', fn() => '-') // ✅ TAMBAH INI
            ->addColumn('deskripsi', fn($row) => optional($row->transaksi)->deskripsi ?? '-')
            ->addColumn('debit', fn($row) => (float) ($row->debit ?? 0))
            ->addColumn('credit', fn($row) => (float) ($row->credit ?? 0))
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

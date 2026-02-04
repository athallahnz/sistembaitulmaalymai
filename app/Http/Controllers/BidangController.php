<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\AkunKeuangan;
use App\Models\Piutang;
use App\Models\Hutang;
use App\Models\PendapatanBelumDiterima;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\LaporanService;
use App\Services\DashboardService;
use App\Services\LaporanKeuanganService;
use Yajra\DataTables\Facades\DataTables;

class BidangController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bidang');  // Middleware khusus Bidang
    }

    public function index(DashboardService $dashboardService)
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null;
        $role = $user->role;

        if ($role === 'Bidang' && empty($bidangId)) {
            return back()->withErrors(['error' => 'Bidang user belum ter-set.']);
        }

        $lapService = new LaporanKeuanganService();

        $akunKasId = $role === 'Bendahara'
            ? 1011
            : ([1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015][$bidangId] ?? null);

        $akunBankId = $role === 'Bendahara'
            ? 1021
            : ([1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025][$bidangId] ?? null);

        if (!$akunKasId || !$akunBankId) {
            return back()->withErrors(['error' => 'Akun kas atau bank tidak valid.']);
        }

        $startDate = request()->filled('start_date')
            ? Carbon::parse(request('start_date'))->startOfDay()
            : now()->startOfYear()->startOfDay();

        $endDate = request()->filled('end_date')
            ? Carbon::parse(request('end_date'))->endOfDay()
            : now()->endOfDay();

        $trfScope = function ($q) use ($role, $bidangId, $startDate, $endDate) {
            $q->where('kode_transaksi', 'like', 'TRF-%')
                ->where('kode_transaksi', 'not like', '%-LAWAN')
                ->whereBetween('tanggal_transaksi', [$startDate, $endDate]);

            // Filter bidang hanya untuk role Bidang
            if ($role === 'Bidang' && $bidangId) {
                $q->where('bidang_name', $bidangId);
            }
        };

        $period = ['start' => $startDate, 'end' => $endDate];

        $akunKasModel  = AkunKeuangan::find($akunKasId);
        $akunBankModel = AkunKeuangan::find($akunBankId);

        $saldoKas = $akunKasModel ? $lapService->getSaldoAkunSampai($akunKasModel, $endDate) : 0.0;
        $saldoBank = $akunBankModel ? $lapService->getSaldoAkunSampai($akunBankModel, $endDate) : 0.0;

        $jumlahTransaksi = Transaksi::query()
            ->where('bidang_name', $bidangId)
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->count();

        $piutangLedger = LaporanKeuanganService::getSaldoByGroup(config('akun.group_piutang'), $bidangId);

        $piutangMurid = Piutang::query()
            ->where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        // ==========================
        // Perantara (ambil dari LEDGER, filter TRF + periode + bidang)
        // ==========================
        $akunPiutangPerantaraId = 1033;   // Piutang Bendahara Umum (saldo_normal = debit)
        $akunHutangPerantaraId  = 50016;  // Hutang Perantara – Bidang (saldo_normal = kredit)

        $trfScope = function ($q) use ($role, $bidangId, $startDate, $endDate) {
            $q->where('kode_transaksi', 'like', 'TRF-%')
                ->where('kode_transaksi', 'not like', '%-LAWAN')
                ->whereBetween('tanggal_transaksi', [$startDate, $endDate]);

            if ($role === 'Bidang' && $bidangId) {
                $q->where('bidang_name', $bidangId);
            }
        };

        // 1) Piutang Perantara (D) => D - C
        $rowPiutang = Ledger::query()
            ->where('akun_keuangan_id', $akunPiutangPerantaraId)
            ->whereHas('transaksi', $trfScope)
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        $saldoPiutangPerantara = (float)$rowPiutang->d - (float)$rowPiutang->c;

        // 2) Hutang Perantara (K) => C - D
        $rowHutang = Ledger::query()
            ->where('akun_keuangan_id', $akunHutangPerantaraId)
            ->whereHas('transaksi', $trfScope)
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        $saldoHutangPerantara = (float)$rowHutang->c - (float)$rowHutang->d;

        // Optional: jika Anda masih butuh nama lama
        $jumlahPiutangPerantara = $saldoPiutangPerantara;
        $jumlahHutangPerantara  = $saldoHutangPerantara;

        // Total (sesuai definisi Anda)
        $totalKeuanganBidang = $saldoKas + $saldoBank + $saldoPiutangPerantara;


        $jumlahTanahBangunan = LaporanKeuanganService::getSaldoByGroup(104, $bidangId);
        $jumlahInventaris    = LaporanKeuanganService::getSaldoByGroup(105, $bidangId);

        $jumlahHutang = Hutang::query()
            ->where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $hutangJatuhTempo = Hutang::query()
            ->where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', now()->addDays(7))
            ->count();

        $pendapatanBelumDiterimaPMB = LaporanKeuanganService::getSaldoByGroup(50012, $bidangId);
        $pendapatanBelumDiterimaSPP = LaporanKeuanganService::getSaldoByGroup(50011, $bidangId);

        $hutangProgramSosial = LaporanKeuanganService::getSaldoAkun(5005, $bidangId);

        // DB-driven cards
        $cards = $dashboardService->getCards('BIDANG', (int) $bidangId, $period);

        // optional statik
        $jumlahBiayadibayardimuka = LaporanKeuanganService::getSaldoByGroup(310, $bidangId);

        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id')
            ->whereNotIn('id', [101, 103, 104, 105, 201])
            ->get();

        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray();

        return view('bidang.index', compact(
            'period',
            'startDate',
            'endDate',
            'totalKeuanganBidang',
            'jumlahTransaksi',
            'saldoKas',
            'saldoBank',
            'piutangLedger',
            'piutangMurid',
            'jumlahPiutangPerantara',
            'saldoPiutangPerantara',
            'saldoHutangPerantara',
            'jumlahTanahBangunan',
            'jumlahInventaris',
            'pendapatanBelumDiterimaPMB',
            'pendapatanBelumDiterimaSPP',
            'jumlahHutang',
            'hutangProgramSosial',
            'hutangJatuhTempo',
            'cards',
            'jumlahBiayadibayardimuka',
            'akunTanpaParent',
            'akunDenganParent'
        ));
    }

    public function showDetail(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id');
        $type = $request->input('type');
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null;
        $role = $user->role ?? 'Bidang';

        $parentAkun = null;
        $labelAkun = null;

        if ($parentAkunId === 'piutang-perantara') {
            $labelAkun = 'Piutang Perantara';
            $parentAkun = null;
        } elseif ($parentAkunId === 'hutang-perantara') {
            $labelAkun = 'Hutang Perantara';
            $parentAkun = null;
        } else {
            $parentAkun = AkunKeuangan::find($parentAkunId);
            $labelAkun = $parentAkun ? $parentAkun->nama_akun : null;
        }

        return view('bidang.detail', [
            'parentAkunId' => $parentAkunId,
            'type' => $type,
            'parentAkun' => $parentAkun,
            'bidangId' => $bidangId,
            'labelAkun' => $labelAkun,
            'role' => $role,
        ]);
    }

    public function getDetailData(Request $request)
    {
        $user = auth()->user();
        $role = $user->role ?? 'Bidang';
        $bidangName = $user->bidang_name; // diasumsikan 1,2,3,4
        $parentAkunId = $request->input('parent_akun_id');
        $type = $request->input('type');

        // Helper: periode optional (kalau Anda kirim start_date/end_date dari detail page)
        $applyPeriod = function ($q) use ($request) {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $q->whereBetween('tanggal_transaksi', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ]);
            }
            return $q;
        };

        // ==========================
        // 🔹 MODE KHUSUS: Piutang/Hutang Perantara (pakai Transaksi, filter by parent_akun_id)
        // ==========================
        // ==========================
        // 🔹 MODE KHUSUS: Piutang/Hutang Perantara (pakai Ledger, filter by akun_keuangan_id)
        // ==========================
        if (in_array($parentAkunId, ['piutang-perantara', 'hutang-perantara'], true)) {

            $map = [
                'piutang-perantara' => 1033,  // debit normal
                'hutang-perantara'  => 50016, // kredit normal
            ];

            $akunPerantaraId = $map[$parentAkunId];

            $query = Ledger::with(['akunKeuangan', 'transaksi'])
                ->where('akun_keuangan_id', $akunPerantaraId)
                ->whereHas('transaksi', function ($q) use ($request, $role, $bidangName, $type) {

                    $q->where('kode_transaksi', 'like', 'TRF-%')
                        ->where('kode_transaksi', 'not like', '%-LAWAN');

                    // filter bidang untuk role Bidang
                    if ($role === 'Bidang' && $bidangName) {
                        $q->where('bidang_name', $bidangName);
                    }

                    // filter type
                    if (!empty($type)) {
                        $q->where('type', $type);
                    }

                    // filter periode jika dikirim
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $q->whereBetween('tanggal_transaksi', [
                            Carbon::parse($request->start_date)->startOfDay(),
                            Carbon::parse($request->end_date)->endOfDay(),
                        ]);
                    }
                });

            return DataTables::of($query)
                ->addColumn('tanggal', fn($row) => optional($row->transaksi)->tanggal_transaksi)
                ->addColumn('kode_transaksi', fn($row) => optional($row->transaksi)->kode_transaksi)
                ->addColumn('type', fn($row) => optional($row->transaksi)->type)
                ->addColumn('akun_keuangan', fn($row) => $row->akunKeuangan->nama_akun ?? 'N/A')
                ->addColumn('deskripsi', fn($row) => optional($row->transaksi)->deskripsi)
                ->addColumn('debit', fn($row) => $row->debit ?? 0)
                ->addColumn('credit', fn($row) => $row->credit ?? 0)
                ->make(true);
        }

        // ==========================
        // 🔹 MODE NORMAL: Detail per kelompok akun (pakai Ledger)
        // ==========================

        // 1) Ambil semua akun anak dari group (201, 202, 103, dst)
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)
            ->pluck('id')
            ->toArray();

        // 2) Kalau TIDAK ada anak → anggap ini akun tunggal (leaf), pakai ID-nya sendiri
        if (empty($subAkunIds)) {
            $akun = AkunKeuangan::find($parentAkunId);
            if ($akun) {
                $subAkunIds = [$akun->id];
            } else {
                // Kalau akun-nya pun nggak ada, kembalikan tabel kosong
                return DataTables::of(collect([]))->make(true);
            }
        }

        $query = Ledger::with(['akunKeuangan', 'transaksi'])
            ->whereIn('akun_keuangan_id', $subAkunIds);

        // Filter bidang (untuk role Bidang)
        if ($role === 'Bidang' && $bidangName) {
            $query->whereHas('transaksi', function ($q) use ($bidangName) {
                $q->where('bidang_name', $bidangName);
            });
        }

        // Filter type kalau dikirim (penerimaan/pengeluaran/pengakuan_pendapatan/dsb)
        if (!empty($type)) {
            $query->whereHas('transaksi', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        return DataTables::of($query)
            ->addColumn('tanggal', function ($row) {
                return optional($row->transaksi)->tanggal_transaksi;
            })
            ->addColumn('kode_transaksi', function ($row) {
                return optional($row->transaksi)->kode_transaksi;
            })
            ->addColumn('type', function ($row) {
                return optional($row->transaksi)->type;
            })
            ->addColumn('akun_keuangan', function ($row) {
                return $row->akunKeuangan->nama_akun ?? 'N/A';
            })
            ->addColumn('deskripsi', function ($row) {
                return optional($row->transaksi)->deskripsi;
            })
            ->addColumn('debit', function ($row) {
                return $row->debit ?? 0;
            })
            ->addColumn('credit', function ($row) {
                return $row->credit ?? 0;
            })
            ->make(true);
    }
}

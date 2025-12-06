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
use App\Services\LaporanKeuanganService;
use Yajra\DataTables\Facades\DataTables;

class BidangController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bidang');  // Middleware khusus Bidang
    }

    public function index()
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null; // integer id bidang
        $role = $user->role;                // Harusnya 'Bidang' (middleware)

        $lapService = new LaporanKeuanganService();

        // ==================================
        // 🔹 Map akun Kas & Bank per Bidang
        // ==================================
        // Walau controller ini khusus Bidang, tetap kubiarkan branch Bendahara
        // biar generic kalau suatu saat dipakai ulang.
        $akunKasId = $role === 'Bendahara'
            ? 1011
            : ([1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015][$bidangId] ?? null);

        $akunBankId = $role === 'Bendahara'
            ? 1021
            : ([1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025][$bidangId] ?? null);

        if (!$akunKasId || !$akunBankId) {
            return back()->withErrors(['error' => 'Akun kas atau bank tidak valid.']);
        }

        // ==================================
        // 🔹 Saldo Kas & Bank via LEDGER (PSAK 45)
        // ==================================
        // Ini akan otomatis "per bidang" karena:
        //   - akun Kas/Bank tiap bidang beda id-nya
        //   - Bidang hanya punya akses ke akun kas/bank miliknya sendiri
        $akunKasModel = AkunKeuangan::find($akunKasId);
        $akunBankModel = AkunKeuangan::find($akunBankId);

        $saldoKas = $akunKasModel
            ? $lapService->getSaldoAkunSampai($akunKasModel, Carbon::now())
            : 0.0;

        $saldoBank = $akunBankModel
            ? $lapService->getSaldoAkunSampai($akunBankModel, Carbon::now())
            : 0.0;

        // ==================================
        // 🔹 Statistik per Bidang
        // ==================================
        $jumlahTransaksi = Transaksi::where('bidang_name', $bidangId)
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->count();

        $piutangLedger = LaporanKeuanganService::getSaldoByGroup(config('akun.group_piutang'), $bidangId);

        $piutangMurid = Piutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');


        $saldoPiutangPerantara = LaporanKeuanganService::getSaldoPerAkun(1034, $bidangId);

        $jumlahPiutangPerantara = Transaksi::where('kode_transaksi', 'like', 'TRF-%')
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->where('bidang_name', $bidangId)
            ->whereNotIn('parent_akun_id', [$akunKasId, $akunBankId])
            ->sum('amount');

        $totalKeuanganBidang = $saldoKas + $saldoBank + $jumlahPiutangPerantara;

        $jumlahTanahBangunan = LaporanKeuanganService::getSaldoByGroup(104, $bidangId);
        $jumlahInventaris = LaporanKeuanganService::getSaldoByGroup(105, $bidangId);

        $jumlahHutang = Hutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $hutangJatuhTempo = Hutang::where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(7))
            ->count();

        // Pendapatan & Biaya (berdasar parent_akun_id – COA baru)
        $bidangName = $bidangId;

        // Pendapatan Belum Diterima – PMB
        $pendapatanBelumDiterimaPMB = LaporanKeuanganService::getSaldoByGroup(50012, $bidangId);

        // Pendapatan Belum Diterima – SPP
        $pendapatanBelumDiterimaSPP = LaporanKeuanganService::getSaldoByGroup(50011, $bidangId);


        // 🔹 Pendapatan per kategori (201–207)
        $jumlahPendapatanPMB = LaporanKeuanganService::getSaldoByGroup(201, $bidangName);
        $jumlahPendapatanSPP = LaporanKeuanganService::getSaldoByGroup(202, $bidangName);
        $jumlahPendapatanLainPendidikan = LaporanKeuanganService::getSaldoByGroupBidang(203, $bidangName);
        $jumlahPendapatanInfaqTidakTerikat = LaporanKeuanganService::getSaldoByGroupBidang(204, $bidangName);
        $jumlahPendapatanInfaqTerikat = LaporanKeuanganService::getSaldoByGroupBidang(205, $bidangName);
        $jumlahPendapatanUsaha = LaporanKeuanganService::getSaldoByGroupBidang(206, $bidangName);
        $jumlahPendapatanBendaharaUmum = LaporanKeuanganService::getSaldoByGroupBidang(207, $bidangName);

        // 🔹 Donasi (dipakai di kartu lama) = Infaq Tidak Terikat + Terikat
        $jumlahDonasi = $jumlahPendapatanInfaqTidakTerikat + $jumlahPendapatanInfaqTerikat;

        // 🔹 Beban (301–309)
        $jumlahPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        $jumlahBebanGaji = LaporanKeuanganService::getSaldoByGroupBidang(302, $bidangName);
        $jumlahBiayaOperasional = LaporanKeuanganService::getSaldoByGroupBidang(303, $bidangName);
        $jumlahBiayaKegiatan = LaporanKeuanganService::getSaldoByGroupBidang(304, $bidangName);
        $jumlahBiayaKonsumsi = LaporanKeuanganService::getSaldoByGroupBidang(305, $bidangName);
        $jumlahBiayaPemeliharaan = LaporanKeuanganService::getSaldoByGroupBidang(306, $bidangName);
        $jumlahPengeluaranTerikat = LaporanKeuanganService::getSaldoByGroupBidang(307, $bidangName);
        $jumlahBiayaLainLain = LaporanKeuanganService::getSaldoByGroupBidang(308, $bidangName);
        $jumlahPengeluaranBendahara = LaporanKeuanganService::getSaldoByGroupBidang(309, $bidangName);

        // 🔹 Biaya dibayar di muka (310)
        $jumlahBiayadibayardimuka = LaporanKeuanganService::getSaldoByGroup(310, $bidangName);

        // ==================================
        // 🔹 Struktur akun (opsional buat view)
        // ==================================
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
            'totalKeuanganBidang',
            'jumlahTransaksi',
            'saldoKas',
            'saldoBank',
            'piutangLedger',
            'piutangMurid',
            'jumlahPiutangPerantara',
            'saldoPiutangPerantara',
            'jumlahTanahBangunan',
            'jumlahInventaris',

            // Liabilitas
            'pendapatanBelumDiterimaPMB',
            'pendapatanBelumDiterimaSPP',
            'jumlahHutang',
            'hutangJatuhTempo',

            // 🔹 Pendapatan (201–207)
            'jumlahPendapatanPMB',
            'jumlahPendapatanSPP',
            'jumlahPendapatanLainPendidikan',
            'jumlahPendapatanInfaqTidakTerikat',
            'jumlahPendapatanInfaqTerikat',
            'jumlahPendapatanUsaha',
            'jumlahPendapatanBendaharaUmum',
            'jumlahDonasi',

            // 🔹 Beban (301–309) + biaya dibayar di muka
            'jumlahPenyusutanAsset',
            'jumlahBebanGaji',
            'jumlahBiayaOperasional',
            'jumlahBiayaKegiatan',
            'jumlahBiayaKonsumsi',
            'jumlahBiayaPemeliharaan',
            'jumlahPengeluaranTerikat',
            'jumlahBiayaLainLain',
            'jumlahPengeluaranBendahara',
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

        // 🔹 MODE KHUSUS: Piutang Perantara
        if ($parentAkunId === 'piutang-perantara') {
            $labelAkun = 'Piutang Perantara';
            $parentAkun = null;
        } else {
            // Mode normal: parentAkunId = group / parent akun (201, 202, 101, dst.)
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

        // ==========================
        // 🔹 MODE KHUSUS: Piutang Perantara (masih pakai Transaksi)
        // ==========================
        if ($parentAkunId === 'piutang-perantara') {
            $query = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])
                ->where('kode_transaksi', 'like', 'TRF-%')
                ->where('kode_transaksi', 'not like', '%-LAWAN');

            if ($role === 'Bidang' && $bidangName) {
                $query->where('bidang_name', $bidangName);
            }

            if ($type) {
                $query->where('type', $type);
            }

            return DataTables::of($query)
                ->addColumn('tanggal', function ($row) {
                    return $row->tanggal_transaksi;
                })
                ->addColumn('kode_transaksi', function ($row) {
                    return $row->kode_transaksi;
                })
                ->addColumn('type', function ($row) {
                    return $row->type;
                })
                ->addColumn('akun_keuangan', function ($row) {
                    return $row->akunKeuangan->nama_akun ?? 'N/A';
                })
                ->addColumn('deskripsi', function ($row) {
                    return $row->deskripsi;
                })
                // untuk mode ini, kalau mau, kamu bisa pakai amount saja dan set debit/kredit = amount
                ->addColumn('debit', function ($row) {
                    return $row->type === 'penerimaan' ? $row->amount : 0;
                })
                ->addColumn('credit', function ($row) {
                    return $row->type === 'pengeluaran' ? $row->amount : 0;
                })
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


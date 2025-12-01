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

    /**
     * Ambil saldo terakhir dari KOLOM `saldo` untuk akun tertentu (<= cutoff).
     * Tidak mengecualikan baris -LAWAN karena saldo akun bisa muncul di baris lawan.
     * - Non-Bendahara: filter per-bidang + fallback histori lama (NULL)
     * - Bendahara: global
     *
     * (Sekarang TIDAK dipakai untuk Kas/Bank, hanya helper umum kalau nanti dibutuhkan)
     */
    protected function getLastSaldoBySaldoColumn(
        int $akunId,
        string $userRole,
        $bidangValue,
        ?string $tanggalCutoff = null
    ): float {
        if (!$akunId) {
            return 0.0;
        }

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

        $jumlahPiutang = Piutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::where('bidang_name', $bidangId)
            ->sum('jumlah');

        $jumlahPiutangPerantara = Transaksi::where('kode_transaksi', 'like', 'TRF-%')
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            // ⛔ JANGAN parent_akun_id yang isinya akun kas/bank bidang ini sendiri
            ->whereNotIn('parent_akun_id', [$akunKasId, $akunBankId])
            ->sum('amount');

        $totalKeuanganBidang = $saldoKas + $saldoBank + $jumlahPiutangPerantara;

        // Asset langsung (contoh ID: 104, 105)
        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->where('bidang_name', $bidangId)
            ->sum('amount');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->where('bidang_name', $bidangId)
            ->sum('amount');

        $jumlahHutang = Hutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $hutangJatuhTempo = Hutang::where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(7))
            ->count();

        // Pendapatan & Biaya (berdasar parent_akun_id – COA baru)
        $bidangName = $bidangId;

        // 🔹 Pendapatan per kategori (201–207)
        $jumlahPendapatanPMB = $this->sumTransaksiByParent(201, $bidangName);
        $jumlahPendapatanSPP = $this->sumTransaksiByParent(202, $bidangName);
        $jumlahPendapatanLainPendidikan = $this->sumTransaksiByParent(203, $bidangName);
        $jumlahPendapatanInfaqTidakTerikat = $this->sumTransaksiByParent(204, $bidangName);
        $jumlahPendapatanInfaqTerikat = $this->sumTransaksiByParent(205, $bidangName);
        $jumlahPendapatanUsaha = $this->sumTransaksiByParent(206, $bidangName);
        $jumlahPendapatanBendaharaUmum = $this->sumTransaksiByParent(207, $bidangName);

        // 🔹 Donasi (dipakai di kartu lama) = Infaq Tidak Terikat + Terikat
        $jumlahDonasi = $jumlahPendapatanInfaqTidakTerikat + $jumlahPendapatanInfaqTerikat;

        // 🔹 Beban (301–309)
        $jumlahPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)
            ->where('bidang_name', $bidangName)
            ->sum('amount');

        $jumlahBebanGaji = $this->sumTransaksiByParent(302, $bidangName);
        $jumlahBiayaOperasional = $this->sumTransaksiByParent(303, $bidangName);
        $jumlahBiayaKegiatan = $this->sumTransaksiByParent(304, $bidangName);
        $jumlahBiayaKonsumsi = $this->sumTransaksiByParent(305, $bidangName);
        $jumlahBiayaPemeliharaan = $this->sumTransaksiByParent(306, $bidangName);
        $jumlahPengeluaranTerikat = $this->sumTransaksiByParent(307, $bidangName);
        $jumlahBiayaLainLain = $this->sumTransaksiByParent(308, $bidangName);
        $jumlahPengeluaranBendahara = $this->sumTransaksiByParent(309, $bidangName);

        // 🔹 Biaya dibayar di muka (310)
        $jumlahBiayadibayardimuka = $this->sumTransaksiByParent(310, $bidangName);

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
            'jumlahPiutang',
            'jumlahPiutangPerantara',
            'jumlahPendapatanBelumDiterima',
            'jumlahTanahBangunan',
            'jumlahInventaris',
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

        $transaksiData = collect();
        $parentAkun = null;
        $labelAkun = null;

        // =====================================================
        // 🔹 MODE KHUSUS: Piutang Perantara (mutasi antar bidang)
        // =====================================================
        if ($parentAkunId === 'piutang-perantara') {

            // Map akun Kas & Bank per Bidang (sesuai snippet kamu)
            $akunKasId = $role === 'Bendahara'
                ? 1011
                : ([1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015][$bidangId] ?? null);

            $akunBankId = $role === 'Bendahara'
                ? 1021
                : ([1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025][$bidangId] ?? null);

            $query = Transaksi::query()
                ->where('kode_transaksi', 'like', 'TRF-%')
                ->where('kode_transaksi', 'not like', '%-LAWAN');

            // Filter per bidang (bidang hanya lihat miliknya)
            if ($bidangId) {
                $query->where('bidang_name', $bidangId);
            }

            // Exclude mutasi ke kas/bank internal bidang ini sendiri
            if ($akunKasId && $akunBankId) {
                $query->whereNotIn('parent_akun_id', [$akunKasId, $akunBankId]);
            }

            if ($type) {
                $query->where('type', $type);
            }

            $transaksiData = $query->with(['akunKeuangan', 'parentAkunKeuangan'])->get();

            $parentAkun = null;
            $labelAkun = 'Piutang Perantara';

        } else {
            // =====================================================
            // 🔹 MODE NORMAL: Detail per kelompok akun (kode lama)
            // =====================================================
            $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)
                ->pluck('id')
                ->toArray();

            $transaksiData = Transaksi::when(!empty($subAkunIds), fn($q) => $q->whereIn('parent_akun_id', $subAkunIds))
                ->when($type, fn($q) => $q->where('type', $type))
                ->when($bidangId, fn($q) => $q->where('bidang_name', $bidangId))
                ->with(['akunKeuangan', 'parentAkunKeuangan'])
                ->get();

            $parentAkun = AkunKeuangan::find($parentAkunId);
            $labelAkun = $parentAkun ? $parentAkun->nama_akun : null;
        }

        return view('bidang.detail', [
            'transaksiData' => $transaksiData,
            'parentAkunId' => $parentAkunId,
            'type' => $type,
            'parentAkun' => $parentAkun,
            'bidangId' => $bidangId,
            'labelAkun' => $labelAkun,
        ]);
    }

    public function getDetailData(Request $request)
    {
        $user = auth()->user();
        $role = $user->role ?? 'Bidang';
        $bidangName = $user->bidang_name; // diasumsikan 1,2,3,4
        $parentAkunId = $request->input('parent_akun_id');
        $type = $request->input('type');

        // Base query
        $query = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan']);

        // Bidang hanya lihat miliknya sendiri
        if ($role === 'Bidang') {
            $query->where('bidang_name', $bidangName);
        }

        // ==========================
        // 🔹 MODE KHUSUS: Piutang Perantara
        // ==========================
        if ($parentAkunId === 'piutang-perantara') {
            // Map akun kas & bank per bidang (pakai mapping yang sudah kamu punya)
            $akunKasId = $role === 'Bendahara'
                ? 1011
                : ([1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015][$bidangName] ?? null);

            $akunBankId = $role === 'Bendahara'
                ? 1021
                : ([1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025][$bidangName] ?? null);

            // Hanya mutasi transfer
            $query->where('kode_transaksi', 'like', 'TRF-%')
                ->where('kode_transaksi', 'not like', '%-LAWAN');

            // Exclude mutasi ke kas/bank internal bidang ini sendiri
            if ($akunKasId && $akunBankId) {
                $query->whereNotIn('parent_akun_id', [$akunKasId, $akunBankId]);
            }
        } else {
            // ==========================
            // 🔹 MODE NORMAL: Detail per kelompok akun
            // ==========================
            if ($parentAkunId) {
                // Ambil semua anak dari parent_akun_id ini
                $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)
                    ->pluck('id')
                    ->toArray();

                $query->whereIn('parent_akun_id', $subAkunIds);
            }
        }

        // Filter type kalau dikirim (penerimaan / pengeluaran)
        if ($type) {
            $query->where('type', $type);
        }

        $transaksiData = $query->get();

        return DataTables::of($transaksiData)
            ->addColumn('akun_keuangan', function ($row) {
                return $row->akunKeuangan ? $row->akunKeuangan->nama_akun : 'N/A';
            })
            ->addColumn('parent_akun_keuangan', function ($row) {
                return $row->parentAkunKeuangan ? $row->parentAkunKeuangan->nama_akun : 'N/A';
            })
            ->make(true);
    }

}


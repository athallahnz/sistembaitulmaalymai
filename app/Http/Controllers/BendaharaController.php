<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\AkunKeuangan;
use App\Models\Piutang;
use App\Models\Hutang;
use App\Models\PendapatanBelumDiterima;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\LaporanService;
use Yajra\DataTables\Facades\DataTables;


class BendaharaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bendahara');  // Menggunakan middleware untuk role ketua
    }
    // ===============================================
    // HELPER FUNCTIONS
    // ===============================================

    protected function getSaldoTerakhir(int $akunKeuanganId, $bidangName = null): float
    {
        $query = Transaksi::where('akun_keuangan_id', $akunKeuanganId)
            ->when(is_null($bidangName), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangName), fn($q) => $q->where('bidang_name', $bidangName));

        $row = $query->selectRaw("
                COALESCE(SUM(CASE
                    WHEN type = 'penerimaan' THEN amount
                    WHEN type = 'pengeluaran' THEN -amount
                    ELSE 0 END), 0
                ) AS saldo_akhir
            ")
            ->first();

        return (float) ($row->saldo_akhir ?? 0.0);
    }

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
     * Hitung total kas, bank, dan keuangan untuk semua bidang.
     */

    protected function getTotalKasDanBankSemuaBidang(): array
    {
        // Mapping yang eksplisit & aman (termasuk Bendahara)
        $maps = [
            ['bidang' => null, 'kas' => 1011, 'bank' => 1021], // Bendahara (global)
            ['bidang' => 1, 'kas' => 1012, 'bank' => 1022], // Kemasjidan
            ['bidang' => 2, 'kas' => 1013, 'bank' => 1023], // Pendidikan
            ['bidang' => 3, 'kas' => 1014, 'bank' => 1024], // Sosial
            ['bidang' => 4, 'kas' => 1015, 'bank' => 1025], // Usaha
        ];

        $saldoKasTotal = 0.0;
        $saldoBankTotal = 0.0;

        // (Opsional) simpan rincian per-bidang untuk debugging / dashboard
        $rincian = [];

        foreach ($maps as $m) {
            $bidangName = $m['bidang'];
            $kasId = $m['kas'];
            $bankId = $m['bank'];

            // Hitung saldo dengan fungsi agregasi yang sudah kamu pakai
            $saldoKas = $this->getSaldoTerakhir($kasId, $bidangName);
            $saldoBank = $this->getSaldoTerakhir($bankId, $bidangName);

            $saldoKasTotal += $saldoKas;
            $saldoBankTotal += $saldoBank;

            // Simpan rincian (berguna buat verifikasi)
            $rincian[] = [
                'bidang' => $bidangName, // null = Bendahara
                'akun_kas_id' => $kasId,
                'saldo_kas' => $saldoKas,
                'akun_bank_id' => $bankId,
                'saldo_bank' => $saldoBank,
                'subtotal' => $saldoKas + $saldoBank,
            ];
        }

        return [
            'saldoKasTotal' => $saldoKasTotal,
            'saldoBankTotal' => $saldoBankTotal,
            'totalKeuanganSemuaBidang' => $saldoKasTotal + $saldoBankTotal,
            // 'rincian' => $rincian, // bisa kamu hapus kalau tak perlu
        ];
    }

    // ===============================================
    // INDEX FUNCTION
    // ===============================================

    public function index()
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null;

        // Tentukan akun kas dan bank aktif untuk user
        $akunKas = $user->role === 'Bendahara'
            ? 1011
            : ([1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015][$bidangId] ?? null);

        $akunBank = $user->role === 'Bendahara'
            ? 1021
            : ([1 => 1022, 2 => 1023, 3 => 1024, 4 => 1025][$bidangId] ?? null);

        if (!$akunKas || !$akunBank) {
            return back()->withErrors(['error' => 'Akun kas atau bank tidak valid.']);
        }

        // Saldo kas & bank bidang aktif
        $saldoKas = $this->getSaldoTerakhir($akunKas, $user->role === 'Bendahara' ? null : $bidangId);
        $saldoBank = $this->getSaldoTerakhir($akunBank, $user->role === 'Bendahara' ? null : $bidangId);
        $totalKeuanganBidang = $saldoKas + $saldoBank;

        // === Total semua bidang ===
        $totalAll = $this->getTotalKasDanBankSemuaBidang();
        $saldoKasTotal = $totalAll['saldoKasTotal'];
        $saldoBankTotal = $totalAll['saldoBankTotal'];
        $totalKeuanganSemuaBidang = $totalAll['totalKeuanganSemuaBidang'];

        // === Statistik tambahan ===
        $jumlahTransaksi = Transaksi::when(is_null($bidangId), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangId), fn($q) => $q->where('bidang_name', $bidangId))
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->where('kode_transaksi', 'not like', '%-LAWAN')
            ->count();

        $jumlahPiutang = Piutang::when(is_null($bidangId), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangId), fn($q) => $q->where('bidang_name', $bidangId))
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::when(is_null($bidangId), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangId), fn($q) => $q->where('bidang_name', $bidangId))
            ->sum('jumlah');

        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->when(is_null($bidangId), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangId), fn($q) => $q->where('bidang_name', $bidangId))
            ->sum('amount');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->when(is_null($bidangId), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangId), fn($q) => $q->where('bidang_name', $bidangId))
            ->sum('amount');

        $jumlahHutang = Hutang::when(is_null($bidangId), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangId), fn($q) => $q->where('bidang_name', $bidangId))
            ->where('status', 'belum_lunas')
            ->sum('jumlah');

        $hutangJatuhTempo = Hutang::where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(7))
            ->count();

        // === Biaya & pendapatan dinamis ===
        $bidangName = $bidangId;
        $jumlahDonasi = $this->sumTransaksiByParent(202, $bidangName);
        $jumlahPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)
            ->when(is_null($bidangName), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangName), fn($q) => $q->where('bidang_name', $bidangName))
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
        $totalPiutang = Piutang::sum('jumlah');
        $totalPendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');

        $totalTanahBangunan = Transaksi::where('akun_keuangan_id', 104)->sum('amount');
        $totalInventaris = Transaksi::where('akun_keuangan_id', 105)->sum('amount');

        $totalHutang = Hutang::where('status', 'belum_lunas')->sum('jumlah');

        // Pendapatan & biaya (pakai parent_akun_id, tanpa filter bidang)
        $totalDonasi = $this->sumTransaksiByParent(202, null);
        $totalPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)->sum('amount');
        $totalBebanGaji = $this->sumTransaksiByParent(302, null);
        $totalBiayaOperasional = $this->sumTransaksiByParent(303, null);
        $totalBiayaKegiatanSiswa = $this->sumTransaksiByParent(304, null);
        $totalBiayaPemeliharaan = $this->sumTransaksiByParent(305, null);
        $totalBiayaSosial = $this->sumTransaksiByParent(306, null);
        $totalBiayaPerlengkapanExtra = $this->sumTransaksiByParent(307, null);
        $totalBiayaSeragam = $this->sumTransaksiByParent(308, null);
        $totalBiayaPeningkatanSDM = $this->sumTransaksiByParent(309, null);
        $totalBiayadibayardimuka = $this->sumTransaksiByParent(310, null);

        return view('bendahara.index', compact(
            // ...yang sudah ada...
            'saldoKas',
            'saldoBank',
            'totalKeuanganBidang',
            'saldoKasTotal',
            'saldoBankTotal',
            'totalKeuanganSemuaBidang',
            'jumlahTransaksi',
            'jumlahPiutang',
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

            // ⬇️ total akumulasi yayasan
            'totalPiutang',
            'totalPendapatanBelumDiterima',
            'totalTanahBangunan',
            'totalInventaris',
            'totalHutang',
            'totalDonasi',
            'totalPenyusutanAsset',
            'totalBebanGaji',
            'totalBiayaOperasional',
            'totalBiayaKegiatanSiswa',
            'totalBiayaPemeliharaan',
            'totalBiayaSosial',
            'totalBiayaPerlengkapanExtra',
            'totalBiayaSeragam',
            'totalBiayaPeningkatanSDM',
            'totalBiayadibayardimuka'
        ));
    }


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
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last()?->saldo ?? 0; // Ambil nilai saldo terakhir atau 0 jika tidak ada data
        });

        return $totalKas;
    }

    private function calculateTotalKeuanganBidang()
    {
        $bidangNames = User::whereNotNull('bidang_name')->pluck('bidang_name');
        $totalKeuangan = 0;

        foreach ($bidangNames as $bidangName) {
            // Ambil saldo terakhir untuk akun 101
            $lastSaldo101 = Transaksi::where('akun_keuangan_id', 101)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last() // Ambil baris terakhir (data terbaru)
                    ?->saldo ?? 0; // Ambil nilai kolom 'saldo' atau default 0 jika tidak ada data

            // Ambil saldo terakhir untuk akun 102
            $lastSaldo102 = Transaksi::where('akun_keuangan_id', 102)
                ->where('bidang_name', $bidangName)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last() // Ambil baris terakhir (data terbaru)
                    ?->saldo ?? 0; // Ambil nilai kolom 'saldo' atau default 0 jika tidak ada data

            $totalKeuangan += $lastSaldo101 + $lastSaldo102;
        }

        return $totalKeuangan;
    }

    public function showDetailBendahara(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id'); // Ambil parent_akun_id dari URL
        $type = $request->input('type');

        // Ambil semua ID anak (sub-akun) dari tabel akun_keuangans berdasarkan parent_id
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();

        // Ambil data transaksi terkait sub-akun (tanpa filter bidang_name)
        $transaksiData = Transaksi::whereIn('parent_akun_id', $subAkunIds)->get();

        // Hitung total jumlah transaksi
        $jumlahBiayaOperasional = $transaksiData->sum('amount');

        // Ambil nama_akun dari parent_akun_id
        $parentAkun = AkunKeuangan::find($parentAkunId);

        return view('bendahara.detail', compact('transaksiData', 'jumlahBiayaOperasional', 'parentAkunId', 'parentAkun', 'type'));
    }

    public function getDetailDataBendahara(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id');
        $type = $request->input('type');

        $query = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan']);

        if ($type) {
            $query->where('type', $type);
        }

        if ($parentAkunId) {
            $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();
            $query->whereIn('parent_akun_id', $subAkunIds);
        }

        return DataTables::of($query)
            ->addColumn('akun_keuangan', function ($row) {
                return $row->akunKeuangan?->nama_akun ?? 'N/A';
            })
            ->addColumn('parent_akun_keuangan', function ($row) {
                return $row->parentAkunKeuangan?->nama_akun ?? 'N/A';
            })
            ->make(true);
    }

}

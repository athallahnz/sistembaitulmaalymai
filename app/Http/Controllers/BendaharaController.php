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
    public function index()
    {
        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id
        // // Konsolidasi bank untuk bidang saat ini
        // $bankId = 102; // ID default akun bank
        // $dataKonsolidasi = LaporanService::index($bankId, $bidangName);
        // $totalSaldoBank = $dataKonsolidasi['saldo'];
        // $transaksiBank = $dataKonsolidasi['transaksi'];

        // **Akumulasi total seluruh Kas & Bank dari semua bidang**
        $akunKas = [
            'Bendahara' => 1011,
            1 => 1012,
            2 => 1013,
            3 => 1014,
            4 => 1015,
        ];

        $akunBank = [
            'Bendahara' => 1021,
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ];

        $saldoKasTotal = 0;
        $saldoBankTotal = 0;

        foreach ($akunKas as $bidang => $akun_keuangan_id) {
            $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoKasTotal += $lastSaldo ? (float) $lastSaldo->saldo : 0;
        }

        foreach ($akunBank as $bidang => $akun_keuangan_id) {
            $lastTransaksi = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoBankTotal += $lastTransaksi ? (float) $lastTransaksi->saldo : 0;
        }

        // **Total Keuangan Semua Bidang (Kas + Bank)**
        $totalKeuanganSemuaBidang = $saldoKasTotal + $saldoBankTotal;

        // Saldo terakhir untuk bidang saat ini
        $lastSaldo = Transaksi::where('bidang_name', $bidang_id)
            ->latest()
            ->value('saldo') ?? 0;

        // Jumlah transaksi untuk bulan ini
        $jumlahTransaksi = Transaksi::where('bidang_name', $bidang_id)
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->count();

        $totalPiutang = Piutang::where('status', 'belum_lunas')->sum('jumlah');
        $totalPendapatanBelumDiterima = PendapatanBelumDiterima::sum('jumlah');
        $totalTanahBangunan = Transaksi::where('akun_keuangan_id', 104)->sum('amount');
        $totalInventaris = Transaksi::where('akun_keuangan_id', 105)->sum('amount');
        $totalHutang = Hutang::where('status', 'belum_lunas')->sum('jumlah');
        $totalHutangJatuhTempo = Hutang::where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(7))
            ->count();

        $totalDonasi = Ledger::whereIn('transaksi_id', function ($query) {
            $query->select('transaksi_id')
                ->from('ledgers')
                ->where('akun_keuangan_id', 202);
        })->sum('credit');

        $totalPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)->sum('amount');
        $totalBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024])->sum('amount');
        $totalBiayaOperasional = Transaksi::whereIn('parent_akun_id', [3031, 3032, 3033, 3034, 3035, 3036, 3037, 3038, 3039, 30310, 30311, 30312])->sum('amount');
        $totalBiayaKegiatan = Transaksi::whereIn('parent_akun_id', [3041, 3042])->sum('amount');

        return view('bendahara.index', compact(
            // 'totalSaldoBank',
            // 'transaksiBank',
            'saldoKasTotal',
            'saldoBankTotal',
            'totalKeuanganSemuaBidang',
            'lastSaldo',
            'jumlahTransaksi',
            'totalPiutang',
            'totalPendapatanBelumDiterima',
            'totalTanahBangunan',
            'totalInventaris',
            'totalHutang',
            'totalHutangJatuhTempo',
            'totalDonasi',
            'totalPenyusutanAsset',
            'totalBebanGaji',
            'totalBiayaOperasional',
            'totalBiayaKegiatan'
        ));
    }

    /**
     * Menghitung total kas untuk bidang tertentu.
     */
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

    /**
     * Menghitung total kas untuk semua bidang.
     */
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

    /**
     * Menghitung total keuangan untuk seluruh bidang.
     */
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

        // Ambil semua ID anak (sub-akun) dari tabel akun_keuangans berdasarkan parent_id
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();

        // Ambil data transaksi terkait sub-akun (tanpa filter bidang_name)
        $transaksiData = Transaksi::whereIn('parent_akun_id', $subAkunIds)->get();

        // Hitung total jumlah transaksi
        $jumlahBiayaOperasional = $transaksiData->sum('amount');

        // Ambil nama_akun dari parent_akun_id
        $parentAkun = AkunKeuangan::find($parentAkunId);

        return view('bendahara.detail', compact('transaksiData', 'jumlahBiayaOperasional', 'parentAkunId', 'parentAkun'));
    }

    // Method untuk mengambil data transaksi tanpa filter bidang_name
    public function getDetailDataBendahara(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id'); // Ambil parent_akun_id dari URL

        // Ambil semua ID anak (sub-akun) dari tabel akun_keuangans berdasarkan parent_id
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();

        // Ambil data transaksi terkait sub-akun (tanpa filter bidang_name)
        $transaksiData = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan']) // Include relasi
            ->whereIn('parent_akun_id', $subAkunIds) // Filter berdasarkan sub-akun
            ->get();

        return DataTables::of($transaksiData)
            ->addColumn(
                'akun_keuangan',
                function ($row) {
                    return $row->akunKeuangan ? $row->akunKeuangan->nama_akun : 'N/A';
                }
            )
            ->addColumn('parent_akun_keuangan', function ($row) {
                return $row->parentAkunKeuangan ? $row->parentAkunKeuangan->nama_akun : 'N/A';
            })
            ->make(true);
    }

}


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\AkunKeuangan;
use App\Models\Piutang;
use App\Models\Hutang;
use App\Models\PendapatanBelumDiterima;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\LaporanService;
use Yajra\DataTables\Facades\DataTables;

class BidangController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bidang');  // Menggunakan middleware untuk role ketua
    }

    protected function getSaldoTerakhir($akunKeuanganId, $bidangName = null)
    {
        $query = Transaksi::where('akun_keuangan_id', $akunKeuanganId)
            ->when(is_null($bidangName), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangName), fn($q) => $q->where('bidang_name', $bidangName))
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id');

        $transaksi = $query->first();

        return $transaksi ? (float) $transaksi->saldo : 0;
    }

    public function index()
    {
        $user = auth()->user();
        $bidangId = $user->bidang_name ?? null;

        $akunKas = $user->role === 'Bendahara' ? 1011 : [
            1 => 1012,
            2 => 1013,
            3 => 1014,
            4 => 1015,
        ][$bidangId] ?? null;

        $akunBank = $user->role === 'Bendahara' ? 1021 : [
            1 => 1022,
            2 => 1023,
            3 => 1024,
            4 => 1025,
        ][$bidangId] ?? null;

        // ✅ Validasi akun sebelum ambil saldo
        if (!$akunKas || !$akunBank) {
            return back()->withErrors(['error' => 'Akun kas atau bank tidak valid.']);
        }

        // 🧮 Ambil saldo
        $saldoKas = $this->getSaldoTerakhir($akunKas, $user->role === 'Bendahara' ? null : $bidangId);
        $saldoBank = $this->getSaldoTerakhir($akunBank, $user->role === 'Bendahara' ? null : $bidangId);


        // Jumlahkan saldo terakhir untuk akun 101 dan 102
        $totalKeuanganBidang = $saldoKas + $saldoBank;

        $jumlahTransaksi = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->whereMonth('tanggal_transaksi', now()->month)  // Filter bulan ini
            ->whereYear('tanggal_transaksi', now()->year)    // Filter tahun ini
            ->where('kode_transaksi', 'not like', '%-LAWAN') // Hindari transaksi lawan
            ->count();  // Hitung jumlah transaksi utama

        $jumlahPiutang = Piutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas') // Opsional: hanya menghitung hutang yang belum lunas
            ->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::where('bidang_name', $bidangId)
            ->sum('jumlah');

        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahHutang = Hutang::where('bidang_name', $bidangId)
            ->where('status', 'belum_lunas') // Opsional: hanya menghitung hutang yang belum lunas
            ->sum('jumlah');

        $hutangJatuhTempo = Hutang::where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(7))
            ->count();

        $jumlahDonasi = Transaksi::whereIn('parent_akun_id', [2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024, 3025, 3026, 3027])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaOperasional = Transaksi::whereIn('parent_akun_id', [3031, 3032, 3033, 3034, 3035, 3036, 3037, 3038, 3039, 30310, 30311, 30312, 30313, 30314, 30315]) // Menggunakan whereIn untuk mengecek beberapa nilai
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount'); // Menjumlahkan kolom 'amount'

        $jumlahBiayaKegiatanSiswa = Transaksi::whereIn('parent_akun_id', [3041, 3042, 3043, 3044, 3045, 3046])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaPemeliharaan = Transaksi::whereIn('parent_akun_id', [3051, 3052])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaSosial = Transaksi::whereIn('parent_akun_id', [3061, 3062])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaPerlengkapanExtra = Transaksi::whereIn('parent_akun_id', [3071, 3072])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaSeragam = Transaksi::whereIn('parent_akun_id', [3081, 3082])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaPeningkatanSDM = Transaksi::whereIn('parent_akun_id', [3091])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayadibayardimuka = Transaksi::whereIn('parent_akun_id', [3101])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        // Kirimkan saldo terakhir ke view
        return view('bidang.index', compact(
            'totalKeuanganBidang',
            'jumlahTransaksi',
            'saldoKas',
            'saldoBank',
            'jumlahPiutang',
            'jumlahPendapatanBelumDiterima',
            'jumlahTanahBangunan',
            'jumlahInventaris',
            'jumlahHutang',
            'hutangJatuhTempo',
            'jumlahDonasi',
            'jumlahPenyusutanAsset',
            'jumlahBebanGaji',
            'jumlahBiayaKegiatanSiswa',
            'jumlahBiayaOperasional',
            'jumlahBiayaPemeliharaan',
            'jumlahBiayaSosial',
            'jumlahBiayaPerlengkapanExtra',
            'jumlahBiayaSeragam',
            'jumlahBiayaPeningkatanSDM',
            'jumlahBiayadibayardimuka'
        ));
    }

    public function showDetail(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id'); // Ambil parent_akun_id dari URL
        $type = $request->input('type'); // Ambil parent_akun_id dari URL
        $bidangId = $user->bidang_name ?? null;

        // Ambil semua ID anak (sub-akun) dari tabel akun_keuangans berdasarkan parent_id
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();

        // Ambil data transaksi terkait sub-akun (parent_akun_id = sub-akun)
        $transaksiData = Transaksi::whereIn('parent_akun_id', $subAkunIds)->get();

        // Ambil nama_akun dari parent_akun_id
        $parentAkun = AkunKeuangan::find($parentAkunId);

        return view('bidang.detail', compact('transaksiData', 'parentAkunId', 'type', 'parentAkun', 'bidangId'));
    }

    // Menambahkan method untuk mengambil data transaksi berdasarkan parent_akun_id
    public function getDetailData(Request $request)
    {
        $bidangName = auth()->user()->bidang_name;

        $parentAkunId = $request->input('parent_akun_id');
        $type = $request->input('type');

        $query = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan'])
            ->where('bidang_name', $bidangName);

        // Jika ada 'type', filter berdasarkan type
        if ($type) {
            $query->where('type', $type);
        }

        // Jika ada 'parent_akun_id', filter berdasarkan sub-akun dari parent
        if ($parentAkunId) {
            $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();
            $query->whereIn('parent_akun_id', $subAkunIds);
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


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
    public function index()
    {
        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Gunakan bidang_name sebagai bidang_id

        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1011;
        } else {
            $akunKas = [
                1 => 1012,
                2 => 1013,
                3 => 1014,
                4 => 1015,
            ];
            $akun_keuangan_id = $akunKas[$bidang_id] ?? null;
        }

        \Log::info("Akun Keuangan ID: " . ($akun_keuangan_id ?? 'NULL'));

        if (!$bidang_id || !$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun bank tidak ditemukan untuk bidang ini.']);
        }

        // Pastikan bidang_name yang diberikan ada dalam daftar
        if (isset($akunKas[$bidang_id])) {
            $akun_keuangan_id = $akunKas[$bidang_id];

            $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang_id)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil saldo terakhir (data terbaru)
        } else {
            $lastSaldo = null; // Jika bidang_name tidak ditemukan, return null
        }

        // Pastikan $lastSaldo adalah objek Transaksi dan mengakses saldo dengan benar
        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0; // Jika tidak ada transaksi sebelumnya, saldo Kas dianggap 0

        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1021;
        } else {
            $akunBank = [
                1 => 1022,
                2 => 1023,
                3 => 1024,
                4 => 1025,
            ];
            $akun_keuangan_id = $akunBank[$bidang_id] ?? null;
        }

        \Log::info("Akun Keuangan ID: " . ($akun_keuangan_id ?? 'NULL'));

        if (!$bidang_id || !$akun_keuangan_id) {
            return back()->withErrors(['error' => 'Akun bank tidak ditemukan untuk bidang ini.']);
        }

        // Pastikan bidang_name yang diberikan ada dalam daftar
        if (isset($akunBank[$bidang_id])) {
            $akun_keuangan_id = $akunBank[$bidang_id];

            $lastTransaksi = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang_id)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();
        } else {
            $lastTransaksi = null; // Jika bidang_name tidak ditemukan, return null
        }

        $saldoBank = $lastTransaksi ? (float) $lastTransaksi->saldo : 0;

        // Jumlahkan saldo terakhir untuk akun 101 dan 102
        $totalKeuanganBidang = $saldoKas + $saldoBank;

        $jumlahTransaksi = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->whereMonth('tanggal_transaksi', now()->month)  // Filter bulan ini
            ->whereYear('tanggal_transaksi', now()->year)    // Filter tahun ini
            ->where('kode_transaksi', 'not like', '%-LAWAN') // Hindari transaksi lawan
            ->count();  // Hitung jumlah transaksi utama

        $jumlahPiutang = Piutang::where('bidang_name', $bidang_id)
            ->where('status', 'belum_lunas') // Opsional: hanya menghitung hutang yang belum lunas
            ->sum('jumlah');

        $jumlahPendapatanBelumDiterima = PendapatanBelumDiterima::where('bidang_name', $bidang_id)
            ->sum('jumlah');

        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahHutang = Hutang::where('bidang_name', $bidang_id)
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

        $jumlahBebanGaji = Transaksi::whereIn('parent_akun_id', [3021, 3022, 3023, 3024])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahBiayaOperasional = Transaksi::whereIn('parent_akun_id', [3031, 3032, 3033, 3034, 3035, 3036, 3037, 3038, 3039, 30310, 30311, 30312]) // Menggunakan whereIn untuk mengecek beberapa nilai
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount'); // Menjumlahkan kolom 'amount'

        $jumlahBiayaKegiatan = Transaksi::whereIn('parent_akun_id', [3041, 3042]) // Menggunakan whereIn untuk mengecek beberapa nilai
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $xxx = Transaksi::where('akun_keuangan_id', 305)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $xxx = Transaksi::where('akun_keuangan_id', 306)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $xxx = Transaksi::where('akun_keuangan_id', 307)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $xxx = Transaksi::where('akun_keuangan_id', 308)
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
            'jumlahBiayaKegiatan',
            'jumlahBiayaOperasional',
        ));
    }

    public function showDetail(Request $request)
    {
        $parentAkunId = $request->input('parent_akun_id'); // Ambil parent_akun_id dari URL

        // Ambil semua ID anak (sub-akun) dari tabel akun_keuangans berdasarkan parent_id
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();

        // Ambil data transaksi terkait sub-akun (parent_akun_id = sub-akun)
        $transaksiData = Transaksi::whereIn('parent_akun_id', $subAkunIds)->get();

        // Hitung total jumlah transaksi
        $jumlahBiayaOperasional = $transaksiData->sum('amount');

        // Ambil nama_akun dari parent_akun_id
        $parentAkun = AkunKeuangan::find($parentAkunId);

        return view('bidang.detail', compact('transaksiData', 'jumlahBiayaOperasional', 'parentAkunId', 'parentAkun'));
    }

    // Menambahkan method untuk mengambil data transaksi berdasarkan parent_akun_id
    public function getDetailData(Request $request)
    {
        $bidang_name = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users

        $parentAkunId = $request->input('parent_akun_id'); // Ambil parent_akun_id dari URL

        // Ambil semua ID anak (sub-akun) dari tabel akun_keuangans berdasarkan parent_id
        $subAkunIds = AkunKeuangan::where('parent_id', $parentAkunId)->pluck('id')->toArray();

        // Ambil data transaksi terkait sub-akun (parent_akun_id = sub-akun)
        $transaksiData = Transaksi::with(['akunKeuangan', 'parentAkunKeuangan']) // Include relasi
            ->where('bidang_name', auth()->user()->bidang_name)
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


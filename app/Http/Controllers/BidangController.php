<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\AkunKeuangan;
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
        $bidangName = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users

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

        // Jumlahkan saldo terakhir untuk akun 101 dan 102
        $totalKeuanganBidang = $lastSaldo101 + $lastSaldo102;

        // Gunakan $totalKeuanganBidang sesuai kebutuhan
        $totalKeuanganBidang;

        // ID default untuk akun bank
        // Memanggil service untuk mendapatkan data konsolidasi bank
        $bankId = 102;
        $dataKonsolidasi = LaporanService::index($bankId, $bidangName);
        $totalSaldoBank = $dataKonsolidasi['saldo']; // Data yang akan diteruskan ke view
        $transaksiBank = $dataKonsolidasi['transaksi'];

        $jumlahTransaksi = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->whereMonth('tanggal_transaksi', now()->month)  // Filter berdasarkan bulan ini
            ->whereYear('tanggal_transaksi', now()->year)    // Filter berdasarkan tahun ini
            ->count();  // Menghitung jumlah transaksi

        $jumlahPiutang = Transaksi::whereIn('parent_akun_id', [1031, 1032, 1033, 1034, 1035, 1036])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

        $jumlahHutang = Transaksi::whereIn('parent_akun_id', [2011, 2012, 2013, 2014])
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('amount');

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

        $parentAkunIds = [3031, 3032, 3033, 3034, 3035];

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
            'lastSaldo101',
            'totalSaldoBank',
            'jumlahPiutang',
            'jumlahTanahBangunan',
            'jumlahInventaris',
            'jumlahHutang',
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
        $bidangName = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users

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


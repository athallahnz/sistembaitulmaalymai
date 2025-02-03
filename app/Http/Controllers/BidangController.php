<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\AkunKeuangan;


class BidangController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bidang');  // Menggunakan middleware untuk role ketua
    }
    public function index()
    {
        $lastSaldo = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->latest()  // Mengambil transaksi terakhir berdasarkan bidang_name
            ->first()->saldo ?? 0;  // Default ke 0 jika tidak ada transaksi sebelumnya

        $jumlahTransaksi = Transaksi::where('bidang_name', auth()->user()->bidang_name)
            ->whereMonth('tanggal_transaksi', now()->month)  // Filter berdasarkan bulan ini
            ->whereYear('tanggal_transaksi', now()->year)    // Filter berdasarkan tahun ini
            ->count();  // Menghitung jumlah transaksi

        // Ambil total debit berdasarkan akun_keuangan_id (Misal, 101 untuk Kas) dan bidang_name
        $jumlahKas = Transaksi::where('akun_keuangan_id', 101)  // ID akun untuk Kas
            ->where('bidang_name', auth()->user()->bidang_name)   // Filter berdasarkan bidang_name
            ->sum('debit'); // Kolom debit berisi nilai transaksi

        $jumlahBank = Transaksi::where('akun_keuangan_id', 102)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('debit');

        $jumlahPiutang = Transaksi::where('akun_keuangan_id', 103)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('debit');

        $jumlahTanahBangunan = Transaksi::where('akun_keuangan_id', 104)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('debit');

        $jumlahInventaris = Transaksi::where('akun_keuangan_id', 105)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('debit');

        $jumlahPenyusutanAsset = Transaksi::where('akun_keuangan_id', 301)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        $jumlahBebanGaji = Transaksi::where('akun_keuangan_id', 302)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        $jumlahBiayaOperasional = Transaksi::where('akun_keuangan_id', 303)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        $jumlahBiayaKegiatan = Transaksi::where('akun_keuangan_id', 304)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        $xxx = Transaksi::where('akun_keuangan_id', 305)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        $xxx = Transaksi::where('akun_keuangan_id', 306)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        $xxx = Transaksi::where('akun_keuangan_id', 307)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        $xxx = Transaksi::where('akun_keuangan_id', 308)
            ->where('bidang_name', auth()->user()->bidang_name)
            ->sum('kredit');

        // Kirimkan saldo terakhir ke view
        return view('bidang.index', compact(
            'lastSaldo',
            'jumlahTransaksi',
            'jumlahKas',
            'jumlahBank',
            'jumlahPiutang',
            'jumlahTanahBangunan',
            'jumlahInventaris',
            'jumlahPenyusutanAsset',
            'jumlahBebanGaji',
            'jumlahBiayaKegiatan',
            'jumlahBiayaOperasional',
        ));
    }

}


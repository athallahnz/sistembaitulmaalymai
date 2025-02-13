<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\LaporanService;

class BendaharaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bendahara');  // Menggunakan middleware untuk role ketua
    }
    public function index()
    {
        $bidangName = auth()->user()->bidang_name; // Bidang name dari user saat ini
        $bankId = 102; // ID default akun bank

        // Konsolidasi bank untuk bidang saat ini
        $dataKonsolidasi = LaporanService::konsolidasiBank($bankId, $bidangName);
        $totalSaldoBank = $dataKonsolidasi['saldo'];
        $transaksiBank = $dataKonsolidasi['transaksi'];

        // **Akumulasi total seluruh bank dari semua bidang**
        $allFields = Transaksi::distinct()->pluck('bidang_name');
        $totalSeluruhBank = $allFields->reduce(function ($carry, $field) use ($bankId) {
            $dataKonsolidasiField = LaporanService::konsolidasiBank($bankId, $field);
            return $carry + $dataKonsolidasiField['saldo'];
        }, 0);

        // Saldo terakhir untuk bidang saat ini
        $lastSaldo = Transaksi::where('bidang_name', $bidangName)
            ->latest()
            ->value('saldo') ?? 0;

        // Jumlah transaksi untuk bulan ini
        $jumlahTransaksi = Transaksi::where('bidang_name', $bidangName)
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->count();

        // Total Kas untuk bidang saat ini
        $totalKas = $this->calculateKasForBidang($bidangName);

        // **Akumulasi total kas seluruh bidang**
        $totalseluruhKas = $this->calculateTotalKas();

        // **Akumulasi total keuangan seluruh bidang**
        $totalKeuanganBidang = $this->calculateTotalKeuanganBidang();

        // Return data ke view
        return view('bendahara.index', compact(
            'totalSaldoBank',
            'transaksiBank',
            'totalSeluruhBank',
            'lastSaldo',
            'jumlahTransaksi',
            'totalKas',
            'totalseluruhKas',
            'totalKeuanganBidang'
        ));
    }

    /**
     * Menghitung total kas untuk bidang tertentu.
     */
    private function calculateKasForBidang($bidangName)
    {
        $totalDebit = Ledger::where('akun_keuangan_id', 101)
            ->whereHas('transaksi', function ($query) use ($bidangName) {
                $query->where('bidang_name', $bidangName);
            })
            ->sum('debit');

        $totalCredit = Ledger::where('akun_keuangan_id', 101)
            ->whereHas('transaksi', function ($query) use ($bidangName) {
                $query->where('bidang_name', $bidangName);
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

}


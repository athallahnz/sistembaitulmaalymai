<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Transaksi;
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

        // Konsolidasi bank untuk bidang saat ini
        $bankId = 102; // ID default akun bank
        $dataKonsolidasi = LaporanService::konsolidasiBank($bankId, $bidangName);
        $totalSaldoBank = $dataKonsolidasi['saldo'];
        $transaksiBank = $dataKonsolidasi['transaksi'];

        // **Akumulasi total seluruh bank dari semua bidang**
        $allFields = Transaksi::distinct()->pluck('bidang_name'); // Semua bidang yang ada di tabel transaksi

        $totalSeluruhBank = 0;
        foreach ($allFields as $field) {
            $dataKonsolidasiField = LaporanService::konsolidasiBank($bankId, $field);
            $totalSeluruhBank += $dataKonsolidasiField['saldo'];
        }

        // Saldo terakhir untuk bidang saat ini
        $lastSaldo = Transaksi::where('bidang_name', $bidangName)
            ->latest()
            ->first()->saldo ?? 0;

        // Jumlah transaksi untuk bulan ini
        $jumlahTransaksi = Transaksi::where('bidang_name', $bidangName)
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->count();

        // Total Kas untuk bidang saat ini
        $totalKas = Ledger::where('akun_keuangan_id', 101)
            ->whereHas('transaksi', function ($query) use ($bidangName) {
                $query->where('bidang_name', $bidangName);
            })
            ->sum('debit') -
            Ledger::where('akun_keuangan_id', 101)
                ->whereHas('transaksi', function ($query) use ($bidangName) {
                    $query->where('bidang_name', $bidangName);
                })
                ->sum('credit');

        // **Akumulasi total kas seluruh bidang**
        $totalseluruhKas = Ledger::where('akun_keuangan_id', 101)
            ->sum('debit') - Ledger::where('akun_keuangan_id', 101)->sum('credit');

        // Return data ke view
        return view('bendahara.index', compact(
            'totalSaldoBank',
            'transaksiBank',
            'totalSeluruhBank',
            'lastSaldo',
            'jumlahTransaksi',
            'totalKas',
            'totalseluruhKas'
        ));
    }
}


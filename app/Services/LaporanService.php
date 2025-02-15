<?php

namespace App\Services;

use App\Models\Transaksi;

class LaporanService
{
    public static function index($bankId, $bidangName = null)
    {
        // Query utama untuk transaksi bank
        $transaksiBankQuery = Transaksi::where('akun_keuangan_id', $bankId)
            ->orderBy('tanggal_transaksi', 'asc');

        // Tambahkan filter bidang_name jika ada
        if ($bidangName) {
            $transaksiBankQuery->where('bidang_name', $bidangName);
        }

        // Ambil data transaksi bank
        $transaksiBank = $transaksiBankQuery->get();

        // Hitung total penerimaan dan pengeluaran
        $saldo = Transaksi::where('akun_keuangan_id', $bankId)
            ->when($bidangName, function ($query) use ($bidangName) {
                return $query->where('bidang_name', $bidangName);
            })
            ->selectRaw("
                SUM(CASE WHEN type = 'penerimaan' THEN amount ELSE 0 END) as total_penerimaan,
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) as total_pengeluaran
            ")
            ->first();

        // Hitung total saldo
        $totalSaldo = ($saldo->total_penerimaan ?? 0) - ($saldo->total_pengeluaran ?? 0);

        // Pastikan nilai saldo tidak null
        if (!isset($totalSaldo)) {
            $totalSaldo = 0;
        }

        // Return hasil konsolidasi
        return [
            'transaksi' => $transaksiBank,
            'saldo' => $totalSaldo,
        ];
    }
}



<?php

namespace App\Services;

use App\Models\Transaksi;

class LaporanService
{
    public static function konsolidasiBank($bankId, $bidangName = null)
    {
        // Mendapatkan daftar transaksi berdasarkan akun bank dan bidang_name (jika ada)
        $transaksiBankQuery = Transaksi::where('akun_keuangan_id', $bankId)
            ->orderBy('tanggal_transaksi', 'asc');

        if ($bidangName) {
            $transaksiBankQuery->where('bidang_name', $bidangName);
        }

        $transaksiBank = $transaksiBankQuery->get();

        // Menghitung total penerimaan dan pengeluaran berdasarkan bidang_name (jika ada)
        $saldoQuery = Transaksi::where('akun_keuangan_id', $bankId)
            ->selectRaw("
                SUM(CASE WHEN type = 'penerimaan' THEN amount ELSE 0 END) as total_penerimaan,
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) as total_pengeluaran
            ");

        if ($bidangName) {
            $saldoQuery->where('bidang_name', $bidangName);
        }

        $saldo = $saldoQuery->first();

        // Menghitung total saldo
        $totalSaldo = $saldo->total_penerimaan - $saldo->total_pengeluaran;

        // Mengembalikan data transaksi dan saldo
        return [
            'transaksi' => $transaksiBank,
            'saldo' => $totalSaldo,
        ];
    }
}


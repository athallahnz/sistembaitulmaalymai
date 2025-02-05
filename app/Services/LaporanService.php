<?php

namespace App\Services;

use App\Models\Transaksi;

class LaporanService
{
    public static function konsolidasiBank($bankId)
    {
        // Mendapatkan daftar transaksi berdasarkan akun bank
        $transaksiBank = Transaksi::where('akun_keuangan_id', $bankId)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get();

        // Menghitung total penerimaan dan pengeluaran
        $saldo = Transaksi::where('akun_keuangan_id', $bankId)
            ->selectRaw("
                SUM(CASE WHEN type = 'penerimaan' THEN amount ELSE 0 END) as total_penerimaan,
                SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END) as total_pengeluaran
            ")
            ->first();

        // Menghitung total saldo
        $totalSaldo = $saldo->total_penerimaan - $saldo->total_pengeluaran;

        // Mengembalikan data transaksi dan saldo
        return [
            'transaksi' => $transaksiBank,
            'saldo' => $totalSaldo,
        ];
    }
}


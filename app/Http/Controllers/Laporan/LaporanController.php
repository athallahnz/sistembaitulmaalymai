<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;

class LaporanController extends Controller
{
    public function konsolidasiBank()
    {
        $bankId = 102; // ID default akun bank

        // Mendapatkan data transaksi dan saldo melalui service
        $dataBank = LaporanService::konsolidasiBank($bankId);

        // Return view dengan data
        return view('laporan.bank', [
            'transaksiBank' => $dataBank['transaksi'],
            'totalSaldoBank' => $dataBank['saldo']
        ]);
    }
}

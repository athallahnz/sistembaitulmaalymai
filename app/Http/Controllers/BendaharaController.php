<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;

class BendaharaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Bendahara');  // Menggunakan middleware untuk role ketua
    }
    public function index()
    {
        // // Ambil total saldo dari semua bidang
        // $lastSaldos = Transaksi::select('bidang_name', \DB::raw('MAX(id) as last_id'))
        //     ->groupBy('bidang_name');

        // $totalSaldo = Transaksi::joinSub($lastSaldos, 'last_saldos', function ($join) {
        //     $join->on('transaksis.id', '=', 'last_saldos.last_id');
        // })
        //     ->sum('transaksis.saldo');

        // Ambil tanggal transaksi terakhir (untuk semua bidang)
        $lastUpdate = Transaksi::latest()->first()->created_at ?? null;

        return view('bendahara.index', compact('lastUpdate'));
    }
}


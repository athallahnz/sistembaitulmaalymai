<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaldoKeuangan;
use App\Models\AkunKeuangan;

class SaldoKeuanganController extends Controller
{
    public function index()
    {
        // Ambil saldo keuangan berdasarkan bidang_name
        $bidangName = auth()->user()->bidang_name; // Ambil bidang_name dari user yang sedang login
        // $saldoKeuangan = SaldoKeuangan::where('bidang_name', $bidangName)->get();

        $saldo_keuangan = SaldoKeuangan::with('akunKeuangan')->orderBy('periode', 'desc')->get();
        return view('saldo_keuangan.index', compact('saldo_keuangan'));
    }
}

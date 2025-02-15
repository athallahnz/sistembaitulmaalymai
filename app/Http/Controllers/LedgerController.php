<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $bidangName = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users

        $totalKas = Ledger::where('akun_keuangan_id', 101)
            ->whereHas('transaksi', function ($query) use ($bidangName) {
                $query->where('bidang_name', $bidangName);
            })
            ->sum('debit')
            -
            Ledger::where('akun_keuangan_id', 101)
                ->whereHas('transaksi', function ($query) use ($bidangName) {
                    $query->where('bidang_name', $bidangName);
                })
                ->sum('credit');


        // Ambil data ledger dengan filter bidang_name
        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereHas('transaksi', function ($query) use ($bidangName) {
                $query->where('bidang_name', $bidangName);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('ledger.index', compact('ledgers', 'totalKas'));
    }

    public function getData()
    {
        $bidangName = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users

        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereHas('transaksi', function ($query) use ($bidangName) {
                $query->where('bidang_name', $bidangName);
            })
            ->whereIn('transaksi_id', function ($query) {
                $query->select('transaksi_id')
                    ->from('ledgers')
                    ->where('akun_keuangan_id', 101);
            })
            ->get();

        return DataTables::of($ledgers)
            ->addColumn('kode_transaksi', function ($item) {
                return $item->transaksi ? $item->transaksi->kode_transaksi : 'N/A';
            })
            ->addColumn('akun_nama', function ($item) {
                return $item->akun_keuangan ? $item->akun_keuangan->nama_akun : 'N/A';
            })
            ->rawColumns(['saldo', 'kode_transaksi', 'akun_nama'])
            ->make(true);
    }


}


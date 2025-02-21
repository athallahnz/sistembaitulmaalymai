<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Transaksi;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $bidangName = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users

        // Ambil saldo terakhir untuk akun 101
        $lastSaldo101 = Transaksi::where('akun_keuangan_id', 101)
            ->where('bidang_name', $bidangName)
            ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
            ->get() // Ambil semua data sebagai collection
            ->last() // Ambil baris terakhir (data terbaru)
                ?->saldo ?? 0; // Ambil nilai kolom 'saldo' atau default 0 jika tidak ada data


        // Ambil data ledger dengan filter bidang_name
        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereHas('transaksi', function ($query) use ($bidangName) {
                $query->where('bidang_name', $bidangName);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('ledger.index', compact('ledgers', 'lastSaldo101'));
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


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
        $bidang_name = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users

        // Daftar akun kas berdasarkan bidang_name
        $akunKas = [
            'Bendahara' => 1011,
            'Kemasjidan' => 1012,
            'Pendidikan' => 1013,
            'Sosial' => 1014,
            'Usaha' => 1015,
        ];

        // Pastikan bidang_name yang diberikan ada dalam daftar
        if (isset($akunKas[$bidang_name])) {
            $akun_keuangan_id = $akunKas[$bidang_name];

            $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->where('bidang_name', $bidang_name)
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil saldo terakhir (data terbaru)
        } else {
            $lastSaldo = null; // Jika bidang_name tidak ditemukan, return null
        }

        // Pastikan $lastSaldo adalah objek Transaksi dan mengakses saldo dengan benar
        $saldoKas = $lastSaldo ? $lastSaldo->saldo : 0; // Jika tidak ada transaksi sebelumnya, saldo Kas dianggap 0

        // Ambil data ledger dengan filter bidang_name
        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereHas('transaksi', function ($query) use ($bidang_name) {
                $query->where('bidang_name', $bidang_name);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('ledger.index', compact('ledgers', 'saldoKas'));
    }

    public function getData()
    {
        $user = auth()->user();
        $bidang_name = $user->bidang_name; // Ambil bidang dari user

        // Mapping bidang ke akun_keuangan_id
        $akunKas = [
            'Bendahara' => 1011,
            'Kemasjidan' => 1012,
            'Pendidikan' => 1013,
            'Sosial' => 1014,
            'Usaha' => 1015,
        ];

        // Pastikan bidang user ada dalam mapping
        $akun_keuangan_id = $akunKas[$bidang_name] ?? null;

        if (!$akun_keuangan_id) {
            return response()->json(['error' => 'Bidang tidak valid'], 400);
        }
        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereHas('transaksi', function ($query) use ($bidang_name, $akun_keuangan_id) {
                $query->where('bidang_name', $bidang_name)
                    ->where(function ($q) use ($akun_keuangan_id) {
                        $q->whereIn('akun_keuangan_id', [$akun_keuangan_id]) // Dari tabel transaksis
                            ->orWhereIn('parent_akun_id', [$akun_keuangan_id]); // Dari tabel transaksis
                    }); 
            })
            ->get();

        return DataTables::of($ledgers)
            ->addColumn('kode_transaksi', function ($item) {
                return $item->transaksi ? $item->transaksi->kode_transaksi : 'N/A';
            })
            ->addColumn('akun_nama', function ($item) {
                return $item->akun_keuangan ? $item->akun_keuangan->nama_akun : 'N/A';
            })
            ->rawColumns(['kode_transaksi', 'akun_nama'])
            ->make(true);
    }
}


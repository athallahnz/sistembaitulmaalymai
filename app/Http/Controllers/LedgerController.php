<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Transaksi;
use App\Models\Bidang;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $bidang_name = auth()->user()->bidang_name; // Sesuaikan dengan kolom yang relevan di tabel users
        $bidang_id = $user->bidang_name; // Ambil bidang_id dari user

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id') // Ambil akun tanpa parent
            ->whereNotIn('id', [101, 103, 104, 105, 201]) // Kecualikan ID tertentu
            ->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray();

        $role = $user->role;
        // Tentukan prefix berdasarkan bidang_id
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_id) {
                case 1: // Pendidikan
                    $prefix = 'SJD';
                    break;
                case 2: // Kemasjidan
                    $prefix = 'PND';
                    break;
                case 3: // Sosial
                    $prefix = 'SOS';
                    break;
                case 4: // Usaha
                    $prefix = 'UHA';
                    break;
                case 5: // Pembangunan
                    $prefix = 'BGN';
                    break;
            }
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH'; // Prefix untuk Bendahara
        }

        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // Cek apakah pengguna adalah Bendahara
        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1011; // Akun Bank untuk Bendahara

            $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();
        } else {
            // Daftar akun Bank berdasarkan bidang_id
            $akunBank = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            // Pastikan bidang_id yang diberikan ada dalam daftar
            if (isset($akunBank[$bidang_id])) {
                $akun_keuangan_id = $akunBank[$bidang_id];

                $lastSaldo = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                    ->where('bidang_name', $bidang_name) // Gunakan bidang_id sebagai referensi
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            } else {
                $lastSaldo = null; // Jika bidang_id tidak ditemukan, return null
            }
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

        return view('ledger.index', compact('ledgers','akunTanpaParent', 'akunDenganParent', 'saldoKas', 'kodeTransaksi'));
    }

    public function getData()
    {
        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Ambil bidang_id dari user

        // Cek apakah pengguna adalah Bendahara
        if ($user->role == 'Bendahara') {
            $akun_keuangan_id = 1011; // Akun Kas Bendahara
        } else {
            // Pastikan bidang ada dalam database
            $bidang = Bidang::find($bidang_id);

            if (!$bidang) {
                return response()->json(['error' => 'Bidang tidak ditemukan'], 400);
            }

            // Mapping bidang_id ke akun_keuangan_id
            $akunKas = [
                1 => 1012, // Kemasjidan
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            // Ambil akun_keuangan_id berdasarkan bidang_id
            $akun_keuangan_id = $akunKas[$bidang_id] ?? null;
        }
        if (!$akun_keuangan_id) {
            return response()->json(['error' => 'Bidang tidak valid'], 400);
        }

        // Ambil data ledger berdasarkan bidang_id
        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereHas('transaksi', function ($query) use ($bidang_id, $akun_keuangan_id) {
                $query->where('bidang_name', $bidang_id) // bidang_name sekarang adalah INTEGER ID
                    ->where(function ($q) use ($akun_keuangan_id) {
                        $q->whereIn('akun_keuangan_id', [$akun_keuangan_id]) // Dari tabel transaksis
                            ->orWhereIn('parent_akun_id', [$akun_keuangan_id]); // Dari tabel transaksis
                    })
                    ->where('kode_transaksi', 'not like', '%-LAWAN'); // Hindari transaksi lawan
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


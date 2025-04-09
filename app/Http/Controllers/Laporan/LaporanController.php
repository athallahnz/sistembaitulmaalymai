<?php

namespace App\Http\Controllers\Laporan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\Bidang;
use App\Services\LaporanService;
use Yajra\DataTables\Facades\DataTables;


class LaporanController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $bidang_name = auth()->user()->bidang_name;
        $bidang_id = $user->bidang_name; // Ambil bidang_id dari user

        // Cek apakah pengguna adalah Bendahara
        if ($user->role === 'Bendahara') {
            $akun_keuangan_id = 1021; // Akun Bank untuk Bendahara

            $lastTransaksi = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();
        } else {
            // Daftar akun Bank berdasarkan bidang_id
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];

            // Pastikan bidang_id yang diberikan ada dalam daftar
            if (isset($akunBank[$bidang_id])) {
                $akun_keuangan_id = $akunBank[$bidang_id];

                $lastTransaksi = Transaksi::where('akun_keuangan_id', $akun_keuangan_id)
                    ->where('bidang_name', $bidang_name) // Gunakan bidang_id sebagai referensi
                    ->orderBy('tanggal_transaksi', 'asc')
                    ->get()
                    ->last();
            } else {
                $lastTransaksi = null; // Jika bidang_id tidak ditemukan, return null
            }
        }

        $lastSaldo = $lastTransaksi ? (float) $lastTransaksi->saldo : 0;


        // Ambil transaksi berdasarkan role
        $transaksiQuery = Transaksi::with('parentAkunKeuangan', 'user');

        // Jika user memiliki role 'Bidang', filter berdasarkan bidang_name
        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        // Ambil hasil transaksi setelah filter
        $transaksi = $transaksiQuery->get();

        // Ambil semua data akun keuangan
        $akunKeuangan = AkunKeuangan::all();

        // // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = AkunKeuangan::whereNull('parent_id')
            ->whereNotIn('id', [103, 104, 105, 201]) // Kecualikan ID tertentu
            ->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = AkunKeuangan::whereNotNull('parent_id')->get()->groupBy('parent_id');

        $role = auth()->user()->role;

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

        // Generate kode transaksi
        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // Ambil akun-akun tanpa parent
        // $akunTanpaParent = AkunKeuangan::whereNull('parent_id')->get();

        // Menentukan bidang_name berdasarkan role user
        $bidangName = null;
        if ($user->hasRole('Bidang')) {
            // Ambil bidang_name sesuai role user
            $bidangName = auth()->user()->bidang_name; // Pastikan kolom 'bidang_name' ada di tabel users
        }

        // Mendapatkan data transaksi dan saldo melalui service
        $bankId = 102; // ID default akun bank
        $dataBank = LaporanService::index($bankId, $bidangName);

        Log::info('Data Bank:', $dataBank);

        if (!isset($dataBank['saldo'])) {
            $dataBank['saldo'] = 0;
        }

        // Return view dengan data
        return view('laporan.bank', [
            'transaksiBank' => $dataBank['transaksi'],
            'totalSaldoBank' => $dataBank['saldo'],
            'transaksi' => $transaksi,
            'akunTanpaParent' => $akunTanpaParent,
            'akunDenganParent' => $akunDenganParent,
            'bidang_name' => $bidang_name,
            'akunKeuangan' => $akunKeuangan,
            'kodeTransaksi' => $kodeTransaksi,
            'lastSaldo' => $lastSaldo,
        ]);
    }

    public function getData()
    {
        $user = auth()->user();
        $bidang_id = $user->bidang_name; // Ambil bidang_id dari user

        // Cek apakah pengguna adalah Bendahara
        if ($user->role == 'Bendahara') {
            $akun_keuangan_id = 1021; // Akun Kas Bendahara
        } else {
            // Pastikan bidang ada dalam database
            $bidang = Bidang::find($bidang_id);

            if (!$bidang) {
                return response()->json(['error' => 'Bidang tidak ditemukan'], 400);
            }

            // Mapping bidang_id ke akun_keuangan_id
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];

            // Ambil akun_keuangan_id berdasarkan bidang_id
            $akun_keuangan_id = $akunBank[$bidang_id] ?? null;
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

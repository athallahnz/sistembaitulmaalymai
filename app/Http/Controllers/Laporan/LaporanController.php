<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Services\LaporanService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;


class LaporanController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Ambil saldo terakhir berdasarkan bidang_name
        $lastSaldo = Transaksi::where('bidang_name', $user->bidang_name)
            ->latest()
            ->first()->saldo ?? 0;

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

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = AkunKeuangan::whereNull('parent_id')->get();

        // Ambil semua akun sebagai referensi untuk child dan konversi ke array
        $akunDenganParent = AkunKeuangan::whereNotNull('parent_id')->get()->groupBy('parent_id');

        $role = auth()->user()->role;
        $bidang_name = auth()->user()->bidang_name;

        // Tentukan prefix berdasarkan bidang_name
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_name) {
                case 'Pendidikan':
                    $prefix = 'PND';
                    break;
                case 'Kemasjidan':
                    $prefix = 'SJD';
                    break;
                case 'Sosial':
                    $prefix = 'SOS';
                    break;
                case 'Usaha':
                    $prefix = 'UHA';
                    break;
                case 'Pembangunan':
                    $prefix = 'BGN';
                    break;
            }
        }

        // Generate kode transaksi
        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // Ambil akun-akun tanpa parent
        $akunTanpaParent = AkunKeuangan::whereNull('parent_id')->get();

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
            'lastSaldo' => $lastSaldo
        ]);
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
                    ->where('akun_keuangan_id', 102);
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

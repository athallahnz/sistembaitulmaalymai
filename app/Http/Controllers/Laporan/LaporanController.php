<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Services\LaporanService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LaporanController extends Controller
{
    public function konsolidasiBank()
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

        // Ambil saldo terakhir untuk setiap akun_keuangan_id berdasarkan bidang_name yang sesuai dengan pengguna yang sedang login
        $lastSaldos = [];
        foreach ($akunTanpaParent as $akun) {
            // Ambil bidang_name dari pengguna yang sedang login
            $bidang_name = Auth::user()->bidang_name; // Mengambil bidang_name dari kolom 'bidang_name' di tabel 'users'

            // Ambil transaksi terakhir untuk akun_keuangan_id dan bidang_name yang sesuai
            $lastTransaksi = DB::table('transaksis')
                ->where('akun_keuangan_id', $akun->id)
                ->where('bidang_name', $bidang_name) // Filter berdasarkan bidang_name yang sesuai dengan user
                ->orderBy('tanggal_transaksi', 'asc') // Urutkan dari yang terlama ke yang terbaru
                ->get() // Ambil semua data sebagai collection
                ->last(); // Ambil baris terakhir dalam hasil (data terbaru)

            // Simpan saldo terakhir untuk akun tersebut jika ada, atau 0 jika tidak ada transaksi
            $lastSaldos[$akun->id] = $lastTransaksi ? $lastTransaksi->saldo : 0;
        }
        // Menentukan bidang_name berdasarkan role user
        $bidangName = null;
        if ($user->hasRole('Bidang')) {
            // Ambil bidang_name sesuai role user
            $bidangName = auth()->user()->bidang_name; // Pastikan kolom 'bidang_name' ada di tabel users
        }

        // Mendapatkan data transaksi dan saldo melalui service
        $bankId = 102; // ID default akun bank
        $dataBank = LaporanService::konsolidasiBank($bankId, $bidangName);

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
            'lastSaldos' => $lastSaldos, // Saldo per akun_keuangan_id
        ]);
    }


    public function getData(Request $request)
    {
        $bankId = 102; // ID default akun bank
        $user = Auth::user();

        // Menentukan bidang_name berdasarkan role user
        $bidangName = null;
        if ($user->hasRole('Bidang')) {
            $bidangName = $user->bidang_name;
        }

        // Ambil data transaksi bank melalui service
        $dataBank = LaporanService::konsolidasiBank($bankId, $bidangName);
        $transaksiBank = collect($dataBank['transaksi']); // Pastikan data dikonversi ke koleksi

        // Gunakan DataTables untuk memproses data
        return DataTables::of($transaksiBank)
            ->addColumn('created_at', function ($row) {
                return $row['tanggal_transaksi']; // Pastikan key sesuai dengan data yang tersedia
            })
            ->addColumn('kode_transaksi', function ($row) {
                return $row['kode_transaksi'];
            })
            ->addColumn('akun_nama', function ($row) {
                return $row['deskripsi']; // Ubah sesuai kolom yang benar
            })
            ->addColumn('debit', function ($row) {
                // Tampilkan hanya jika type = 'pengeluaran'
                return $row['type'] === 'penerimaan' ? number_format($row['amount'], 2) : '-';
            })
            ->addColumn('credit', function ($row) {
                // Tampilkan hanya jika type = 'penerimaan'
                return $row['type'] === 'pengeluaran' ? number_format($row['amount'], 2) : '-';
            })
            ->make(true);
    }

}

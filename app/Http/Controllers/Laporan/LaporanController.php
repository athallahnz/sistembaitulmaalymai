<?php

namespace App\Http\Controllers\Laporan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Yajra\DataTables\Facades\DataTables;

class LaporanController extends Controller
{
    public function konsolidasiBank()
    {
        $bankId = 102; // ID default akun bank
        $user = Auth::user();

        // Menentukan bidang_name berdasarkan role user
        $bidangName = null;

        if ($user->hasRole('Bidang')) {
            // Ambil bidang_name sesuai role user
            $bidangName = auth()->user()->bidang_name; // Pastikan kolom 'bidang_name' ada di tabel users
        }

        // Mendapatkan data transaksi dan saldo melalui service
        $dataBank = LaporanService::konsolidasiBank($bankId, $bidangName);

        // Return view dengan data
        return view('laporan.bank', [
            'transaksiBank' => $dataBank['transaksi'],
            'totalSaldoBank' => $dataBank['saldo']
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
                return $row['type'] === 'pengeluaran' ? number_format($row['amount'], 2) : '-';
            })
            ->addColumn('credit', function ($row) {
                // Tampilkan hanya jika type = 'penerimaan'
                return $row['type'] === 'penerimaan' ? number_format($row['amount'], 2) : '-';
            })
            ->make(true);
    }

}

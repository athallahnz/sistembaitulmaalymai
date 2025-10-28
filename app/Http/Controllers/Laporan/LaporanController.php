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
    protected function getSaldoTerakhir(int $akunKeuanganId, $bidangName = null): float
    {
        $query = Transaksi::where('akun_keuangan_id', $akunKeuanganId)
            ->when(is_null($bidangName), fn($q) => $q->whereNull('bidang_name'))
            ->when(!is_null($bidangName), fn($q) => $q->where('bidang_name', $bidangName));

        $row = $query->selectRaw("
                COALESCE(SUM(CASE
                    WHEN type = 'penerimaan' THEN amount
                    WHEN type = 'pengeluaran' THEN -amount
                    ELSE 0 END), 0
                ) AS saldo_akhir
            ")
            ->first();

        return (float) ($row->saldo_akhir ?? 0.0);
    }

    public function index()
    {
        $user = auth()->user();
        $bidang_name = $user->bidang_name;
        $bidang_id = $user->bidang_name;

        // ==========================
        // Tentukan akun bank sesuai role
        // ==========================
        if ($user->role === 'Bendahara') {
            $akunBankId = 1021; // Bank Bendahara
            $saldoBank = $this->getSaldoTerakhir($akunBankId, null);
        } else {
            $akunBank = [
                1 => 1022, // Kemasjidan
                2 => 1023, // Pendidikan
                3 => 1024, // Sosial
                4 => 1025, // Usaha
            ];

            if (isset($akunBank[$bidang_id])) {
                $akunBankId = $akunBank[$bidang_id];
                $saldoBank = $this->getSaldoTerakhir($akunBankId, $bidang_name);
            } else {
                $saldoBank = 0.0; // Jika bidang tidak valid
                $akunBankId = null;
            }
        }

        // ==========================
        // Ambil transaksi berdasarkan role
        // ==========================
        $transaksiQuery = Transaksi::with('parentAkunKeuangan', 'user');

        if ($user->role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $user->bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        // ==========================
        // Ambil data akun keuangan
        // ==========================
        $akunKeuangan = AkunKeuangan::all();
        $akunTanpaParent = AkunKeuangan::whereNull('parent_id')
            ->whereNotIn('id', [103, 104, 105, 201])
            ->get();

        $akunDenganParent = AkunKeuangan::whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id');

        // ==========================
        // Generate kode transaksi
        // ==========================
        $role = $user->role;
        $prefix = '';

        if ($role === 'Bidang') {
            switch ($bidang_id) {
                case 1:
                    $prefix = 'SJD';
                    break; // Pendidikan
                case 2:
                    $prefix = 'PND';
                    break; // Kemasjidan
                case 3:
                    $prefix = 'SOS';
                    break; // Sosial
                case 4:
                    $prefix = 'UHA';
                    break; // Usaha
                case 5:
                    $prefix = 'BGN';
                    break; // Pembangunan
            }
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH';
        }

        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // ==========================
        // Data laporan (jika pakai LaporanService)
        // ==========================
        $bidangName = $user->hasRole('Bidang') ? $user->bidang_name : null;

        $bankId = 102; // ID default akun bank
        $dataBank = LaporanService::index($bankId, $bidangName);

        Log::info('Data Bank:', $dataBank);

        if (!isset($dataBank['saldo'])) {
            $dataBank['saldo'] = 0;
        }

        // ==========================
        // Return ke view
        // ==========================
        return view('laporan.bank', [
            'transaksiBank' => $dataBank['transaksi'],
            'totalSaldoBank' => $dataBank['saldo'],
            'transaksi' => $transaksi,
            'akunTanpaParent' => $akunTanpaParent,
            'akunDenganParent' => $akunDenganParent,
            'bidang_name' => $bidang_name,
            'akunKeuangan' => $akunKeuangan,
            'kodeTransaksi' => $kodeTransaksi,
            'lastSaldo' => $saldoBank, // ← ini hasil dari getSaldoTerakhir()
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

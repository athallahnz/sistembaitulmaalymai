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
        $bidang_name = $user->bidang_name; // kolom bidang pada users
        $bidang_id = $user->bidang_name;

        // Ambil akun tanpa parent (parent_id = null)
        $akunTanpaParent = DB::table('akun_keuangans')
            ->whereNull('parent_id')
            ->whereNotIn('id', [101, 103, 104, 105, 201]) // Kecualikan ID tertentu
            ->get();

        // Ambil semua akun child dan group by parent
        $akunDenganParent = DB::table('akun_keuangans')
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id')
            ->toArray();

        $role = $user->role;

        // Tentukan prefix kode transaksi
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_id) {
                case 1:
                    $prefix = 'SJD';
                    break; // Pendidikan (cek kembali mapping ini)
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
        // Hitung Saldo KAS dengan getSaldoTerakhir (agregasi)
        // ==========================
        // Mapping KAS (bukan bank): Bendahara 1011, per-bidang 1012..1015
        if ($role === 'Bendahara') {
            $akunKasId = 1011; // Kas Bendahara
            // Bendahara: saldo agregat untuk bidang NULL
            $saldoKas = $this->getSaldoTerakhir($akunKasId, null);
        } else {
            $akunKas = [
                1 => 1012, // Kemasjidan (pastikan mapping sesuai COA kamu)
                2 => 1013, // Pendidikan
                3 => 1014, // Sosial
                4 => 1015, // Usaha
            ];

            if (isset($akunKas[$bidang_id])) {
                $akunKasId = $akunKas[$bidang_id];
                // Non-bendahara: saldo agregat per bidang (filter bidang_name)
                $saldoKas = $this->getSaldoTerakhir($akunKasId, $bidang_name);
            } else {
                $saldoKas = 0.0; // bidang tidak dikenali
            }
        }

        // Ambil data ledger dengan filter bidang_name (seperti sebelumnya)
        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereHas('transaksi', function ($query) use ($bidang_name, $role) {
                if ($role === 'Bendahara') {
                    $query->whereNull('bidang_name');
                } else {
                    $query->where('bidang_name', $bidang_name);
                }
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('ledger.index', compact('ledgers', 'akunTanpaParent', 'akunDenganParent', 'saldoKas', 'kodeTransaksi'));
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


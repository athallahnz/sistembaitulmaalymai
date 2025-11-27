<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Transaksi;
use App\Models\Bidang;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Services\LaporanKeuanganService;
use Illuminate\Support\Carbon;

class LedgerController extends Controller
{
    protected function getLastSaldoBySaldoColumn(
        int $akunId,
        string $userRole,
        $bidangValue,
        ?string $tanggalCutoff = null
    ): float {
        if (!$akunId)
            return 0.0;

        $q = Transaksi::where('akun_keuangan_id', $akunId);

        if ($tanggalCutoff) {
            $cutoff = \Carbon\Carbon::parse($tanggalCutoff)->toDateString();
            $q->whereDate('tanggal_transaksi', '<=', $cutoff);
        }

        if ($userRole !== 'Bendahara') {
            $q->where(function ($w) use ($bidangValue) {
                $w->where('bidang_name', $bidangValue)
                    ->orWhereNull('bidang_name');
            });
        }

        return (float) ($q->orderBy('tanggal_transaksi', 'desc')
            ->orderBy('id', 'desc')
            ->value('saldo') ?? 0.0);
    }

    public function index()
    {
        $user = auth()->user();
        $bidang_name = $user->bidang_name; // kolom bidang pada users
        $bidang_id = $user->bidang_name;
        $role = $user->role;

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

        // Tentukan prefix kode transaksi
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
        // 🔹 Hitung Saldo KAS via LaporanKeuanganService (PSAK 45)
        // dan dibatasi per-role
        // ==========================
        $kasGroupId = 101; // parent "Kas" di CoA

        if ($role === 'Bidang') {
            // Bidang: kas bidang saja (pakai group + filter bidang_name)
            $bidangNameForService = $bidang_name;

            $saldoKasRaw = LaporanKeuanganService::getSaldoByGroup(
                $kasGroupId,
                $bidangNameForService
            );
            $saldoKas = (float) ($saldoKasRaw ?? 0);
        } else {
            // Bendahara: hanya Kas Bendahara (1011) dengan bidang_name NULL
            $akunKasBendaharaId = 1011;
            $akunKasBendahara = AkunKeuangan::find($akunKasBendaharaId);

            if ($akunKasBendahara) {
                $saldoKas = (new LaporanKeuanganService())->getSaldoAkunSampai(
                    $akunKasBendahara,
                    Carbon::now()
                );
            } else {
                $saldoKas = 0;
            }
        }

        // ==========================
        // Ambil data ledger dengan filter bidang_name (seperti sebelumnya)
        // ==========================
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

        return view('ledger.index', compact(
            'ledgers',
            'akunTanpaParent',
            'akunDenganParent',
            'saldoKas',
            'kodeTransaksi'
        ));
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


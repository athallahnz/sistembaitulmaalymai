<?php

namespace App\Http\Controllers\Laporan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\AkunKeuangan;
use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\Bidang;
use App\Services\LaporanKeuanganService;
use Yajra\DataTables\Facades\DataTables;


class LaporanController extends Controller
{
    /**
     * Hitung saldo akhir akun (Kas/Bank) via agregasi,
     * MENGABAIKAN baris '-LAWAN' dan mendukung per-bidang/Bendahara.
     */

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
        $role = $user->role;
        $bidang_name = $user->bidang_name;
        $bidang_id = $user->bidang_name;

        // ==========================
        // Ambil transaksi (untuk tabel DataTable)
        // ==========================
        $transaksiQuery = Transaksi::with('parentAkunKeuangan', 'user');

        if ($role === 'Bidang') {
            $transaksiQuery->where('bidang_name', $bidang_name);
        }

        $transaksi = $transaksiQuery->get();

        // ==========================
        // Data akun keuangan
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
        // SALDO & TRANSAKSI BANK SESUAI PSAK 45
        // tapi dibatasi per role (Bidang vs Bendahara)
        // ==========================
        $bankGroupId = 102; // parent Bank di CoA

        if ($role === 'Bidang') {
            // 🔹 Bidang hanya lihat Bank untuk bidangnya sendiri
            $bidangNameForService = $bidang_name;

            // Transaksi Bank per-bidang
            $dataBankTransaksi = LaporanKeuanganService::getTransaksiByGroup(
                $bankGroupId,
                $bidangNameForService
            );

            // Saldo Bank per-bidang
            $saldoBankRaw = LaporanKeuanganService::getSaldoByGroup(
                $bankGroupId,
                $bidangNameForService
            );
            $saldoBank = (float) ($saldoBankRaw ?? 0);
        } else {
            // 🔹 Bendahara hanya lihat Bank Bendahara (misal 1021) & bidang_name NULL
            $akunBankBendaharaId = 1021;

            $akunBankBendahara = AkunKeuangan::find($akunBankBendaharaId);

            if ($akunBankBendahara) {
                // Saldo Bank Bendahara pakai ledger sampai hari ini
                $saldoBank = (new LaporanKeuanganService())->getSaldoAkunSampai(
                    $akunBankBendahara,
                    Carbon::now()
                );
            } else {
                $saldoBank = 0;
            }

            // Transaksi khusus Bank Bendahara
            $dataBankTransaksi = Ledger::with(['transaksi', 'akun_keuangan'])
                ->where('akun_keuangan_id', $akunBankBendaharaId)
                ->whereHas('transaksi', function ($q) {
                    $q->whereNull('bidang_name')
                        ->where('kode_transaksi', 'not like', '%-LAWAN');
                })
                ->orderBy('created_at', 'asc')
                ->get();
        }

        // ==========================
        // Kirim ke view
        // ==========================
        return view('laporan.bank', [
            'transaksiBank' => $dataBankTransaksi,
            'totalSaldoBank' => $saldoBank,
            'transaksi' => $transaksi,
            'akunTanpaParent' => $akunTanpaParent,
            'akunDenganParent' => $akunDenganParent,
            'bidang_name' => $bidang_name,
            'akunKeuangan' => $akunKeuangan,
            'kodeTransaksi' => $kodeTransaksi,
            'lastSaldo' => $saldoBank, // dipakai di kartu & <small id="saldo-bank">
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

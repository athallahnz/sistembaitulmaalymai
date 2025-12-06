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
            ->whereIn('tipe_akun', ['revenue', 'expense'])
            ->get();

        $akunDenganParent = AkunKeuangan::whereNotNull('parent_id')
            ->whereIn('tipe_akun', ['revenue', 'expense'])
            ->get()
            ->groupBy('parent_id');

        // ==========================
        // Generate kode transaksi
        // ==========================
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidang_id) {
                case 1:
                    $prefix = 'SJD'; // Pendidikan
                    break;
                case 2:
                    $prefix = 'PND'; // Kemasjidan
                    break;
                case 3:
                    $prefix = 'SOS'; // Sosial
                    break;
                case 4:
                    $prefix = 'UHA'; // Usaha
                    break;
                case 5:
                    $prefix = 'BGN'; // Pembangunan
                    break;
            }
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH';
        }

        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // ==========================
        // SALDO & TRANSAKSI BANK SESUAI PSAK 45 (via LEDGER)
        // ==========================

        $lapService = new LaporanKeuanganService();

        if ($role === 'Bidang') {
            // 🔹 Map akun Bank per bidang (sesuaikan dengan CoA-mu)
            $akunBankPerBidang = [
                1 => 1022, // Bank Pendidikan
                2 => 1023, // Bank Kemasjidan
                3 => 1024, // Bank Sosial
                4 => 1025, // Bank Usaha
                // 5 => ...  // kalau ada bidang lain
            ];

            $akunBankId = $akunBankPerBidang[$bidang_name] ?? null;

            if ($akunBankId) {
                $akunBank = AkunKeuangan::find($akunBankId);

                // Saldo Bank per-bidang via ledger sampai hari ini
                $saldoBank = $akunBank
                    ? $lapService->getSaldoAkunSampai($akunBank, Carbon::now())
                    : 0.0;

                // Transaksi Bank per-bidang (ledger, NOT %-LAWAN, filter bidang_name)
                $dataBankTransaksi = Ledger::with(['transaksi', 'akun_keuangan'])
                    ->where('akun_keuangan_id', $akunBankId)
                    ->whereHas('transaksi', function ($q) use ($bidang_name) {
                        $q->where('bidang_name', $bidang_name)
                            ->where('kode_transaksi', 'not like', '%-LAWAN');
                    })
                    ->orderBy('created_at', 'asc')
                    ->get();
            } else {
                // fallback kalau mapping gak ketemu
                $saldoBank = 0.0;
                $dataBankTransaksi = collect([]);
            }

        } else {
            // 🔹 Bendahara: lihat Bank Bendahara umum (akun 1021, bidang_name = NULL)
            $akunBankBendaharaId = 1021;
            $akunBankBendahara = AkunKeuangan::find($akunBankBendaharaId);

            // Saldo Bank Bendahara via ledger sampai hari ini
            $saldoBank = $akunBankBendahara
                ? $lapService->getSaldoAkunSampai($akunBankBendahara, Carbon::now())
                : 0.0;

            // Transaksi khusus Bank Bendahara (ledger, bidang_name NULL, NOT %-LAWAN)
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
        $role = $user->role;
        $bidang_id = $user->bidang_name;  // ini integer ID bidang

        // ==============================
        // 🔹 Tentukan akun KAS yang aktif
        // ==============================
        if ($role === 'Bendahara') {
            $akun_keuangan_id = 1021; // Bank Bendahara
        } else {
            $akunKasMap = [
                1 => 1022, // Bidang 1 (ubah dari 1012 -> 1022)
                2 => 1023, // Bidang 2 (ubah dari 1013 -> 1023)
                3 => 1024, // Bidang 3 (ubah dari 1014 -> 1024)
                4 => 1025, // Bidang 4 (ubah dari 1015 -> 1025)
            ];

            $akun_keuangan_id = $akunKasMap[$bidang_id] ?? null;
        }

        if (!$akun_keuangan_id) {
            return response()->json(['error' => 'Bidang tidak valid'], 400);
        }

        // ==============================
        // 🔹 Ambil ledger khusus akun KAS ini
        // ==============================
        $ledgers = Ledger::with(['transaksi', 'akun_keuangan'])
            ->where('akun_keuangan_id', $akun_keuangan_id)          // ⬅ cuma ledger kas
            ->whereHas('transaksi', function ($q) use ($role, $bidang_id) {
                // Filter per-bidang hanya untuk role Bidang
                if ($role === 'Bidang') {
                    $q->where('bidang_name', $bidang_id);
                } else {
                    $q->whereNull('bidang_name'); // Bendahara global
                }
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return DataTables::of($ledgers)

            // ================== TANGGAL ==================
            ->addColumn('tanggal', function ($item) {
                $trx = $item->transaksi;
                if (!$trx || !$trx->tanggal_transaksi) {
                    return '-';
                }

                try {
                    // kalau sudah di-cast ke Carbon, langsung format
                    if ($trx->tanggal_transaksi instanceof Carbon) {
                        return $trx->tanggal_transaksi->format('d-m-Y');
                    }

                    // kalau masih string, parse dulu
                    return Carbon::parse($trx->tanggal_transaksi)->format('d-m-Y');
                } catch (\Exception $e) {
                    return (string) $trx->tanggal_transaksi; // fallback
                }
            })

            // ================== AKUN SUMBER ==================
            ->addColumn('akun_sumber', function ($item) {
                $trx = $item->transaksi;
                if (!$trx) {
                    return '-';
                }

                $parentId = $trx->parent_akun_id;

                // Jika kredit > 0 → ledger ini sumber
                if ($item->credit > 0) {
                    return optional($item->akun_keuangan)->nama_akun ?? '-';
                }

                // Jika debit > 0 → sumber ada di parent_akun_id
                if ($item->debit > 0 && $parentId) {
                    $akunParent = AkunKeuangan::find($parentId);
                    return $akunParent ? $akunParent->nama_akun : '-';
                }

                return '-';
            })

            // ================== AKUN TUJUAN ==================
            ->addColumn('akun_tujuan', function ($item) {
                $trx = $item->transaksi;
                if (!$trx) {
                    return '-';
                }

                $parentId = $trx->parent_akun_id;

                // Jika debit > 0 → ledger ini tujuan
                if ($item->debit > 0) {
                    return optional($item->akun_keuangan)->nama_akun ?? '-';
                }

                // Jika kredit > 0 → tujuan ada di parent_akun_id
                if ($item->credit > 0 && $parentId) {
                    $akunParent = AkunKeuangan::find($parentId);
                    return $akunParent ? $akunParent->nama_akun : '-';
                }

                return '-';
            })

            // ================== DEBIT ==================
            ->addColumn('debit', function ($item) {
                return number_format($item->debit ?? 0, 0, ',', '.');
            })

            // ================== KREDIT ==================
            ->addColumn('kredit', function ($item) {
                return number_format($item->credit ?? 0, 0, ',', '.');
            })

            ->rawColumns(['tanggal', 'akun_sumber', 'akun_tujuan'])
            ->make(true);
    }

}

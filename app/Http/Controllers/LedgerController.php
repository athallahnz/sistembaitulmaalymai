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
        $role = $user->role;
        $bidangId = $user->bidang_name ?? null;

        $lapService = new LaporanKeuanganService();

        // Map Kas per role/bidang
        $akunKasId = $role === 'Bendahara'
            ? 1011
            : ([1 => 1012, 2 => 1013, 3 => 1014, 4 => 1015][$bidangId] ?? null);

        if (!$akunKasId) {
            return back()->withErrors(['error' => 'Akun kas tidak valid untuk bidang ini.']);
        }

        $akunKasModel = AkunKeuangan::find($akunKasId);

        $saldoKas = $akunKasModel
            ? $lapService->getSaldoAkunSampai($akunKasModel, Carbon::now())
            : 0.0;

        // --- data lain: akunTanpaParent, akunDenganParent, kodeTransaksi, dsb ---
        $akunTanpaParent = AkunKeuangan::whereNull('parent_id')
            ->whereIn('tipe_akun', ['asset','revenue', 'expense', 'liability'])
            ->get();

        $akunDenganParent = AkunKeuangan::whereNotNull('parent_id')
            ->whereIn('tipe_akun', ['asset','revenue', 'expense', 'liability'])
            ->get()
            ->groupBy('parent_id');

        // Kode transaksi (kaya yang tadi kamu pakai)
        $prefix = '';
        if ($role === 'Bidang') {
            switch ($bidangId) {
                case 1:
                    $prefix = 'SJD';
                    break;
                case 2:
                    $prefix = 'PND';
                    break;
                case 3:
                    $prefix = 'SOS';
                    break;
                case 4:
                    $prefix = 'UHA';
                    break;
                case 5:
                    $prefix = 'BGN';
                    break;
            }
        } elseif ($role === 'Bendahara') {
            $prefix = 'BDH';
        }

        $kodeTransaksi = $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        return view('ledger.index', [
            'saldoKas' => $saldoKas,
            'akunTanpaParent' => $akunTanpaParent,
            'akunDenganParent' => $akunDenganParent,
            'kodeTransaksi' => $kodeTransaksi,
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
            $akun_keuangan_id = 1011; // Kas Bendahara
        } else {
            $akunKasMap = [
                1 => 1012, // Bidang 1
                2 => 1013, // Bidang 2
                3 => 1014, // Bidang 3
                4 => 1015, // Bidang 4
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


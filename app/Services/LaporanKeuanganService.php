<?php

namespace App\Services;

use App\Models\AkunKeuangan;
use App\Models\Ledger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanKeuanganService
{
    /* ==========================================================
     |  DASHBOARD HELPERS (Dipakai di BidangController@index)
     * ========================================================== */

    /**
     * Hitung saldo 1 akun dari aggregate ledger.
     * Saldo = (debit-credit) jika saldo_normal=debit, else (credit-debit)
     */
    private function hitungSaldoAkun(AkunKeuangan $akun, ?object $agg): float
    {
        if (!$agg) return 0.0;

        $totalDebit  = (float) $agg->total_debit;
        $totalKredit = (float) $agg->total_credit;

        return $akun->saldo_normal === 'debit'
            ? ($totalDebit - $totalKredit)
            : ($totalKredit - $totalDebit);
    }

    /**
     * @param  string $bidangFilterMode 'ALL' | 'ID' | 'NULL'
     */
    public function getDashboardTotalsByParents(
        Carbon $startDate,
        Carbon $endDate,
        string $bidangFilterMode,
        ?int $bidangId,
        array $parentIds
    ): array {
        $parentIds = array_values(array_unique(array_map('intval', $parentIds)));
        $result = array_fill_keys($parentIds, 0.0);

        if (empty($parentIds)) {
            return $result;
        }

        // parent itself + children
        $akunRelevant = AkunKeuangan::query()
            ->whereIn('id', $parentIds)
            ->orWhereIn('parent_id', $parentIds)
            ->get(['id', 'parent_id', 'saldo_normal'])
            ->unique('id')
            ->values();

        $akunIds = $akunRelevant->pluck('id')->all();
        if (empty($akunIds)) {
            return $result;
        }

        // Normalisasi mode (defensive)
        $mode = strtoupper($bidangFilterMode); // 'ID' | 'NULL' | 'ALL'

        $agg = Ledger::query()
            ->select(
                'akun_keuangan_id',
                DB::raw('SUM(debit)  as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
            ->whereIn('akun_keuangan_id', $akunIds)
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate, $mode, $bidangId) {

                // Lebih ramah index daripada whereDate() (terutama jika datetime)
                $q->whereBetween('tanggal_transaksi', [
                    $startDate->copy()->startOfDay(),
                    $endDate->copy()->endOfDay(),
                ]);

                if ($mode === 'ID') {
                    if (!is_null($bidangId)) {
                        $q->where('bidang_name', $bidangId);
                    } else {
                        // Jika mode ID tapi bidangId kosong, aman: hasil = 0
                        $q->whereRaw('1=0');
                    }
                } elseif ($mode === 'NULL') {
                    $q->whereNull('bidang_name');
                }
                // mode ALL => no filter bidang (konsolidasi)
            })
            ->groupBy('akun_keuangan_id')
            ->get()
            ->keyBy('akun_keuangan_id');

        $parentSet = array_flip($parentIds);

        foreach ($akunRelevant as $akun) {
            $row = $agg->get($akun->id);
            $saldo = $this->hitungSaldoAkun($akun, $row);

            $targetParentId = isset($parentSet[$akun->id])
                ? (int) $akun->id
                : (int) $akun->parent_id;

            if (!isset($result[$targetParentId])) {
                continue;
            }

            /**
             * Catatan penting:
             * - Jika Anda ingin dashboard selalu positif (seperti sebelumnya), pakai abs().
             * - Jika Anda ingin saldo real (bisa negatif, mis. kas minus), hapus abs().
             */
            $result[$targetParentId] += abs($saldo);
            // $result[$targetParentId] += $saldo; // <-- opsi saldo real
        }

        return $result;
    }

    /**
     * Saldo akun sampai tanggal tertentu (cutoff).
     * Dipakai untuk saldo Kas/Bank pada dashboard.
     *
     * NOTE:
     * - Menggunakan LEDGER
     * - Default: tidak memfilter %-LAWAN (karena ledger sudah double-entry dan saldo akun tetap valid).
     *   Jika Anda butuh konsisten dengan dashboard lain yang exclude LAWAN, tambahkan filter kode_transaksi.
     */
    public function getSaldoAkunSampai(AkunKeuangan $akun, Carbon $tanggal): float
    {
        $q = Ledger::query()
            ->where('akun_keuangan_id', $akun->id)
            ->whereHas('transaksi', function ($tr) use ($tanggal) {
                $tr->whereDate('tanggal_transaksi', '<=', $tanggal);
                // Optional bila mau exclude LAWAN:
                // $tr->where('kode_transaksi', 'not like', '%-LAWAN');
            });

        $totalDebit  = (float) $q->sum('debit');
        $totalKredit = (float) $q->sum('credit');

        return ($akun->saldo_normal === 'debit')
            ? ($totalDebit - $totalKredit)
            : ($totalKredit - $totalDebit);
    }

    // ===== CORE: Aggregate saldo per akun dari tabel ledger (generic) =====
    protected static function buildSaldoPerAkun(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $bidangId = null,
        ?bool $excludeLawan = null
    ): \Illuminate\Support\Collection {
        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();

        $query = Ledger::select(
            'akun_keuangan_id',
            DB::raw('SUM(debit)  as total_debit'),
            DB::raw('SUM(credit) as total_credit')
        )
            ->whereHas('transaksi', function ($q) use ($startDate, $endDate, $bidangId, $excludeLawan) {
                $q->whereDate('tanggal_transaksi', '>=', $startDate)
                    ->whereDate('tanggal_transaksi', '<=', $endDate);

                if (!is_null($bidangId)) {
                    $q->where('bidang_name', $bidangId);
                }

                if ($excludeLawan === true) {
                    $q->where('kode_transaksi', 'not like', '%-LAWAN');
                } elseif ($excludeLawan === false) {
                    $q->where('kode_transaksi', 'like', '%-LAWAN');
                }
            });

        return $query->groupBy('akun_keuangan_id')->get()->keyBy('akun_keuangan_id');
    }

    public static function getSaldoByGroup(
        int $groupId,
        $bidangName = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $excludeLawan = true
    ): float {
        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate   = $endDate ?? now()->copy()->endOfDay();
        $bidangId  = $bidangName ? (int) $bidangName : null;

        $saldoPerAkun = self::buildSaldoPerAkun($startDate, $endDate, $bidangId, $excludeLawan);

        $akunList = self::getAkunByGroup($groupId);
        if ($akunList->isEmpty()) {
            $akun = AkunKeuangan::find($groupId);
            if (!$akun) return 0.0;
            $akunList = collect([$akun]);
        }

        $saldoTotal = 0.0;

        foreach ($akunList as $akun) {
            $agg = $saldoPerAkun->get($akun->id);
            $saldoTotal += self::hitungSaldoAkundashboard($akun, $agg);
        }

        return $saldoTotal;
    }

    private static function hitungSaldoAkundashboard(AkunKeuangan $akun, ?object $agg): float
    {
        if (!$agg) return 0.0;

        $totalDebit  = (float) ($agg->total_debit ?? 0);
        $totalKredit = (float) ($agg->total_credit ?? 0);

        return $akun->saldo_normal === 'debit'
            ? ($totalDebit - $totalKredit)
            : ($totalKredit - $totalDebit);
    }

    public static function getSaldoAkun(int $akunId, ?int $bidangId = null): float
    {
        $q = Ledger::query()
            ->join('transaksis as t', 't.id', '=', 'ledgers.transaksi_id')
            ->where('ledgers.akun_keuangan_id', $akunId);

        // ✅ Exclude LAWAN hanya jika bukan akun hutang pool
        if ($akunId !== 5005) {
            $q->where('t.kode_transaksi', 'not like', '%-LAWAN');
        }

        if (!is_null($bidangId)) {
            $q->where('t.bidang_name', $bidangId);
        }

        $agg = $q->selectRaw("
        COALESCE(SUM(ledgers.debit),0) AS total_debit,
        COALESCE(SUM(ledgers.credit),0) AS total_credit
    ")->first();

        $akun = AkunKeuangan::findOrFail($akunId);

        return $akun->saldo_normal === 'debit'
            ? $agg->total_debit - $agg->total_credit
            : $agg->total_credit - $agg->total_debit;
    }


    public static function getSaldoPerAkun(
        int $akunId,
        ?int $bidangName = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $excludeLawan = true
    ): float {
        $akun = AkunKeuangan::find($akunId);
        if (!$akun) return 0.0;

        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();
        $bidangId = $bidangName ? (int) $bidangName : null;

        $saldoPerAkun = self::buildSaldoPerAkun($startDate, $endDate, $bidangId, $excludeLawan);

        $row = $saldoPerAkun->get($akunId);
        if (!$row) return 0.0;

        return $akun->saldo_normal === 'debit'
            ? ($row->total_debit - $row->total_credit)
            : ($row->total_credit - $row->total_debit);
    }

    public static function getAkunByGroup(int $groupId)
    {
        return AkunKeuangan::where('parent_id', $groupId)->get();
    }

    /* =====================================================================
     |  DISABLED / NOT USED BY BidangController@index (untuk sementara)
     |  Jika nanti dibutuhkan lagi, pindahkan ke file/trait/service lain.
     * ===================================================================== */

    /*

    public static function getTransaksiByGroup(int $groupId, $bidangName = null)
    {
        $akunIds = self::getAkunByGroup($groupId)->pluck('id');

        if ($akunIds->isEmpty()) {
            $akunIds = collect([$groupId]);
        }

        $query = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereIn('akun_keuangan_id', $akunIds)
            ->whereHas('transaksi', function ($q) {
                $q->where('kode_transaksi', 'not like', '%-LAWAN');
            });

        if ($bidangName) {
            $query->whereHas('transaksi', function ($q) use ($bidangName) {
                $q->where('bidang_name', $bidangName);
            });
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    public static function getSaldoByGroupBidang(
        int $groupId,
        ?int $bidangId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): float {
        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();

        $saldoPerAkun = self::buildSaldoPerAkun($startDate, $endDate, $bidangId, null);

        $akunList = self::getAkunByGroup($groupId);

        if ($akunList->isEmpty()) {
            $akun = AkunKeuangan::find($groupId);
            if (!$akun) return 0.0;
            $akunList = collect([$akun]);
        }

        $saldoTotal = 0.0;
        foreach ($akunList as $akun) {
            $row = $saldoPerAkun->get($akun->id);
            if (!$row) continue;

            $saldoAkun = $akun->saldo_normal === 'debit'
                ? ($row->total_debit - $row->total_credit)
                : ($row->total_credit - $row->total_debit);

            $saldoTotal += abs($saldoAkun);
        }

        return $saldoTotal;
    }
    */
}

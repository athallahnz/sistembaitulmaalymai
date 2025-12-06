<?php

namespace App\Services;

use App\Models\AkunKeuangan;
use App\Models\Ledger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanKeuanganService
{
    /**
     * CORE: Aggregate saldo per akun dari tabel ledger.
     *
     * $excludeLawan:
     *   - null  : ambil semua transaksi (baik yang %-LAWAN maupun bukan)
     *   - true  : hanya transaksi BUKAN %-LAWAN
     *   - false : hanya transaksi %-LAWAN
     *
     * Return: Collection keyed by akun_keuangan_id
     *   [ akun_id => (object){ akun_keuangan_id, total_debit, total_credit } ]
     */
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

        return $query
            ->groupBy('akun_keuangan_id')
            ->get()
            ->keyBy('akun_keuangan_id');
    }

    /**
     * Hitung saldo akun sampai tanggal tertentu (cutoff).
     * Dipakai di dashboard Bidang (saldo kas & bank).
     */
    public function getSaldoAkunSampai(AkunKeuangan $akun, Carbon $tanggal): float
    {
        $query = Ledger::where('akun_keuangan_id', $akun->id)
            ->whereHas('transaksi', function ($q) use ($tanggal) {
                $q->whereDate('tanggal_transaksi', '<=', $tanggal);
            });

        $totalDebit = (float) $query->sum('debit');
        $totalKredit = (float) $query->sum('credit');

        return $akun->saldo_normal === 'debit'
            ? $totalDebit - $totalKredit
            : $totalKredit - $totalDebit;
    }

    /**
     * Ambil semua akun dalam suatu grup (berdasarkan parent_id).
     */
    public static function getAkunByGroup(int $groupId)
    {
        return AkunKeuangan::where('parent_id', $groupId)->get();
    }

    /**
     * Ambil transaksi ledger berdasarkan group akun.
     * (belum dipakai di index, tapi berguna untuk detail).
     */
    public static function getTransaksiByGroup(int $groupId, $bidangName = null)
    {
        $akunIds = self::getAkunByGroup($groupId)->pluck('id');

        // kalau tidak ada anak, pakai parent-nya
        if ($akunIds->isEmpty()) {
            $akunIds = collect([$groupId]);
        }

        $query = Ledger::with(['transaksi', 'akun_keuangan'])
            ->whereIn('akun_keuangan_id', $akunIds)
            ->whereHas('transaksi', function ($q) {
                $q->where('kode_transaksi', 'not like', '%-LAWAN'); // Hindari double-entry lawan
            });

        if ($bidangName) {
            $query->whereHas('transaksi', function ($q) use ($bidangName) {
                $q->where('bidang_name', $bidangName);
            });
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Hitung saldo grup PSAK (Kas/Bank/Pendapatan/Beban/Aset Neto) dengan filter bidang dan tanggal.
     * Menggunakan pola agregat yang sama dengan laporan aktivitas.
     * (TIDAK filter %-LAWAN → dua sisi ikut).
     */
    public static function getSaldoByGroupBidang(
        int $groupId,
        ?int $bidangId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): float {
        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();

        $saldoPerAkun = self::buildSaldoPerAkun(
            $startDate,
            $endDate,
            $bidangId,
            null // null = semua, tidak filter %-LAWAN
        );

        $akunList = self::getAkunByGroup($groupId);

        if ($akunList->isEmpty()) {
            $akun = AkunKeuangan::find($groupId);
            if (!$akun) {
                return 0.0;
            }
            $akunList = collect([$akun]);
        }

        $saldoTotal = 0.0;

        foreach ($akunList as $akun) {
            $row = $saldoPerAkun->get($akun->id);
            if (!$row) {
                continue;
            }

            $saldoAkun = $akun->saldo_normal === 'debit'
                ? ($row->total_debit - $row->total_credit)
                : ($row->total_credit - $row->total_debit);

            $saldoTotal += $saldoAkun;
        }

        return $saldoTotal;
    }

    /**
     * Hitung saldo grup PSAK (Kas/Bank/Pendapatan/Beban/Aset Neto).
     * Konsisten dengan buildSaldoPerAkun (bisa filter bidang & periode).
     *
     * @param  int         $groupId
     * @param  int|null    $bidangName   (id bidang, kalau ada)
     * @param  Carbon|null $startDate
     * @param  Carbon|null $endDate
     * @param  bool        $excludeLawan true = buang %-LAWAN (default)
     */
    public static function getSaldoByGroup(
        int $groupId,
        $bidangName = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $excludeLawan = true
    ): float {
        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();
        $bidangId = $bidangName ? (int) $bidangName : null;

        $saldoPerAkun = self::buildSaldoPerAkun(
            $startDate,
            $endDate,
            $bidangId,
            $excludeLawan
        );

        $akunList = self::getAkunByGroup($groupId);

        if ($akunList->isEmpty()) {
            $akun = AkunKeuangan::find($groupId);
            if (!$akun) {
                return 0.0;
            }
            $akunList = collect([$akun]);
        }

        $saldoTotal = 0.0;

        foreach ($akunList as $akun) {
            $row = $saldoPerAkun->get($akun->id);
            if (!$row) {
                continue;
            }

            $saldoAkun = $akun->saldo_normal === 'debit'
                ? ($row->total_debit - $row->total_credit)
                : ($row->total_credit - $row->total_debit);

            $saldoTotal += $saldoAkun;
        }

        return $saldoTotal;
    }

    /**
     * Hitung saldo grup khusus sisi LAWAN (kode_transaksi like %-LAWAN).
     */
    public static function getSaldoLawanByGroup(
        int $groupId,
        ?int $bidangName = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): float {
        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();
        $bidangId = $bidangName ? (int) $bidangName : null;

        // onlyLawan = false → helper akan pakai like '%-LAWAN'
        $saldoPerAkun = self::buildSaldoPerAkun(
            $startDate,
            $endDate,
            $bidangId,
            false
        );

        $akunList = self::getAkunByGroup($groupId);

        if ($akunList->isEmpty()) {
            $akun = AkunKeuangan::find($groupId);
            if (!$akun) {
                return 0.0;
            }
            $akunList = collect([$akun]);
        }

        $saldoTotal = 0.0;

        foreach ($akunList as $akun) {
            $row = $saldoPerAkun->get($akun->id);
            if (!$row) {
                continue;
            }

            $saldoAkun = $akun->saldo_normal === 'debit'
                ? ($row->total_debit - $row->total_credit)
                : ($row->total_credit - $row->total_debit);

            $saldoTotal += $saldoAkun;
        }

        return $saldoTotal;
    }

    /**
     * Hitung saldo suatu akun dengan filter bidang dan tanggal.
     */
    public static function getSaldoPerAkun(
        int $akunId,
        ?int $bidangName = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $excludeLawan = true
    ): float {
        $akun = AkunKeuangan::find($akunId);
        if (!$akun) {
            return 0.0;
        }

        $startDate = $startDate ?? now()->copy()->startOfYear();
        $endDate = $endDate ?? now()->copy()->endOfDay();
        $bidangId = $bidangName ? (int) $bidangName : null;

        $saldoPerAkun = self::buildSaldoPerAkun(
            $startDate,
            $endDate,
            $bidangId,
            $excludeLawan
        );

        $row = $saldoPerAkun->get($akunId);
        if (!$row) {
            return 0.0;
        }

        return $akun->saldo_normal === 'debit'
            ? ($row->total_debit - $row->total_credit)
            : ($row->total_credit - $row->total_debit);
    }

    /**
     * Hitung saldo ledger sampai tanggal dengan filter bidang + role.
     * (dipakai di laporan lain, bukan dashboard index yang tadi).
     */
    protected function getSaldoLedgerSampaiTanggal(
        ?int $akunId,
        string $tanggal,
        ?int $bidangValue,
        string $userRole
    ): float {
        if (!$akunId) {
            return 0.0;
        }

        $akun = AkunKeuangan::find($akunId);
        if (!$akun) {
            return 0.0;
        }

        $q = Ledger::where('akun_keuangan_id', $akunId)
            ->whereHas('transaksi', function ($tr) use ($tanggal, $bidangValue, $userRole) {
                $tr->whereDate('tanggal_transaksi', '<=', $tanggal);

                if ($userRole !== 'Bendahara') {
                    $tr->where('bidang_name', $bidangValue);
                } else {
                    $tr->whereNull('bidang_name');
                }
            });

        $debit = (float) $q->sum('debit');
        $credit = (float) $q->sum('credit');

        return $akun->saldo_normal === 'debit'
            ? ($debit - $credit)
            : ($credit - $debit);
    }
}

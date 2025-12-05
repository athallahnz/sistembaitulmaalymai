<?php

namespace App\Services;

use App\Models\AkunKeuangan;
use App\Models\Ledger;
use Illuminate\Support\Carbon;

class LaporanKeuanganService
{
    /**
     * Hitung saldo akun sampai tanggal tertentu (cutoff).
     */
    public function getSaldoAkunSampai(AkunKeuangan $akun, Carbon $tanggal): float
    {
        $query = Ledger::where('akun_keuangan_id', $akun->id)
            ->whereHas('transaksi', function ($q) use ($tanggal) {
                $q->whereDate('tanggal_transaksi', '<=', $tanggal);
            });

        $totalDebit = (float) $query->sum('debit');
        $totalKredit = (float) $query->sum('credit');

        // Gunakan saldo_normal yang sudah kamu pakai di sistem
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
     */
    public static function getTransaksiByGroup(int $groupId, $bidangName = null)
    {
        $akunIds = self::getAkunByGroup($groupId)->pluck('id');

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
     * Hitung saldo grup PSAK (Kas/Bank/Pendapatan/Beban/Aset Neto).
     */
    public static function getSaldoByGroup(int $groupId, $bidangName = null): float
    {
        // 1) Cari sub-akun berdasarkan parent_id
        $akunList = self::getAkunByGroup($groupId); // biasanya: AkunKeuangan::where('parent_id', $groupId)->get()

        // 2) Kalau tidak ada anak, maka pakai akun dengan id = $groupId (akun tunggal)
        if ($akunList->isEmpty()) {
            $akun = AkunKeuangan::find($groupId);

            if (!$akun) {
                return 0; // kalau benar-benar tidak ada, ya 0
            }

            $akunList = collect([$akun]);
        }

        $saldoTotal = 0;

        foreach ($akunList as $akun) {
            $query = Ledger::where('akun_keuangan_id', $akun->id)
                ->whereHas('transaksi', function ($q) {
                    // exclude kode transaksi lawan
                    $q->where('kode_transaksi', 'not like', '%-LAWAN');
                });

            if ($bidangName) {
                $query->whereHas('transaksi', function ($q) use ($bidangName) {
                    $q->where('bidang_name', $bidangName);
                });
            }

            $debit = (float) $query->sum('debit');
            $credit = (float) $query->sum('credit');

            // Gunakan saldo_normal akun
            $saldoAkun = $akun->saldo_normal === 'debit'
                ? $debit - $credit
                : $credit - $debit;

            $saldoTotal += $saldoAkun;
        }

        return $saldoTotal;
    }

    public static function getSaldoPerAkun(int $akunId, ?int $bidangName = null): float
    {
        $akun = AkunKeuangan::find($akunId);
        if (!$akun) {
            return 0.0;
        }

        $query = Ledger::where('akun_keuangan_id', $akunId)
            ->whereHas('transaksi', function ($q) {
                $q->where('kode_transaksi', 'not like', '%-LAWAN'); // hindari double entry lawan
            });

        if (!is_null($bidangName)) {
            $query->whereHas('transaksi', function ($q) use ($bidangName) {
                $q->where('bidang_name', $bidangName);
            });
        }

        $debit = (float) $query->sum('debit');
        $credit = (float) $query->sum('credit');

        return $akun->saldo_normal === 'debit'
            ? ($debit - $credit)
            : ($credit - $debit);
    }

    protected function getSaldoLedgerSampaiTanggal(
        ?int $akunId,
        string $tanggal,
        ?int $bidangValue,
        string $userRole
    ): float {
        if (!$akunId)
            return 0.0;

        // Ambil akun untuk saldo_normal
        $akun = AkunKeuangan::find($akunId);
        if (!$akun)
            return 0.0;

        $q = Ledger::where('akun_keuangan_id', $akunId)
            ->whereHas('transaksi', function ($tr) use ($tanggal, $bidangValue, $userRole) {
                $tr->whereDate('tanggal_transaksi', '<=', $tanggal);

                // Bidang filter
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

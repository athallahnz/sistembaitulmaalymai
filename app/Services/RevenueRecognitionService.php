<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\Student;
use App\Models\TagihanSpp;
use App\Models\StudentCost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueRecognitionService
{
    /**
     * Pengakuan pendapatan SPP untuk satu siswa di 1 periode (bulan/tahun).
     * Dipakai oleh controller recognizeStudentSPP().
     */
    public static function recognizeSPP(
        Student $student,
        int $bulan,
        int $tahun,
        ?Carbon $tanggal = null
    ): void {
        // delegasikan ke fungsi utama
        self::recognizeMonthlySPP($student, $bulan, $tahun, $tanggal);
    }

    /**
     * Pengakuan pendapatan SPP untuk satu siswa di 1 periode (bulan/tahun).
     * Dipakai juga oleh proses BULK.
     *
     * Alur jurnal:
     *   D Pendapatan Belum Diterima – SPP (50011)
     *       K Pendapatan SPP (202 / 2021 / 2022) tergantung kelas siswa
     *
     * HANYA untuk tagihan SPP yang status-nya 'lunas'.
     */
    public static function recognizeMonthlySPP(
        Student $student,
        int $bulan,
        int $tahun,
        ?Carbon $tanggal = null
    ): void {
        DB::transaction(function () use ($student, $bulan, $tahun, $tanggal) {

            $akunPBDSPP = config('akun.pendapatan_belum_diterima_spp'); // 50011
            $akunPendSPP = self::resolveAkunPendapatanSPP($student);     // 202 / 2021 / 2022

            // 🔹 Ambil tagihan SPP siswa untuk bulan/tahun INI yang SUDAH LUNAS
            $tagihan = TagihanSpp::where('student_id', $student->id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('status', 'lunas')   // ⬅️ filter LUNAS
                ->first();

            if (!$tagihan || $tagihan->jumlah <= 0) {
                // Tidak ada tagihan lunas / nominal 0 → tidak buat jurnal
                return;
            }

            $nominal = $tagihan->jumlah;
            $tanggal = $tanggal ?? Carbon::now();
            $kode = 'RCG-SPP-' . $tanggal->format('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));
            $deskripsi = "Pengakuan pendapatan SPP {$student->name} bulan {$bulan}/{$tahun}";

            // 🔸 Header transaksi
            $transaksi = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengakuan_pendapatan',
                'deskripsi' => $deskripsi,
                'akun_keuangan_id' => $akunPBDSPP,   // D PBD SPP
                'parent_akun_id' => $akunPendSPP,  // parent = akun pendapatan SPP
                'amount' => $nominal,
                'saldo' => 0,
                'bidang_name' => 2,
                'sumber' => $student->id,
            ]);

            // D Pendapatan Belum Diterima – SPP
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPBDSPP,
                'debit' => $nominal,
                'credit' => 0,
            ]);

            // K Pendapatan SPP (KB/TK / Daycare / umum)
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPendSPP,
                'debit' => 0,
                'credit' => $nominal,
            ]);
        });
    }

    /**
     * Menentukan akun pendapatan SPP (202 / 2021 / 2022)
     * berdasarkan nama kelas di relasi eduClass (edu_classes.name).
     *
     * Contoh:
     *  - "KB A", "TK B"      -> 2021 (pendapatan_spp_kb_tk)
     *  - "Daycare A"         -> 2022 (pendapatan_spp_daycare)
     *  - lainnya / tidak ada -> 202  (pendapatan_spp)
     */
    protected static function resolveAkunPendapatanSPP(Student $student): int
    {
        $className = optional($student->eduClass)->name;

        if (!$className) {
            return config('akun.pendapatan_spp'); // 202
        }

        $normalized = strtolower(trim($className));

        if (str_starts_with($normalized, 'daycare')) {
            return config('akun.pendapatan_spp_daycare'); // 2022
        }

        if (str_starts_with($normalized, 'kb') || str_starts_with($normalized, 'tk')) {
            return config('akun.pendapatan_spp_kb_tk');   // 2021
        }

        return config('akun.pendapatan_spp'); // 202 (fallback)
    }

    /**
     * Pengakuan pendapatan PMB untuk satu siswa.
     *
     * Nominal + akun detail diambil dari tabel student_costs.
     *
     * Jurnal:
     *   D Pendapatan Belum Diterima – PMB (50012)
     *       K Pendapatan PMB – detail (2011/2012/2013/2014/2015)
     */
    public static function recognizePMB(Student $student, ?Carbon $tanggal = null): void
    {
        DB::transaction(function () use ($student, $tanggal) {

            $akunPBDPMB = config('akun.pendapatan_belum_diterima_pmb'); // 50012
            $akunPendPMBParent = config('akun.pendapatan_pmb');               // 201 (parent)

            // Ambil seluruh rincian biaya PMB untuk siswa ini
            $costs = StudentCost::where('student_id', $student->id)->get();

            if ($costs->isEmpty()) {
                // Tidak ada rincian biaya → tidak buat jurnal
                return;
            }

            $totalNominal = $costs->sum('jumlah');

            $tanggal = $tanggal ?? Carbon::now();
            $kode = 'RCG-PMB-' . $tanggal->format('Ymd') . '-' . strtoupper(substr(md5(rand()), 0, 5));
            $deskripsi = "Pengakuan pendapatan PMB {$student->name}";

            // Header transaksi
            $transaksi = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengakuan_pendapatan',
                'deskripsi' => $deskripsi,
                'akun_keuangan_id' => $akunPBDPMB,        // D PBD PMB
                'parent_akun_id' => $akunPendPMBParent, // parent pendapatan PMB (201)
                'amount' => $totalNominal,
                'saldo' => 0,
                'bidang_name' => 2,
                'sumber' => $student->id,
            ]);

            // D Pendapatan Belum Diterima – PMB (total)
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPBDPMB,
                'debit' => $totalNominal,
                'credit' => 0,
            ]);

            // K pendapatan per komponen (2011, 2012, 2013, 2014, 2015, ...)
            foreach ($costs as $cost) {
                Ledger::create([
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $cost->akun_keuangan_id,
                    'debit' => 0,
                    'credit' => $cost->jumlah,
                ]);
            }
        });
    }
}



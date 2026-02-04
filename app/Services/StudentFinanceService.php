<?php

namespace App\Services;

use App\Models\Student;
use App\Models\PendapatanBelumDiterima;
use App\Models\Piutang;
use App\Models\Transaksi;
use App\Models\Bidang;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentFinanceService
{
    /**
     * Menangani penyesuaian keuangan ketika data biaya siswa di-update.
     *
     * Sekarang pendekatan: REBUILD
     * - Hapus jejak tagihan awal PMB (Transaksi PBD PMB + Ledger + tracker Piutang & PBD)
     * - Bangun ulang tagihan awal berdasarkan student_cost terbaru (biayaPairs)
     *
     * Detail per akun tetap di student_cost.
     * Piutang & PendapatanBelumDiterima = 1 row per siswa (total).
     */
    public function handleUpdateStudentFinance(
        Student $student,
        array $biayaPairs,
        int $totalBiayaBaru,
        ?int $totalBiayaLama = null
    ): void {
        Log::info('[FinanceUpdate] Mulai handleUpdateStudentFinance (REBUILD)', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'old_total_biaya' => $totalBiayaLama,
            'new_total_biaya' => $totalBiayaBaru,
            'biaya_pairs' => $biayaPairs,
        ]);

        $akunPiutangPMB = config('akun.piutang_pmb');                  // 1032
        $akunPBDPMB = config('akun.pendapatan_belum_diterima_pmb'); // 50012

        // 1) HAPUS transaksi "pendapatan belum diterima" PMB + ledger-nya untuk siswa ini
        $trxPbd = Transaksi::where('type', 'pendapatan belum diterima')
            ->where('student_id', $student->id)
            ->get();

        foreach ($trxPbd as $trx) {
            Ledger::where('transaksi_id', $trx->id)->delete();
            $trx->delete();
        }

        Log::info('[FinanceUpdate] Transaksi PBD PMB & ledger dihapus', [
            'student_id' => $student->id,
            'deleted_trx' => $trxPbd->pluck('id')->all(),
        ]);

        // 2) HAPUS tracker PBD (PendapatanBelumDiterima) untuk siswa ini
        $deletedPbd = PendapatanBelumDiterima::where('student_id', $student->id)->delete();

        // 3) HAPUS tracker piutang PMB untuk siswa ini
        $deletedPiutang = Piutang::where('student_id', $student->id)
            ->where('akun_keuangan_id', $akunPiutangPMB)
            ->delete();

        Log::info('[FinanceUpdate] Tracker PBD & Piutang dihapus', [
            'student_id' => $student->id,
            'deleted_pbd_row' => $deletedPbd,
            'deleted_piutang_row' => $deletedPiutang,
        ]);

        // 4) BANGUN ULANG berdasarkan biayaPairs (student_cost terbaru)
        $this->handleNewStudentFinance($student, $biayaPairs);

        Log::info('[FinanceUpdate] handleUpdateStudentFinance (REBUILD) selesai', [
            'student_id' => $student->id,
            'total_biaya' => $totalBiayaBaru,
        ]);
    }

    public function handleNewStudentFinance(Student $student, array $biayaPairs)
    {
        // Total dari seluruh rincian biaya PMB siswa ini
        $totalBiaya = array_sum($biayaPairs);

        if ($totalBiaya <= 0) {
            Log::warning('[FinanceNew] Total biaya PMB <= 0, skip create finance row.', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'biaya_pairs' => $biayaPairs,
            ]);
            return;
        }

        $akunPiutangPMB = config('akun.piutang_pmb');                  // 1032
        $akunPBDPMB = config('akun.pendapatan_belum_diterima_pmb'); // 50012
        $bidangId = 2; // Pendidikan (kalau nanti mau dinamis, bisa di-parameter-kan)

        $tanggal = now();
        $kode = 'PMB-' . $tanggal->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 5));
        $deskripsi = 'Pendaftaran murid baru ' . $student->name;

        // ==========================
        // 1) TRANSKASI HEADER
        // ==========================
        $transaksi = Transaksi::create([
            'kode_transaksi' => $kode,
            'tanggal_transaksi' => $tanggal->toDateString(),
            'type' => 'pendapatan belum diterima',
            'deskripsi' => $deskripsi,
            // AKUN = PBD (liabilitas)
            'akun_keuangan_id' => $akunPBDPMB,
            // LAWAN = Piutang PMB (asset)
            'parent_akun_id' => $akunPiutangPMB,
            'bidang_name' => $bidangId,
            'amount' => $totalBiaya,
            'saldo' => $totalBiaya,
            'student_id' => $student->id,
        ]);

        // ==========================
        // 2) JURNAL DOUBLE-ENTRY (PMB)
        // Debit  : Piutang PMB (asset)                = totalBiaya
        // Kredit : Pendapatan Belum Diterima – PMB    = totalBiaya
        // ==========================
        Ledger::create([
            'transaksi_id' => $transaksi->id,
            'akun_keuangan_id' => $akunPiutangPMB, // Piutang PMB
            'debit' => $totalBiaya,
            'credit' => 0,
        ]);

        Ledger::create([
            'transaksi_id' => $transaksi->id,
            'akun_keuangan_id' => $akunPBDPMB,     // Pendapatan Belum Diterima – PMB
            'debit' => 0,
            'credit' => $totalBiaya,
        ]);

        // ==========================
        // 3) TRACKER PIUTANG (1 ROW PER STUDENT)
        // ==========================
        Piutang::create([
            'student_id' => $student->id,
            'akun_keuangan_id' => $akunPiutangPMB,
            'jumlah' => $totalBiaya,
            'tanggal_jatuh_tempo' => now()->addMonths(1),
            'deskripsi' => 'Pendapatan PMB siswa ' . $student->name,
            'status' => 'belum_lunas',
            'bidang_name' => $bidangId,
        ]);

        // ==========================
        // 4) TRACKER PENDAPATAN BELUM DITERIMA (1 ROW PER STUDENT)
        // ==========================
        PendapatanBelumDiterima::create([
            'student_id' => $student->id,
            'jumlah' => $totalBiaya,
            'tanggal_pencatatan' => $tanggal->toDateString(),
            'deskripsi' => 'Pendapatan PMB siswa ' . $student->name,
            'bidang_name' => $bidangId,
            // 'user_id'         => auth()->id(), // kalau ada kolom ini & mau diisi
        ]);

        Log::info('[FinanceNew] handleNewStudentFinance selesai (single-row trackers)', [
            'student_id' => $student->id,
            'total_biaya' => $totalBiaya,
            'biaya_pairs' => $biayaPairs,
        ]);
    }

    public function handleNewStudentSPPFinance(Student $student, int $jumlah, int $bulan, int $tahun)
    {
        $transaksi = Transaksi::create([
            'kode_transaksi' => 'SPP-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5)),
            'tanggal_transaksi' => now()->format('Y-m-d'),
            'type' => 'pendapatan belum diterima',
            'deskripsi' => "Tagihan SPP siswa {$student->name} - {$bulan}/{$tahun}",

            // lawan = PBD SPP (akun kewajiban)
            'akun_keuangan_id' => config('akun.pendapatan_belum_diterima_spp'),

            // akun utama = Piutang SPP
            'parent_akun_id' => config('akun.piutang_spp'),

            'bidang_name' => 2,
            'amount' => $jumlah,
            'saldo' => $jumlah,

            // ==== tambahan baru ====
            'student_id' => $student->id,
            'sumber' => config('sumber.pendapatan_belum_diterima_spp'), // 50011
        ]);

        // Ledger: Debit Piutang, Kredit PBD
        Ledger::create([
            'transaksi_id' => $transaksi->id,
            'akun_keuangan_id' => config('akun.piutang_spp'),
            'debit' => $jumlah,
            'credit' => 0,
        ]);

        Ledger::create([
            'transaksi_id' => $transaksi->id,
            'akun_keuangan_id' => config('akun.pendapatan_belum_diterima_spp'),
            'debit' => 0,
            'credit' => $jumlah,
        ]);

        // Piutang (rekap by siswa)
        $existingPiutang = Piutang::where('student_id', $student->id)->first();
        if ($existingPiutang) {
            $existingPiutang->update([
                'jumlah' => $existingPiutang->jumlah + $jumlah,
                'tanggal_jatuh_tempo' => now()->addMonths(1),
                'deskripsi' => $existingPiutang->deskripsi . " + Tagihan SPP siswa {$bulan}/{$tahun}",
                'status' => 'belum_lunas',
                'bidang_name' => 2,
            ]);
        } else {
            Piutang::create([
                'student_id' => $student->id,
                'akun_keuangan_id' => config('akun.piutang_spp'),
                'jumlah' => $jumlah,
                'tanggal_jatuh_tempo' => now()->addMonths(1),
                'deskripsi' => "Tagihan SPP siswa {$student->name} - {$bulan}/{$tahun}",
                'status' => 'belum_lunas',
                'bidang_name' => 2,
            ]);
        }

        // PBD (rekap by siswa)
        $existingPBD = PendapatanBelumDiterima::where('student_id', $student->id)->first();
        if ($existingPBD) {
            $existingPBD->update([
                'jumlah' => $existingPBD->jumlah + $jumlah,
                'tanggal_pencatatan' => now()->format('Y-m-d'),
                'deskripsi' => $existingPBD->deskripsi . " + Tagihan SPP siswa {$bulan}/{$tahun}",
                'bidang_name' => 2,
            ]);
        } else {
            PendapatanBelumDiterima::create([
                'student_id' => $student->id,
                'jumlah' => $jumlah,
                'tanggal_pencatatan' => now()->format('Y-m-d'),
                'deskripsi' => "Tagihan SPP siswa {$student->name} - {$bulan}/{$tahun}",
                'bidang_name' => 2,
            ]);
        }

        return $transaksi; // <=== penting, untuk disimpan ke tagihan_spps.transaksi_id
    }

    public function deleteWithAllRelations(Student $student): void
    {
        DB::transaction(function () use ($student) {

            // Hapus file siswa
            if ($student->pas_photo) {
                Storage::disk('public')->delete($student->pas_photo);
            }
            if ($student->akte) {
                Storage::disk('public')->delete($student->akte);
            }
            if ($student->kk) {
                Storage::disk('public')->delete($student->kk);
            }

            // Hapus wali murid dan file KTP-nya
            foreach ($student->waliMurids as $wali) {
                if ($wali->foto_ktp) {
                    Storage::disk('public')->delete($wali->foto_ktp);
                }
                $wali->delete();
            }

            // Hapus rincian biaya
            $student->costs()->delete();

            // Hapus transaksi keuangan & ledgers
            Transaksi::where('deskripsi', 'like', '%' . $student->name . '%')->each(function ($transaksi) {
                $transaksi->ledgers()->delete();
                $transaksi->delete();
            });

            // Hapus tagihan SPP (kalau ada relasi)
            $student->tagihanSpps()->each(function ($tagihan) {
                $tagihan->delete();
            });

            // Hapus murid
            $student->delete();
        });
    }
}

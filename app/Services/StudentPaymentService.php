<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\Piutang;
use App\Models\Student;
use App\Models\PendapatanBelumDiterima;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentPaymentService
{
    /**
     * Mencatat pembayaran PMB siswa.
     *
     * Alur akuntansi (PMB):
     *  - Saat tagihan dibuat  : D Piutang PMB, K Pendapatan Belum Diterima – PMB
     *  - Saat pembayaran masuk: D Kas/Bank,   K Piutang PMB
     *
     * Pengakuan pendapatan (D PBD, K Pendapatan) dilakukan di proses terpisah.
     */
    public static function recordPayment(Student $student, float $jumlah, string $metode = 'tunai')
    {
        DB::beginTransaction();

        try {
            $tanggal = Carbon::now();

            // 1. Tentukan akun kas/bank berdasarkan metode pembayaran
            $akunKasBank = match ($metode) {
                'tunai' => config('akun.kas_pendidikan'),      // 1013
                'transfer' => config('akun.bank_pendidikan'),     // 1023
                default => throw new \InvalidArgumentException("Metode tidak valid"),
            };

            // Akun lain dari config
            $akunPendapatanPMB = config('akun.pendapatan_pmb');      // 202 (untuk transaksi lawan / laporan pendapatan)
            $akunPiutangPMB = config('akun.piutang_pmb');         // 1032
            // $akunPBDPMB     = config('akun.pendapatan_belum_diterima_pmb'); // 512 (dipakai nanti untuk pengakuan pendapatan, bukan di sini)

            // 2. Hitung saldo kas/bank terakhir (khusus bidang 2)
            $lastSaldoAkun = Transaksi::where('akun_keuangan_id', $akunKasBank)
                ->where('bidang_name', 2) // Ganti 2 jika bidang_name-nya dinamis
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoSebelumnyaAkun = $lastSaldoAkun ? $lastSaldoAkun->saldo : 0;
            $saldoBaru = $saldoSebelumnyaAkun + $jumlah;

            // 3. Transaksi penerimaan kas/bank
            $transaksiPenerimaan = Transaksi::create([
                'kode_transaksi' => 'PMB-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5)),
                'tanggal_transaksi' => $tanggal,
                'type' => 'penerimaan',
                'deskripsi' => 'Pembayaran murid ' . $student->name,
                'akun_keuangan_id' => $akunKasBank,        // Kas/Bank Pendidikan
                'parent_akun_id' => $akunPendapatanPMB,  // Lawan "secara kasat mata" (pendapatan) untuk tampilan tertentu
                'amount' => $jumlah,
                'saldo' => $saldoBaru,
                'bidang_name' => 2,                   // disesuaikan jika dinamis
                'sumber' => $student->id,
            ]);

            // 4. (Opsional) Transaksi LAWAN untuk pendapatan (kalau kamu masih pakai pola transaksi 2 sisi)
            //    Ini tidak mempengaruhi jurnal double-entry, hanya jejak di tabel transaksis.
            $transaksiLawan = Transaksi::create([
                'kode_transaksi' => 'PMB-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5)) . '-LAWAN',
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengeluaran',
                'deskripsi' => '(LAWAN) Pembayaran murid ' . $student->name,
                'akun_keuangan_id' => $akunPendapatanPMB,  // Pendapatan PMB
                'parent_akun_id' => $akunKasBank,        // Kas/Bank
                'amount' => $jumlah,
                'saldo' => $jumlah,
                'bidang_name' => 2,
                'sumber' => $student->id,
            ]);

            // 5. Jurnal double entry (yang benar-benar jadi acuan laporan)
            //    D Kas/Bank
            //    K Piutang PMB
            Ledger::insert([
                [
                    'transaksi_id' => $transaksiPenerimaan->id, // diikat ke transaksi penerimaan kas
                    'akun_keuangan_id' => $akunKasBank,             // Debit Kas/Bank
                    'debit' => $jumlah,
                    'credit' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'transaksi_id' => $transaksiPenerimaan->id,
                    'akun_keuangan_id' => $akunPiutangPMB,          // Kredit Piutang PMB
                    'debit' => 0,
                    'credit' => $jumlah,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // 6. Update Piutang PMB siswa
            $piutang = Piutang::where('student_id', $student->id)
                ->where('status', 'belum_lunas')
                ->first();

            if ($piutang) {
                $sisa = $piutang->jumlah - $jumlah;

                // Update jumlah sisa piutang
                $piutang->jumlah = max(0, $sisa);

                // Jika sisa = 0 maka lunas
                if ($sisa <= 0) {
                    $piutang->status = 'lunas';
                }

                $piutang->save();
            }

            // 7. Kurangi jumlah pada PendapatanBelumDiterima (TABLE),
            //    BUKAN akun CoA. Ini hanya tracker sisa "unearned" per siswa.
            $jumlahPembayaran = $jumlah;

            $pendapatans = PendapatanBelumDiterima::where('student_id', $student->id)
                ->where('jumlah', '>', 0)
                ->orderBy('tanggal_pencatatan', 'asc')
                ->get();

            foreach ($pendapatans as $pendapatan) {
                if ($jumlahPembayaran <= 0) {
                    break;
                }

                $kurangi = min($pendapatan->jumlah, $jumlahPembayaran);
                $pendapatan->jumlah -= $kurangi;
                $pendapatan->save();

                $jumlahPembayaran -= $kurangi;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal mencatat pembayaran murid: ' . $e->getMessage());
            throw $e;
        }
    }
}

<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\Ledger;
use App\Models\Piutang;
use App\Models\Student;
use App\Models\PendapatanBelumDiterima;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentPaymentSPPService
{
    /**
     * Mencatat pembayaran SPP siswa.
     *
     * Alur akuntansi (SPP):
     *  - Saat tagihan dibuat  : D Piutang SPP, K Pendapatan Belum Diterima – SPP
     *  - Saat pembayaran masuk: D Kas/Bank,   K Piutang SPP
     *
     * Pengakuan pendapatan (D PBD, K Pendapatan SPP) dilakukan di proses terpisah.
     */
    public static function recordPayment(Student $student, float $jumlah, string $metode = 'tunai')
    {
        DB::beginTransaction();

        try {
            $tanggal = Carbon::now();

            // 1. Ambil akun dari config
            $akunKasBank = match ($metode) {
                'tunai' => config('akun.kas_pendidikan'),     // 1013
                'transfer' => config('akun.bank_pendidikan'),    // 1023
                default => throw new \InvalidArgumentException("Metode tidak valid"),
            };

            $akunPendapatanSPP = config('akun.pendapatan_spp');    // 2021
            $akunPiutangSPP = config('akun.piutang_spp');       // 1031
            // $akunPBDSPP     = config('akun.pendapatan_belum_diterima_spp'); // 511 (dipakai di service pengakuan pendapatan, bukan di sini)

            // 2. Hitung saldo kas/bank terakhir (khusus bidang 2)
            $lastSaldoAkun = Transaksi::where('akun_keuangan_id', $akunKasBank)
                ->where('bidang_name', 2)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoSebelumnyaAkun = $lastSaldoAkun ? $lastSaldoAkun->saldo : 0;
            $saldoBaru = $saldoSebelumnyaAkun + $jumlah;

            // 3. Buat transaksi penerimaan (Kas/Bank)
            $kode = 'SPP-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

            $transaksiDebit = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal,
                'type' => 'penerimaan',
                'deskripsi' => 'Pembayaran SPP murid ' . $student->name,
                'akun_keuangan_id' => $akunKasBank,        // Kas/Bank Pendidikan
                'parent_akun_id' => $akunPendapatanSPP,  // Lawan "secara kasat mata"
                'amount' => $jumlah,
                'saldo' => $saldoBaru,
                'bidang_name' => 2,
                'sumber' => $student->id,
            ]);

            // 4. (Opsional) Transaksi LAWAN untuk pendapatan (sebagai pasangan di tabel transaksis)
            $transaksiKredit = Transaksi::create([
                'kode_transaksi' => $kode . '-LAWAN',
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengeluaran',
                'deskripsi' => '(LAWAN) Pembayaran SPP murid ' . $student->name,
                'akun_keuangan_id' => $akunPendapatanSPP,  // Pendapatan SPP
                'parent_akun_id' => $akunKasBank,
                'amount' => $jumlah,
                'saldo' => $jumlah,
                'bidang_name' => 2,
                'sumber' => $student->id,
            ]);

            // 5. Jurnal double-entry yang benar:
            //    D Kas/Bank
            //    K Piutang SPP
            Ledger::insert([
                [
                    'transaksi_id' => $transaksiDebit->id,
                    'akun_keuangan_id' => $akunKasBank,   // Debit Kas/Bank
                    'debit' => $jumlah,
                    'credit' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'transaksi_id' => $transaksiDebit->id,
                    'akun_keuangan_id' => $akunPiutangSPP, // Kredit Piutang SPP
                    'debit' => 0,
                    'credit' => $jumlah,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // 6. Update Piutang SPP siswa
            $piutang = Piutang::where('student_id', $student->id)
                ->where('status', 'belum_lunas')
                ->first();

            if ($piutang) {
                $sisa = $piutang->jumlah - $jumlah;

                // Tetap hapus tagihan tambahan di deskripsi
                $piutang->deskripsi = preg_replace(
                    '/\s\+\sTagihan SPP siswa\s\d{1,2}\/\d{4}/',
                    '',
                    $piutang->deskripsi
                );

                $piutang->jumlah = max(0, $sisa);

                // Jika lunas semua
                if ($piutang->jumlah <= 0) {
                    $piutang->status = 'lunas';
                    $piutang->deskripsi = 'Lunas semua';
                }

                $piutang->save();
            }

            // 7. Kurangi jumlah pada PendapatanBelumDiterima (TABLE),
            //    ini hanya tracker sisa "unearned" per siswa.
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

                // Tetap hapus tagihan tambahan di deskripsi
                $pendapatan->deskripsi = preg_replace(
                    '/\s\+\sTagihan SPP siswa\s\d{1,2}\/\d{4}/',
                    '',
                    $pendapatan->deskripsi
                );

                $pendapatan->jumlah -= $kurangi;

                // Jika sisa = 0 → Lunas semua
                if ($pendapatan->jumlah <= 0) {
                    $pendapatan->deskripsi = 'Lunas semua';
                }

                $pendapatan->save();
                $jumlahPembayaran -= $kurangi;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran SPP: ' . $e->getMessage());
            throw $e;
        }
    }
}

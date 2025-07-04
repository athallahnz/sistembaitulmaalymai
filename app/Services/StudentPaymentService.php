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
    public static function recordPayment(Student $student, float $jumlah, string $metode = 'tunai')
    {
        DB::beginTransaction();

        try {
            $tanggal = Carbon::now();

            // Ambil akun dari config
            $akunKeuangans = match ($metode) {
                'tunai' => config('akun.kas_pendidikan'),      // 1013
                'transfer' => config('akun.bank_pendidikan'),  // 1023
                default => throw new \InvalidArgumentException("Metode tidak valid"),
            };

            $akunPendapatan = config('akun.pendapatan_pmb'); // 2022
            $akunPiutang = config('akun.piutang_pmb');       // 1032
            $akunPBD = config('akun.pendapatan_belum_diterima'); // 203

            $lastSaldoAkun = Transaksi::where('akun_keuangan_id', $akunKeuangans)
                ->where('bidang_name', 2) // Ganti 2 jika bidang_name-nya dinamis
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoSebelumnyaAkun = $lastSaldoAkun ? $lastSaldoAkun->saldo : 0;
            $saldoBaru = $saldoSebelumnyaAkun + $jumlah;

            // 1. Buat transaksi penerimaan
            $transaksi = Transaksi::create([
                'kode_transaksi' => 'PMB-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5)),
                'tanggal_transaksi' => $tanggal,
                'type' => 'penerimaan',
                'deskripsi' => 'Pembayaran murid ' . $student->name,
                'akun_keuangan_id' => $akunKeuangans,
                'parent_akun_id' => $akunPendapatan,
                'amount' => $jumlah,
                'saldo' => $saldoBaru,
                'bidang_name' => 2, // disesuaikan jika dinamis
                'sumber' => $student->id,
            ]);

            $transaksi = Transaksi::create([
                'kode_transaksi' => 'PMB-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5)) . '-LAWAN',
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengeluaran',
                'deskripsi' => '(LAWAN) Pembayaran murid ' . $student->name,
                'akun_keuangan_id' => $akunPendapatan,
                'parent_akun_id' => $akunKeuangans,
                'amount' => $jumlah,
                'saldo' => $jumlah,
                'bidang_name' => 2, // disesuaikan jika dinamis
                'sumber' => $student->id,
            ]);

            // 2. Buat jurnal double entry
            Ledger::insert([
                [
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $akunKeuangans, // Debit Kas/Bank
                    'debit' => $jumlah,
                    'credit' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'transaksi_id' => $transaksi->id,
                    'akun_keuangan_id' => $akunPiutang, // Kredit Piutang
                    'debit' => 0,
                    'credit' => $jumlah,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // 3. Tandai piutang sebagai lunas (jika sudah terbayar sebagian/seluruhnya)
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

            // 4. Kurangi jumlah pada pendapatan belum diterima
            $jumlahPembayaran = $jumlah;

            $pendapatans = PendapatanBelumDiterima::where('student_id', $student->id)
                ->where('jumlah', '>', 0)
                ->orderBy('tanggal_pencatatan', 'asc')
                ->get();

            foreach ($pendapatans as $pendapatan) {
                if ($jumlahPembayaran <= 0)
                    break;

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

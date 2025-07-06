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

            $akunPendapatan = config('akun.pendapatan_spp'); // 2022

            $lastSaldoAkun = Transaksi::where('akun_keuangan_id', $akunKeuangans)
                ->where('bidang_name', 2)
                ->orderBy('tanggal_transaksi', 'asc')
                ->get()
                ->last();

            $saldoSebelumnyaAkun = $lastSaldoAkun ? $lastSaldoAkun->saldo : 0;
            $saldoBaru = $saldoSebelumnyaAkun + $jumlah;

            // 1. Buat transaksi penerimaan
            $kode = 'SPP-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5));

            $transaksiDebit = Transaksi::create([
                'kode_transaksi' => $kode,
                'tanggal_transaksi' => $tanggal,
                'type' => 'penerimaan',
                'deskripsi' => 'Pembayaran SPP murid ' . $student->name,
                'akun_keuangan_id' => $akunKeuangans,
                'parent_akun_id' => $akunPendapatan,
                'amount' => $jumlah,
                'saldo' => $saldoBaru,
                'bidang_name' => 2,
                'sumber' => $student->id,
            ]);

            $transaksiKredit = Transaksi::create([
                'kode_transaksi' => $kode . '-LAWAN',
                'tanggal_transaksi' => $tanggal,
                'type' => 'pengeluaran',
                'deskripsi' => '(LAWAN) Pembayaran SPP murid ' . $student->name,
                'akun_keuangan_id' => $akunPendapatan,
                'parent_akun_id' => $akunKeuangans,
                'amount' => $jumlah,
                'saldo' => $jumlah,
                'bidang_name' => 2,
                'sumber' => $student->id,
            ]);

            // 2. Buat jurnal double-entry
            Ledger::insert([
                [
                    'transaksi_id' => $transaksiDebit->id,
                    'akun_keuangan_id' => $akunKeuangans,
                    'debit' => $jumlah,
                    'credit' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'transaksi_id' => $transaksiDebit->id,
                    'akun_keuangan_id' => $akunPendapatan,
                    'debit' => 0,
                    'credit' => $jumlah,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran SPP: ' . $e->getMessage());
            throw $e;
        }
    }
}

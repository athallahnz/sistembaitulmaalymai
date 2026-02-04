<?php

namespace App\Services;

use App\Models\EduPayment;
use App\Models\EduPaymentItem;
use App\Models\Ledger;
use App\Models\Student;
use App\Models\TagihanSpp;
use App\Models\Transaksi;
use App\Models\Piutang;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentPaymentSPPService
{
    public static function syncPiutangSPP(int $studentId): void
    {
        $akunPiutangSPP = config('akun.piutang_spp'); // 1031

        $masihAdaTagihan = TagihanSpp::where('student_id', $studentId)
            ->where('status', 'belum_lunas')
            ->exists();

        Piutang::where('student_id', $studentId)
            ->where('akun_keuangan_id', $akunPiutangSPP)
            ->where('status', 'belum_lunas')
            ->update([
                'status' => $masihAdaTagihan ? 'belum_lunas' : 'lunas',
                'updated_at' => now(),
            ]);
    }

    /**
     * Pembayaran SPP: membuat edu_payments + items, lalu jurnal:
     *   D Kas/Bank
     *   K Piutang SPP
     * dan update tagihan_spps jadi lunas untuk item yang dibayar.
     *
     * @param Collection<int,TagihanSpp> $tagihans
     */
    public static function paySPP(Student $student, Collection $tagihans, string $metode, int $total): EduPayment
    {
        $tanggal = Carbon::now();
        $user = Auth::user();

        // 1) Resolve akun kas/bank berdasarkan metode (bidang pendidikan)
        $akunKasBank = match ($metode) {
            'tunai' => (int) config('akun.kas_pendidikan'),   // 1013
            'transfer' => (int) config('akun.bank_pendidikan'), // 1023
            default => throw new \InvalidArgumentException('Metode tidak valid'),
        };

        $akunPiutangSPP = (int) config('akun.piutang_spp'); // 1031

        // 2) Validasi total dari DB vs argumen (double safety)
        $dbTotal = (int) $tagihans->sum('jumlah');
        if ($dbTotal !== $total) {
            throw new \RuntimeException("Total pembayaran tidak konsisten (db={$dbTotal}, input={$total}).");
        }

        // 3) Hitung saldo kas/bank terakhir (bidang pendidikan = 2)
        $lastSaldo = Transaksi::where('akun_keuangan_id', $akunKasBank)
            ->where('bidang_name', 2)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoSebelumnya = $lastSaldo ? (float) $lastSaldo->saldo : 0;
        $saldoBaru = $saldoSebelumnya + $total;

        // 4) Buat transaksi header (1 transaksi cukup)
        $kode = 'SPP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));

        $transaksi = Transaksi::create([
            'kode_transaksi' => $kode,
            'tanggal_transaksi' => $tanggal,
            'type' => 'penerimaan',
            'deskripsi' => 'Pembayaran SPP murid ' . $student->name,
            'akun_keuangan_id' => $akunKasBank, // Kas/Bank Pendidikan
            'parent_akun_id' => $akunPiutangSPP, // lawan = Piutang SPP (lebih benar daripada pendapatan)
            'amount' => $total,
            'saldo' => $saldoBaru,
            'bidang_name' => 2,
            'student_id' => $student->id,
        ]);

        // 5) Ledger double-entry: D Kas/Bank, K Piutang
        Ledger::insert([
            [
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunKasBank,
                'debit' => $total,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPiutangSPP,
                'debit' => 0,
                'credit' => $total,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 6) Buat edu_payments (header) + items
        $payment = EduPayment::create([
            'student_id' => $student->id,
            // kolom lama jumlah boleh tetap diisi agar kompatibel
            'jumlah' => $total,
            'total' => $total,
            'tanggal' => $tanggal,
            'metode' => $metode,
            'akun_kas_bank_id' => $akunKasBank,
            'user_id' => $user?->id,
            'transaksi_id' => $transaksi->id,
            'verifikasi_token' => (string) Str::uuid(),
            'status_verifikasi' => 'verified',
            'catatan' => null,
        ]);

        // items: alokasi per tagihan spp
        $items = [];
        foreach ($tagihans as $t) {
            $items[] = [
                'edu_payment_id' => $payment->id,
                'bill_type' => 'spp',
                'bill_id' => $t->id,
                'amount' => (int) $t->jumlah,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        EduPaymentItem::insert($items);

        // 7) Update tagihan_spps jadi lunas + set transaksi_id (audit)
        TagihanSpp::whereIn('id', $tagihans->pluck('id'))
            ->update([
                'status' => 'lunas',
                'transaksi_id' => $transaksi->id, // optional audit
            ]);

        self::syncPiutangSPP($student->id);

        return $payment;
    }
}

<?php

namespace App\Services;

use App\Models\EduPayment;
use App\Models\EduPaymentItem;
use App\Models\Ledger;
use App\Models\Student;
use App\Models\StudentCost;
use App\Models\Transaksi;
use App\Models\Piutang;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentPaymentService
{
    public static function syncPiutangPMB(int $studentId): void
    {
        $akunPiutangPMB = config('akun.piutang_pmb'); // 1032

        $costIds = StudentCost::where('student_id', $studentId)->pluck('id');

        if ($costIds->isEmpty()) {
            return;
        }

        $totalBiaya = (float) StudentCost::whereIn('id', $costIds)->sum('jumlah');

        $totalBayar = (float) EduPaymentItem::where('bill_type', 'pmb')
            ->whereIn('bill_id', $costIds)
            ->sum('amount');

        $isLunas = $totalBiaya > 0 && $totalBayar >= $totalBiaya;

        Piutang::where('student_id', $studentId)
            ->where('akun_keuangan_id', $akunPiutangPMB)
            ->where('status', 'belum_lunas')
            ->update([
                'status' => $isLunas ? 'lunas' : 'belum_lunas',
                'updated_at' => now(),
            ]);
    }

    /**
     * Pembayaran PMB: membuat edu_payments + items, lalu jurnal:
     *   D Kas/Bank
     *   K Piutang PMB
     * dan update piutang_pmb jadi lunas untuk item yang dibayar.
     */
    public static function payPMB(Student $student, int $amount, string $metode): EduPayment
    {
        $tanggal = Carbon::now();
        $user = Auth::user();

        $akunKasBank = match ($metode) {
            'tunai' => (int) config('akun.kas_pendidikan'),     // 1013
            'transfer' => (int) config('akun.bank_pendidikan'), // 1023
            default => throw new \InvalidArgumentException('Metode tidak valid'),
        };

        $akunPiutangPMB = (int) config('akun.piutang_pmb'); // 1032

        // 1) Ambil biaya PMB siswa (student_costs) + lock agar aman
        $costs = StudentCost::where('student_id', $student->id)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        if ($costs->isEmpty()) {
            throw new \RuntimeException('Biaya PMB siswa belum diset (student_costs kosong).');
        }

        // 2) Hitung outstanding per cost berdasarkan edu_payment_items
        //    Map: bill_id => paid_amount
        $paidByCost = EduPaymentItem::query()
            ->selectRaw('bill_id, SUM(amount) as paid')
            ->where('bill_type', 'pmb')
            ->whereIn('bill_id', $costs->pluck('id'))
            ->groupBy('bill_id')
            ->pluck('paid', 'bill_id'); // [cost_id => paid]

        // 3) Alokasi FIFO
        $remaining = $amount;
        $items = []; // rows untuk edu_payment_items

        foreach ($costs as $c) {
            if ($remaining <= 0) break;

            $paid = (int) ($paidByCost[$c->id] ?? 0);
            $costAmount = (int) $c->jumlah;

            $outstanding = $costAmount - $paid;
            if ($outstanding <= 0) continue;

            $alloc = min($outstanding, $remaining);

            $items[] = [
                'bill_type' => 'pmb',
                'bill_id' => $c->id,
                'amount' => $alloc,
            ];

            $remaining -= $alloc;
        }

        if ($remaining > 0) {
            // Ini seharusnya tidak terjadi jika controller sudah cek sisa,
            // tapi guard tambahan tetap bagus.
            throw new \RuntimeException('Jumlah pembayaran melebihi outstanding PMB.');
        }

        // 4) Hitung saldo kas/bank terakhir bidang pendidikan (2)
        $lastSaldo = Transaksi::where('akun_keuangan_id', $akunKasBank)
            ->where('bidang_name', 2)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get()
            ->last();

        $saldoSebelumnya = $lastSaldo ? (float) $lastSaldo->saldo : 0;
        $saldoBaru = $saldoSebelumnya + $amount;

        // 5) Buat transaksi (1 header cukup)
        $kode = 'PMB-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));

        $transaksi = Transaksi::create([
            'kode_transaksi' => $kode,
            'tanggal_transaksi' => $tanggal,
            'type' => 'penerimaan',
            'deskripsi' => 'Pembayaran PMB murid ' . $student->name,
            'akun_keuangan_id' => $akunKasBank,
            'parent_akun_id' => $akunPiutangPMB, // lawan yang benar: Piutang PMB
            'amount' => $amount,
            'saldo' => $saldoBaru,
            'bidang_name' => 2,
            'student_id' => $student->id,
        ]);

        // 6) Ledger: D Kas/Bank, K Piutang PMB
        Ledger::insert([
            [
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunKasBank,
                'debit' => $amount,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => $akunPiutangPMB,
                'debit' => 0,
                'credit' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 7) Buat edu_payments header
        $payment = EduPayment::create([
            'student_id' => $student->id,
            'jumlah' => $amount, // legacy compat
            'total' => $amount,
            'tanggal' => $tanggal,
            'metode' => $metode,
            'akun_kas_bank_id' => $akunKasBank,
            'user_id' => $user?->id,
            'transaksi_id' => $transaksi->id,
            'verifikasi_token' => (string) Str::uuid(),
            'status_verifikasi' => 'verified',
            'catatan' => null,
        ]);

        // 8) Insert items
        foreach ($items as &$row) {
            $row['edu_payment_id'] = $payment->id;
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }
        EduPaymentItem::insert($items);

        self::syncPiutangPMB($student->id);

        return $payment;
    }
}

<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentCost;
use App\Models\Piutang;
use App\Models\PendapatanBelumDiterima;
use App\Models\Transaksi;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StudentFinanceService
{
    public function handleNewStudentFinance(Student $student, array $biayaPairs)
    {
        foreach ($biayaPairs as $akunId => $jumlah) {
            // Buat transaksi
            $transaksi = Transaksi::create([
                'kode_transaksi' => 'PMB-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5)),
                'tanggal_transaksi' => Carbon::now()->format('Y-m-d'),
                'type' => 'pendapatan belum diterima',
                'deskripsi' => 'Pendaftaran murid baru ' . $student->name,
                'akun_keuangan_id' => 103,
                'parent_akun_id' => config('akun.piutang_pmb'),
                'bidang_name' => 2,
                'amount' => $jumlah,
                'saldo' => $jumlah,
            ]);

            // Jurnal double-entry
            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => config('akun.piutang_pmb'), // Piutang
                'debit' => $jumlah,
                'credit' => 0,
            ]);

            Ledger::create([
                'transaksi_id' => $transaksi->id,
                'akun_keuangan_id' => config('akun.pendapatan_belum_diterima'), // Pendapatan belum diterima
                'debit' => 0,
                'credit' => $jumlah,
            ]);

            // Tambah ke piutang
            Piutang::create([
                'student_id' => $student->id,
                'akun_keuangan_id' => config('akun.piutang_pmb'),
                'jumlah' => $jumlah,
                'tanggal_jatuh_tempo' => now()->addMonths(1),
                'deskripsi' => 'Pendapatan PMB siswa ' . $student->name,
                'status' => 'belum_lunas',
                'bidang_name' => 2,
            ]);

            // Tambah ke pendapatan belum diterima
            PendapatanBelumDiterima::create([
                'student_id' => $student->id,
                'jumlah' => $jumlah,
                'tanggal_pencatatan' => now()->format('Y-m-d'),
                'deskripsi' => 'Pendapatan PMB siswa ' . $student->name,
                'bidang_name' => 2,
            ]);
        }
    }
}

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
use Illuminate\Support\Facades\Storage;

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
    public function handleNewStudentSPPFinance(Student $student, int $jumlah, int $bulan, int $tahun)
    {
        // Buat transaksi
        $transaksi = Transaksi::create([
            'kode_transaksi' => 'SPP-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(rand()), 0, 5)),
            'tanggal_transaksi' => now()->format('Y-m-d'),
            'type' => 'pendapatan belum diterima',
            'deskripsi' => "Tagihan SPP siswa {$student->name} - {$bulan}/{$tahun}",
            'akun_keuangan_id' => 103,
            'parent_akun_id' => config('akun.piutang_spp'),
            'bidang_name' => 2,
            'amount' => $jumlah,
            'saldo' => $jumlah,
        ]);

        // Jurnal
        Ledger::create([
            'transaksi_id' => $transaksi->id,
            'akun_keuangan_id' => config('akun.piutang_spp'),
            'debit' => $jumlah,
            'credit' => 0,
        ]);

        Ledger::create([
            'transaksi_id' => $transaksi->id,
            'akun_keuangan_id' => config('akun.pendapatan_belum_diterima'),
            'debit' => 0,
            'credit' => $jumlah,
        ]);

        // Piutang
        $existingPiutang = Piutang::where([
            ['student_id', '=', $student->id],
        ])->first();

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

        // Pendapatan Belum Diterima
        $existingPBD = PendapatanBelumDiterima::where([
            ['student_id', '=', $student->id],
        ])->first();

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

            // (Opsional) Hapus piutang dan pendapatan belum diterima jika punya relasi
            $student->tagihanSpps()->each(function ($tagihan) {
                $tagihan->delete();
            });


            // Hapus murid
            $student->delete();
        });
    }
}

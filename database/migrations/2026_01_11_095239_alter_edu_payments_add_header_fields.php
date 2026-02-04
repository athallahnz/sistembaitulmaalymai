<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_payments', function (Blueprint $table) {
            // ===== Header fields (baru) =====

            // total payment header (jangan hanya mengandalkan `jumlah` lama)
            $table->unsignedBigInteger('total')->nullable()->after('jumlah');

            // metode bayar
            $table->enum('metode', ['tunai', 'transfer'])->nullable()->after('tanggal');

            // akun kas/bank yang dipakai saat pembayaran (1013/1023/...)
            $table->unsignedBigInteger('akun_kas_bank_id')->nullable()->after('metode');
            $table->foreign('akun_kas_bank_id')
                ->references('id')
                ->on('akun_keuangans')
                ->nullOnDelete();

            // user kasir / petugas input
            $table->unsignedBigInteger('user_id')->nullable()->after('akun_kas_bank_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // link ke transaksi/jurnal header (audit trail)
            $table->unsignedBigInteger('transaksi_id')->nullable()->after('user_id');
            $table->foreign('transaksi_id')
                ->references('id')
                ->on('transaksis')
                ->nullOnDelete();

            // status verifikasi (opsional, tapi sangat membantu)
            $table->enum('status_verifikasi', ['pending', 'verified', 'rejected'])
                ->default('verified')
                ->after('verifikasi_token');

            // catatan tambahan (opsional)
            $table->text('catatan')->nullable()->after('status_verifikasi');

            // index untuk query cepat
            $table->index(['student_id', 'tanggal']);
            $table->index(['transaksi_id']);
            $table->index(['akun_kas_bank_id']);
        });
    }

    public function down(): void
    {
        Schema::table('edu_payments', function (Blueprint $table) {
            // drop index (Laravel akan auto-naming; pakai dropIndex array)
            $table->dropIndex(['student_id', 'tanggal']);
            $table->dropIndex(['transaksi_id']);
            $table->dropIndex(['akun_kas_bank_id']);

            // drop foreign keys
            $table->dropForeign(['akun_kas_bank_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['transaksi_id']);

            // drop columns
            $table->dropColumn([
                'total',
                'metode',
                'akun_kas_bank_id',
                'user_id',
                'transaksi_id',
                'status_verifikasi',
                'catatan',
            ]);
        });
    }
};

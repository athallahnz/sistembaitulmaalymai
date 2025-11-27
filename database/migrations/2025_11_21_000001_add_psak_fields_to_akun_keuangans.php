<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('akun_keuangans', function (Blueprint $table) {

            // Kategori PSAK 45 (untuk Neraca & Laporan Aktivitas)
            $table->enum('kategori_psak', [
                'aset_lancar',
                'aset_tidak_lancar',
                'liabilitas_jangka_pendek',
                'liabilitas_jangka_panjang',
                'aset_neto_tidak_terikat',
                'aset_neto_terikat_temporer',
                'aset_neto_terikat_permanen',
                'pendapatan',
                'beban',
            ])->nullable()->after('tipe_akun');

            // Pembatasan dana (khusus pendapatan & aset neto)
            $table->enum('pembatasan', [
                'tidak_terikat',
                'terikat_temporer',
                'terikat_permanen',
            ])->nullable()->after('kategori_psak');

            // Penanda akun kas/bank (untuk Laporan Arus Kas)
            $table->boolean('is_kas_bank')->default(false)->after('saldo_normal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akun_keuangans', function (Blueprint $table) {
            $table->dropColumn([
                'kategori_psak',
                'pembatasan',
                'is_kas_bank',
            ]);
        });
    }
};

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
        Schema::table('hutangs', function (Blueprint $table) {
            $table->string('kode_transaksi')->nullable()->after('deskripsi');
            $table->unsignedBigInteger('transaksi_id')->nullable()->after('kode_transaksi');

            // kalau mau FK ke transaksis, pastikan tabel & naming sesuai
            // $table->foreign('transaksi_id')->references('id')->on('transaksis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hutangs', function (Blueprint $table) {
            // kalau ada FK, drop dulu
            // $table->dropForeign(['transaksi_id']);

            $table->dropColumn(['kode_transaksi', 'transaksi_id']);
        });
    }
};

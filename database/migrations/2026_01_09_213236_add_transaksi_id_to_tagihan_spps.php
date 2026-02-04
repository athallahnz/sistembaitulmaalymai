<?php

// database/migrations/2026_01_09_000002_add_transaksi_id_to_tagihan_spps.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tagihan_spps', function (Blueprint $table) {
            $table->unsignedBigInteger('transaksi_id')->nullable()->after('student_id');

            $table->foreign('transaksi_id')
                ->references('id')->on('transaksis')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_spps', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->dropColumn('transaksi_id');
        });
    }
};


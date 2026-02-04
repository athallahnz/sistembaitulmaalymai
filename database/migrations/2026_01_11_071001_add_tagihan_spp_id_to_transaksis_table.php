<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->unsignedBigInteger('tagihan_spp_id')->nullable()->after('student_id');

            $table->foreign('tagihan_spp_id')
                ->references('id')->on('tagihan_spps')
                ->nullOnDelete();

            // 1 tagihan SPP hanya boleh punya 1 transaksi pengakuan
            $table->unique(['tagihan_spp_id', 'type'], 'uq_transaksis_tagihan_spp_type');
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropUnique('uq_transaksis_tagihan_spp_type');
            $table->dropForeign(['tagihan_spp_id']);
            $table->dropColumn('tagihan_spp_id');
        });
    }
};

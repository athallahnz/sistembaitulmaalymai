<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('infaq_kemasjidans', function (Blueprint $table) {
            // sesuaikan posisinya jika Anda ingin after('no_hp') dll.
            $table->unsignedBigInteger('warga_id')->nullable()->after('no_hp');

            // FK (asumsi PK wargas.id)
            $table->foreign('warga_id')
                ->references('id')
                ->on('wargas')
                ->nullOnDelete(); // jika warga dihapus, relasi jadi null
        });
    }

    public function down(): void
    {
        Schema::table('infaq_kemasjidans', function (Blueprint $table) {
            $table->dropForeign(['warga_id']);
            $table->dropColumn('warga_id');
        });
    }
};

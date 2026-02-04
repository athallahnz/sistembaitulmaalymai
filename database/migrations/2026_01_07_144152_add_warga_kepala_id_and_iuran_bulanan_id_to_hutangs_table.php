<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hutangs', function (Blueprint $table) {
            // Relasi ke kepala keluarga (KK)
            $table->foreignId('warga_kepala_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('wargas')       // GANTI jika tabel KK Anda bukan 'wargas'
                ->nullOnDelete();

            // Relasi ke iuran bulanan (audit trail)
            $table->foreignId('iuran_bulanan_id')
                ->nullable()
                ->after('warga_kepala_id')
                ->constrained('iuran_bulanan')
                ->nullOnDelete();

            $table->index(['warga_kepala_id', 'status'], 'hutangs_warga_kepala_status_idx');
            $table->index(['iuran_bulanan_id'], 'hutangs_iuran_bulanan_idx');
        });
    }

    public function down(): void
    {
        Schema::table('hutangs', function (Blueprint $table) {
            // Drop FK dulu baru drop kolom
            $table->dropForeign(['warga_kepala_id']);
            $table->dropForeign(['iuran_bulanan_id']);

            $table->dropIndex('hutangs_warga_kepala_status_idx');
            $table->dropIndex('hutangs_iuran_bulanan_idx');

            $table->dropColumn(['warga_kepala_id', 'iuran_bulanan_id']);
        });
    }
};

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
        Schema::create('iuran_bulanan', function (Blueprint $table) {
            $table->id();

            // Hanya mengarah ke kepala keluarga (tabel wargas)
            $table->foreignId('warga_kepala_id')
                ->constrained('wargas')
                ->onDelete('cascade');

            // Periode iuran
            $table->smallInteger('tahun');     // contoh: 2025
            $table->tinyInteger('bulan');      // 1–12

            // Nilai tagihan & pembayaran
            $table->unsignedInteger('nominal_tagihan')->default(0);
            $table->unsignedInteger('nominal_bayar')->default(0);

            // Status iuran
            $table->enum('status', ['belum', 'sebagian', 'lunas'])->default('belum');

            // Info pembayaran
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode_bayar', 50)->nullable(); // cash, transfer, dll (opsional)

            $table->timestamps();

            // Satu KK hanya boleh punya 1 baris per bulan-tahun
            $table->unique(['warga_kepala_id', 'tahun', 'bulan'], 'uniq_iuran_per_bulan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iuran_bulanan');
    }
};

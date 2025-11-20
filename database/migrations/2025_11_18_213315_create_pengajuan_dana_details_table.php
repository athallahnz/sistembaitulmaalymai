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
        Schema::create('pengajuan_dana_details', function (Blueprint $table) {
            $table->id();
            // Hapus detail jika header dihapus (cascade)
            $table->foreignId('pengajuan_dana_id')->constrained('pengajuan_danas')->onDelete('cascade');
            $table->foreignId('akun_keuangan_id')->constrained('akun_keuangans'); // Relasi ke CoA

            $table->string('keterangan_item');
            $table->decimal('kuantitas', 10, 2); // Jumlah barang (bisa koma)
            $table->decimal('harga_pokok', 15, 2); // Harga satuan
            $table->decimal('jumlah_dana', 15, 2); // Subtotal (Qty x Harga)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_dana_details');
    }
};

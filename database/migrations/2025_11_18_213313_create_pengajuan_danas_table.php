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
        Schema::create('pengajuan_danas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Siapa yang buat
            $table->foreignId('bidang_id')->nullable()->constrained('bidangs'); // Dari bidang mana

            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->decimal('total_jumlah', 15, 2)->default(0); // Total rupiah

            // Status Workflow
            $table->enum('status', ['Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Dicairkan'])
                ->default('Menunggu Verifikasi');

            // Validator Info (Diisi saat diapprove)
            $table->foreignId('validator_id')->nullable()->constrained('users');
            $table->timestamp('tgl_verifikasi')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_danas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('piutangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Relasi ke tabel users
            $table->foreignId('akun_keuangan_id')->constrained('akun_keuangans')->onDelete('cascade'); // Relasi ke akun keuangan
            $table->decimal('jumlah', 15, 2); // Jumlah piutang
            $table->date('tanggal_jatuh_tempo'); // Tanggal jatuh tempo
            $table->text('deskripsi')->nullable(); // Keterangan tambahan
            $table->enum('status', ['belum_lunas', 'lunas'])->default('belum_lunas'); // Status pembayaran
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piutangs');
    }
};

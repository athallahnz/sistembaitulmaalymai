<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tagihan_spps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->year('tahun'); // contoh: 2025
            $table->tinyInteger('bulan'); // 1 = Januari, 2 = Februari, ..., 12 = Desember
            $table->integer('jumlah'); // nominal per bulan
            $table->enum('status', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->date('tanggal_aktif')->nullable(); // tanggal aktif tagihan
            $table->timestamps();

            $table->unique(['student_id', 'tahun', 'bulan']); // mencegah duplikasi tagihan per bulan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_spps');
    }
};


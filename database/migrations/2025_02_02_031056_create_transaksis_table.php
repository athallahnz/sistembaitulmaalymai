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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi');
            $table->date('tanggal_transaksi');
            $table->enum('type', ['penerimaan', 'pengeluaran']);
            $table->unsignedBigInteger('akun_keuangan_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('deskripsi');
            $table->decimal('amount', 15, 2);
            $table->string('bidang_name')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};

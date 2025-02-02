<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->unique(); // Kode unik untuk transaksi
            $table->date('tanggal_transaksi'); // Tanggal transaksi
            $table->text('deskripsi'); // Deskripsi transaksi
            $table->foreignId('akun_keuangan_id')->constrained('akun_keuangans')->onDelete('cascade'); // Menghubungkan dengan akun_keuangans
            $table->decimal('debit', 15, 2)->default(0); // Jumlah debit
            $table->decimal('kredit', 15, 2)->default(0); // Jumlah kredit
            $table->decimal('saldo', 15, 2)->default(0); // Saldo setelah transaksi
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

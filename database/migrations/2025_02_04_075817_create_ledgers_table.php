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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('traksaksi_id'); // Transaksi terkait
            $table->unsignedBigInteger('akun_keuangan_id'); // Akun terkait
            $table->decimal('debit', 15, 2)->default(0); // Nilai debit
            $table->decimal('credit', 15, 2)->default(0); // Nilai kredit
            $table->foreign('traksaksi_id')->references('id')->on('traksaksis')->onDelete('cascade');
            $table->foreign('akun_keuangan_id')->references('id')->on('akun_keuangans')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};

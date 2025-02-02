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
        Schema::create('akun_keuangans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_akun'); // Descriptive name of the account
            $table->enum('tipe_akun', ['asset', 'liability', 'revenue', 'expense', 'equity']); // Account category
            $table->string('kode_akun')->unique(); // Unique code for the account
            $table->foreignId('parent_id')->nullable()->constrained('akun_keuangans')->onDelete('cascade'); // Parent account (for sub-accounts)
            $table->enum('saldo_normal', ['debit', 'kredit']); // Indicates normal balance (Debit or Credit)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_keuangans');
    }
};

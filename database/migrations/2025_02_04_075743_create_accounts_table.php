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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id(); // ID unik untuk setiap akun
            $table->string('account_code')->unique(); // Kode akun (misalnya: 101, 102, 1031)
            $table->string('name'); // Nama akun (misalnya: Kas, Bank, SPP, Gaji Guru)
            $table->enum('type', ['aset', 'liabilitas', 'ekuitas', 'pendapatan', 'beban']); // Jenis akun
            $table->unsignedBigInteger('parent_id')->nullable(); // Untuk akun yang memiliki hierarki
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

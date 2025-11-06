<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('wargas', function (Blueprint $table) {
            $table->id(); // ID warga
            $table->string('nama'); // Nama Warga
            $table->string('rt'); // RT
            $table->string('alamat')->nullable(); // Alamat
            $table->string('no')->nullable(); // Nomor rumah
            $table->string('hp')->nullable(); // Nomor HP
            $table->foreignId('infaq_sosial_id')->constrained('infaq_sosials')->onDelete('cascade'); // Relasi ke tabel infaq_sosial
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wargas');
    }
};

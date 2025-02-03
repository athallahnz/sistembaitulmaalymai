<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('saldo_keuangan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_keuangan_id');
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            $table->date('periode'); // Biasanya per bulan
            $table->timestamps();

            $table->foreign('akun_keuangan_id')->references('id')->on('akun_keuangans')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('saldo_keuangan');
    }
};


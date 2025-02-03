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
        Schema::table('transaksis', function (Blueprint $table) {
            $table->unsignedBigInteger('sumber')->nullable(); // Menambahkan kolom sumber

            // Menambahkan foreign key constraint
            $table->foreign('sumber')->references('id')->on('akun_keuangans')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Menghapus foreign key constraint
            $table->dropForeign(['sumber']);
            // Menghapus kolom sumber
            $table->dropColumn('sumber');
        });
    }

};

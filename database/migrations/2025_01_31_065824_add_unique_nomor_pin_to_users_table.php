<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('nomor'); // Menambahkan unique constraint pada nomor
            $table->unique('pin');   // Menambahkan unique constraint pada pin
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nomor']);
            $table->dropUnique(['pin']);
        });
    }
};

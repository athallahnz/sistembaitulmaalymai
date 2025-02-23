<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('hutangs', function (Blueprint $table) {
            $table->string('bidang_name')->nullable(); // Menambahkan kolom bidang_name
        });

        Schema::table('piutangs', function (Blueprint $table) {
            $table->string('bidang_name')->nullable(); // Menambahkan kolom bidang_name
        });
    }

    public function down()
    {
        Schema::table('hutangs', function (Blueprint $table) {
            $table->dropColumn('bidang_name'); // Menghapus kolom jika migrasi dibatalkan
        });

        Schema::table('piutangs', function (Blueprint $table) {
            $table->dropColumn('bidang_name'); // Menghapus kolom jika migrasi dibatalkan
        });
    }
};

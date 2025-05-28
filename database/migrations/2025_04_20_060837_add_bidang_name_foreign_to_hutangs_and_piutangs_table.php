<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tambahkan kolom dan relasi ke tabel hutangs
        Schema::table('hutangs', function (Blueprint $table) {
            $table->unsignedBigInteger('bidang_name')->nullable()->after('status');
            $table->foreign('bidang_name')->references('id')->on('bidangs')->onDelete('set null');
        });

        // Tambahkan kolom dan relasi ke tabel piutangs
        Schema::table('piutangs', function (Blueprint $table) {
            $table->unsignedBigInteger('bidang_name')->nullable()->after('status');
            $table->foreign('bidang_name')->references('id')->on('bidangs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Hapus foreign key dan kolom dari hutangs
        Schema::table('hutangs', function (Blueprint $table) {
            $table->dropForeign(['bidang_name']);
            $table->dropColumn('bidang_name');
        });

        // Hapus foreign key dan kolom dari piutangs
        Schema::table('piutangs', function (Blueprint $table) {
            $table->dropForeign(['bidang_name']);
            $table->dropColumn('bidang_name');
        });
    }
};





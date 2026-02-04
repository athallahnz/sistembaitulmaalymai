<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_awal')
                ->after('name')
                ->comment('Tahun awal ajaran, contoh: 2025');
        });
    }

    public function down(): void
    {
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->dropColumn('tahun_awal');
        });
    }
};

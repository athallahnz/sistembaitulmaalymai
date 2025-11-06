<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_pin_to_wargas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wargas', function (Blueprint $table) {
            // simpan HASH dari PIN (bukan PIN mentah)
            $table->string('pin', 255)->nullable()->after('hp');
            // kalau sudah pasti wajib, nanti bisa ubah ke ->nullable(false)
        });
    }
    public function down(): void
    {
        Schema::table('wargas', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};


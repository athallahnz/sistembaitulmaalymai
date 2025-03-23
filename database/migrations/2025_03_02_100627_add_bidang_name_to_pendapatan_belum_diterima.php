<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pendapatan_belum_diterima', function (Blueprint $table) {
            $table->string('bidang_name', 255)->nullable()->after('user_id');
        });
    }

    public function down()
    {
        Schema::table('pendapatan_belum_diterima', function (Blueprint $table) {
            $table->dropColumn('bidang_name');
        });
    }
};

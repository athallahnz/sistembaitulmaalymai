<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('piutangs', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('akun_keuangan_id');

            // Menambahkan foreign key ke akun_keuangans
            $table->foreign('parent_id')->references('id')->on('akun_keuangans')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('piutangs', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};

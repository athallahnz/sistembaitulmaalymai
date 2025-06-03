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
        Schema::table('wali_murids', function (Blueprint $table) {
            $table->string('email')->after('no_hp')->nullable();
            $table->string('pendidikan_terakhir')->after('email')->nullable();
            $table->string('pekerjaan')->after('pendidikan_terakhir')->nullable();
        });
    }

    public function down()
    {
        Schema::table('wali_murids', function (Blueprint $table) {
            $table->dropColumn(['email', 'pendidikan_terakhir', 'pekerjaan']);
        });
    }

};

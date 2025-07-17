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
        Schema::table('sidebar_settings', function (Blueprint $table) {
            $table->string('background_login')->nullable()->after('background_color');
        });
    }

    public function down()
    {
        Schema::table('sidebar_settings', function (Blueprint $table) {
            $table->dropColumn('background_login');
        });
    }

};

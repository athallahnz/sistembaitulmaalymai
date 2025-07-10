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
        Schema::table('edu_payments', function (Blueprint $table) {
            $table->string('verifikasi_token')->nullable()->unique();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edu_payments', function (Blueprint $table) {
            //
        });
    }
};

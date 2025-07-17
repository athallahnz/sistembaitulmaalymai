<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sidebar_settings', function (Blueprint $table) {
            $table->string('background_color')->nullable()->default('#222e3c');
            $table->string('cta_background_color')->nullable()->default('#2b3947');
            $table->string('link_color')->nullable()->default('rgba(233, 236, 239, 0.5)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidebar_settings', function (Blueprint $table) {
            //
        });
    }
};

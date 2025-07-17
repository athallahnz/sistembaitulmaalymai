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
            $table->string('link_hover_color')->nullable()->default('rgba(233, 236, 239, 0.75)');
            $table->string('link_active_color')->nullable()->default('#e9ecef');
            $table->string('link_active_border_color')->nullable()->default('#f2c89d');

            $table->string('cta_button_color')->nullable()->default('#81431E');
            $table->string('cta_button_hover_color')->nullable()->default('#984F23');
            $table->string('cta_button_text_color')->nullable()->default('#fff5e1');
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

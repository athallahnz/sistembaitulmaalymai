<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sidebar_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable(); // path logo
            $table->string('title')->default('Sistem Baitul Maal');
            $table->string('subtitle')->nullable()->default('Yayasan Masjid Al Iman Surabaya');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_settings');
    }
};

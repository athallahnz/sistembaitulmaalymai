<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('slideshows', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->string('image', 255);
            $table->string('title', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps(); // created_at & updated_at (nullable)
        });
    }

    /**
     * Rollback migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('slideshows');
    }
};

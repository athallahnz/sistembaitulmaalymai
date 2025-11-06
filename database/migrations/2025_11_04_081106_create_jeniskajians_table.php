<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jeniskajians', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->string('name', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeniskajians');
    }
};

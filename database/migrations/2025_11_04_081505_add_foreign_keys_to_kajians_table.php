<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('kajians')) {
            Schema::create('kajians', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('youtube_link', 255);
                $table->string('image', 255)->nullable();
                $table->timestamp('start_time')->nullable();
                $table->unsignedBigInteger('jeniskajian_id');
                $table->unsignedBigInteger('ustadz_id');
                $table->timestamps();
            });
        } else {
            // Jika tabel sudah ada, pastikan kolom minimal tersedia (opsional)
            Schema::table('kajians', function (Blueprint $table) {
                if (!Schema::hasColumn('kajians', 'jeniskajian_id')) {
                    $table->unsignedBigInteger('jeniskajian_id')->after('start_time');
                }
                if (!Schema::hasColumn('kajians', 'ustadz_id')) {
                    $table->unsignedBigInteger('ustadz_id')->after('jeniskajian_id');
                }
                if (!Schema::hasColumn('kajians', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('kajians', function (Blueprint $table) {
            $table->dropForeign('kajians_jeniskajian_id_fk');
            $table->dropForeign('kajians_ustadz_id_fk');
        });
    }
};

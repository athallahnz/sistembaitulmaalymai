<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('infaq_sosials') && !Schema::hasTable('infaq_kemasjidan_legacy')) {
            Schema::rename('infaq_sosials', 'infaq_kemasjidan_legacy');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('infaq_kemasjidan_legacy') && !Schema::hasTable('infaq_sosials')) {
            Schema::rename('infaq_kemasjidan_legacy', 'infaq_sosials');
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('akun_keuangans', function (Blueprint $table) {
            $table->boolean('show_on_dashboard')->default(false)->after('icon');

            $table->enum('dashboard_scope', ['BIDANG', 'BENDAHARA', 'YAYASAN'])
                ->nullable()->after('show_on_dashboard');

            $table->enum('dashboard_section', ['asset', 'liability', 'revenue', 'expense', 'kpi'])
                ->nullable()->after('dashboard_scope');

            $table->enum('dashboard_calc', ['rollup_children_period', 'rollup_children_ytd', 'balance_asof', 'custom'])
                ->nullable()->after('dashboard_section');

            $table->integer('dashboard_order')->nullable()->after('dashboard_calc');

            $table->string('dashboard_title')->nullable()->after('dashboard_order');

            $table->string('dashboard_link_route')->nullable()->after('dashboard_title');
            $table->string('dashboard_link_param')->nullable()->after('dashboard_link_route');

            $table->enum('dashboard_format', ['currency', 'number'])
                ->default('currency')->after('dashboard_link_param');

            $table->boolean('dashboard_masked')->default(false)->after('dashboard_format');

            $table->string('dashboard_icon')->nullable()->after('dashboard_masked');
        });
    }

    public function down(): void
    {
        Schema::table('akun_keuangans', function (Blueprint $table) {
            $table->dropColumn([
                'show_on_dashboard',
                'dashboard_scope',
                'dashboard_section',
                'dashboard_calc',
                'dashboard_order',
                'dashboard_title',
                'dashboard_link_route',
                'dashboard_link_param',
                'dashboard_format',
                'dashboard_masked',
                'dashboard_icon',
            ]);
        });
    }
};

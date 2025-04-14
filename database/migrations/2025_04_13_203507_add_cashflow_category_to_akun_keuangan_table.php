<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashflowCategoryToAkunKeuanganTable extends Migration
{
    public function up()
    {
        Schema::table('akun_keuangans', function (Blueprint $table) {
            $table->enum('cashflow_category', ['operasional', 'investasi', 'pendanaan'])
                ->nullable()
                ->after('saldo_normal');
        });
    }

    public function down()
    {
        Schema::table('akun_keuangans', function (Blueprint $table) {
            $table->dropColumn('cashflow_category');
        });
    }
}

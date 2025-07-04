<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStudentIdToPiutangsAndPendapatan extends Migration
{
    public function up()
    {
        Schema::table('piutangs', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
        });

        Schema::table('pendapatan_belum_diterima', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('piutangs', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
        });

        Schema::table('pendapatan_belum_diterima', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
        });
    }
}

<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWaliMuridsAddStudentId extends Migration
{
    public function up()
    {
        // Tambahkan student_id di tabel wali_murids
        Schema::table('wali_murids', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('id');

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
        });

    }

    public function down()
    {
        // Tambahkan kembali wali_murid_id ke students
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('wali_murid_id')->nullable()->after('kk');
            $table->foreign('wali_murid_id')
                ->references('id')
                ->on('wali_murids')
                ->onDelete('set null');
        });

    }
}

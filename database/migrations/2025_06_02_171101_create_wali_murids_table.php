<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaliMuridsTable extends Migration
{
    public function up()
    {
        Schema::create('wali_murids', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nik')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('hubungan', ['Ayah', 'Ibu', 'Wali']);
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('foto_ktp')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wali_murids');
    }
}
